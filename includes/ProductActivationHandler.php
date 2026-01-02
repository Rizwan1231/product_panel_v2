<?php
/**
 * ProductActivationHandler Class
 *
 * Centralized handler for all product activations, email validations, and renewals.
 * Reads configuration from the database and executes accordingly.
 *
 * @version 1.0
 */

class ProductActivationHandler
{
    private $db;
    private $lastLogId = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Log automation API call
     *
     * @param string $logType 'activation', 'email_validation', 'renewal'
     * @param array $data Log data
     * @return int|null Log ID
     */
    public function logApiCall($logType, $data)
    {
        // Check if table exists first (graceful handling if migration not run)
        try {
            $tableCheck = @$this->db->query("SHOW TABLES LIKE 'automation_logs'");
            if ($this->db->num_rows() == 0) {
                // Table doesn't exist, skip logging silently
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        $productName = '';
        if (!empty($data['product_id'])) {
            $this->db->query("SELECT `product_name` FROM `products` WHERE `id` = '%d'", $data['product_id']);
            if ($this->db->num_rows() > 0) {
                $row = $this->db->getdata();
                $productName = $row['product_name'];
            }
        }

        try {
            $this->db->query(
                "INSERT INTO `automation_logs`
                (`log_type`, `product_id`, `product_name`, `user_id`, `invoice_id`, `order_id`,
                 `api_url`, `api_method`, `request_params`, `response_raw`, `response_parsed`,
                 `status`, `error_message`, `extracted_data`, `instructions_generated`,
                 `ip_address`, `user_agent`, `execution_time`)
                VALUES ('%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                $logType,
                $data['product_id'] ?? 0,
                $productName,
                $data['user_id'] ?? 0,
                $data['invoice_id'] ?? 0,
                $data['order_id'] ?? 0,
                $data['api_url'] ?? '',
                $data['api_method'] ?? 'GET',
                json_encode($data['request_params'] ?? []),
                $data['response_raw'] ?? '',
                json_encode($data['response_parsed'] ?? []),
                $data['status'] ?? 'error',
                $data['error_message'] ?? '',
                json_encode($data['extracted_data'] ?? []),
                $data['instructions_generated'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $data['execution_time'] ?? 0
            );

            $this->lastLogId = $this->db->inserted_id();
            return $this->lastLogId;
        } catch (Exception $e) {
            // Silently fail if logging doesn't work
            return null;
        }
    }

    /**
     * Get last log ID
     *
     * @return int|null
     */
    public function getLastLogId()
    {
        return $this->lastLogId;
    }

    /**
     * Check if instructions contain unresolved placeholders
     *
     * @param string $instructions
     * @return array List of unresolved placeholders
     */
    public function getUnresolvedPlaceholders($instructions)
    {
        $unresolved = [];
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $instructions, $matches)) {
            $unresolved = $matches[1];
        }
        return $unresolved;
    }

    /**
     * Validate that all required data was extracted from API response
     *
     * @param array $responseMapping Expected response mapping
     * @param array $extractedData Actually extracted data
     * @return array ['valid' => bool, 'missing' => array]
     */
    public function validateExtractedData($responseMapping, $extractedData)
    {
        $missing = [];

        foreach ($responseMapping as $varName => $path) {
            if ($path === '_raw_response') {
                continue; // Raw response is always available
            }
            if (!isset($extractedData[$varName]) || $extractedData[$varName] === null || $extractedData[$varName] === '') {
                $missing[] = $varName;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * Get product configuration from database
     *
     * @param int $productId
     * @return array|null
     */
    public function getProductConfig($productId)
    {
        $this->db->query("SELECT * FROM `products` WHERE `id` = '%d'", $productId);
        if ($this->db->num_rows() == 0) {
            return null;
        }
        return $this->db->getdata();
    }

    /**
     * Check if product requires email validation
     *
     * @param int $productId
     * @return bool
     */
    public function requiresEmailValidation($productId)
    {
        $config = $this->getProductConfig($productId);
        return $config && isset($config['requires_email_validation']) && $config['requires_email_validation'] == 1;
    }

    /**
     * Validate email for a product
     *
     * @param int $productId
     * @param string $email
     * @param array $context Optional context data (user_id, invoice_id, order_id)
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateEmail($productId, $email, $context = [])
    {
        $startTime = microtime(true);
        $config = $this->getProductConfig($productId);
        $logData = [
            'product_id' => $productId,
            'user_id' => $context['user_id'] ?? 0,
            'invoice_id' => $context['invoice_id'] ?? 0,
            'order_id' => $context['order_id'] ?? 0,
            'api_method' => 'GET'
        ];

        if (!$config) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Product not found.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('email_validation', $logData);
            return ['success' => false, 'message' => 'Product not found.'];
        }

        if (empty($config['requires_email_validation'])) {
            return ['success' => true, 'message' => 'Email validation not required.'];
        }

        if (empty($config['email_validation_api_url'] ?? '')) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Email validation API not configured.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('email_validation', $logData);
            return ['success' => false, 'message' => 'Email validation API not configured.'];
        }

        // Build API parameters
        $params = $this->parseJsonSafe($config['email_validation_api_params'] ?? '', []);
        $data = [
            'email' => $email,
            'api_key' => $config['activation_api_key'] ?? ''
        ];
        $params = $this->replacePlaceholders($params, $data);
        $logData['request_params'] = $params;

        // Build URL with parameters
        $url = $config['email_validation_api_url'] ?? '';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $logData['api_url'] = $url;

        // Call API
        $response = $this->callApi($url, 'GET');

        if ($response['success'] === false) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Unable to contact validation API. Network error or timeout.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('email_validation', $logData);
            return ['success' => false, 'message' => 'Unable to contact validation API.'];
        }

        $logData['response_raw'] = $response['data'];
        $responseData = json_decode($response['data'], true);
        $logData['response_parsed'] = $responseData;

        $successKey = $config['email_validation_success_key'] ?? 'status';
        $successValue = $config['email_validation_success_value'] ?? 'success';

        // Check response for success
        if (isset($responseData[$successKey])) {
            $actualValue = (string) $responseData[$successKey];
            if ($actualValue == $successValue) {
                $logData['status'] = 'success';
                $logData['execution_time'] = microtime(true) - $startTime;
                $this->logApiCall('email_validation', $logData);
                return ['success' => true, 'message' => 'Email validated successfully.'];
            } else {
                $logData['status'] = 'failed';
                $logData['error_message'] = 'Email is already in use.';
                $logData['execution_time'] = microtime(true) - $startTime;
                $this->logApiCall('email_validation', $logData);
                return [
                    'success' => false,
                    'message' => 'This email is already in use. Please use another email or contact support.'
                ];
            }
        }

        $logData['status'] = 'error';
        $logData['error_message'] = 'Invalid response from validation API - missing success key.';
        $logData['execution_time'] = microtime(true) - $startTime;
        $this->logApiCall('email_validation', $logData);
        return ['success' => false, 'message' => 'Invalid response from validation API.'];
    }

    /**
     * Activate a product
     *
     * @param int $productId
     * @param array $invoiceData Invoice data including require_data, activation_email
     * @param array $userData User information
     * @return array ['success' => bool, 'instructions' => string, 'auto_approve' => bool, 'message' => string]
     */
    public function activateProduct($productId, $invoiceData, $userData)
    {
        $config = $this->getProductConfig($productId);

        if (!$config) {
            return [
                'success' => false,
                'instructions' => '',
                'auto_approve' => false,
                'message' => 'Product not found.'
            ];
        }

        $activationType = $config['activation_type'] ?? 'manual';
        $autoApprove = ($config['auto_approve'] ?? 0) == 1;

        // Handle different activation types
        switch ($activationType) {
            case 'none':
            case 'instant':
                // Instant activation - no API call needed
                return [
                    'success' => true,
                    'instructions' => $config['product'] ?? '',
                    'auto_approve' => true,
                    'message' => 'Product activated successfully.'
                ];

            case 'manual':
                // Manual activation - admin needs to approve
                return [
                    'success' => true,
                    'instructions' => '',
                    'auto_approve' => false,
                    'message' => 'Product pending manual activation.'
                ];

            case 'api':
                // API-based activation
                return $this->processApiActivation($config, $invoiceData, $userData);

            default:
                return [
                    'success' => false,
                    'instructions' => '',
                    'auto_approve' => false,
                    'message' => 'Unknown activation type.'
                ];
        }
    }

    /**
     * Process API-based product activation
     *
     * @param array $config Product configuration
     * @param array $invoiceData Invoice data
     * @param array $userData User data
     * @return array
     */
    private function processApiActivation($config, $invoiceData, $userData)
    {
        $startTime = microtime(true);
        $logData = [
            'product_id' => $config['id'] ?? 0,
            'user_id' => $userData['id'] ?? 0,
            'invoice_id' => $invoiceData['invoice_id'] ?? 0,
            'order_id' => $invoiceData['order_id'] ?? 0,
            'api_method' => $config['activation_api_method'] ?? 'GET'
        ];

        if (empty($config['activation_api_url'])) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Activation API not configured.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('activation', $logData);

            return [
                'success' => false,
                'instructions' => '',
                'auto_approve' => false,
                'message' => 'Activation API not configured.'
            ];
        }

        // Prepare data for placeholder replacement
        $requireData = $invoiceData['require_data'] ?? [];
        $data = array_merge([
            'api_key' => $config['activation_api_key'] ?? '',
            'activation_email' => $invoiceData['activation_email'] ?? '',
            'user_email' => $userData['email'] ?? '',
            'user_id' => $userData['id'] ?? '',
            'activation_date' => date('d-m-Y'),
            'timestamp' => time()
        ], $requireData);

        // Build API parameters
        $params = $this->parseJsonSafe($config['activation_api_params'], []);
        $params = $this->replacePlaceholders($params, $data);
        $logData['request_params'] = $params;

        // Build URL
        $url = $config['activation_api_url'];
        $method = $config['activation_api_method'] ?? 'GET';

        if ($method == 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
            $params = [];
        }
        $logData['api_url'] = $url;

        // Call API
        $response = $this->callApi($url, $method, $params);

        if ($response['success'] === false) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Unable to contact activation API. Network error or timeout.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('activation', $logData);

            return [
                'success' => false,
                'instructions' => '',
                'auto_approve' => false,
                'message' => 'Unable to contact activation API. Please try again later or contact administrator.'
            ];
        }

        $logData['response_raw'] = $response['data'];

        // Parse response
        $responseData = json_decode($response['data'], true);
        $logData['response_parsed'] = $responseData;

        // Check if JSON parsing failed
        if ($responseData === null && !empty($response['data'])) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Invalid JSON response from API.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('activation', $logData);

            return [
                'success' => false,
                'instructions' => '',
                'auto_approve' => false,
                'message' => 'Invalid response from server. Please contact administrator.'
            ];
        }

        // Check for error response from API (common patterns)
        if (is_array($responseData)) {
            $apiStatus = $responseData['status'] ?? $responseData['Status'] ?? null;
            $apiError = $responseData['message'] ?? $responseData['error'] ?? $responseData['msg'] ?? null;

            if ($apiStatus === 'failed' || $apiStatus === 'error' || $apiStatus === 'Failed' || $apiStatus === 'Error') {
                $errorMsg = $apiError ?? 'API returned an error status.';
                $logData['status'] = 'failed';
                $logData['error_message'] = $errorMsg;
                $logData['execution_time'] = microtime(true) - $startTime;
                $this->logApiCall('activation', $logData);

                return [
                    'success' => false,
                    'instructions' => '',
                    'auto_approve' => false,
                    'message' => 'Activation failed: ' . $errorMsg . ' Please contact administrator.'
                ];
            }
        }

        $responseMapping = $this->parseJsonSafe($config['activation_response_mapping'], []);

        // Extract values from response
        $extractedData = $this->extractFromResponse($responseData, $responseMapping, $response['data']);
        $logData['extracted_data'] = $extractedData;

        // Validate that required data was extracted
        if (!empty($responseMapping)) {
            $validation = $this->validateExtractedData($responseMapping, $extractedData);

            if (!$validation['valid']) {
                $missingFields = implode(', ', $validation['missing']);
                $logData['status'] = 'failed';
                $logData['error_message'] = 'Missing required data in API response: ' . $missingFields;
                $logData['execution_time'] = microtime(true) - $startTime;
                $this->logApiCall('activation', $logData);

                return [
                    'success' => false,
                    'instructions' => '',
                    'auto_approve' => false,
                    'message' => 'Invalid response from server. Missing required data (' . $missingFields . '). Please contact administrator.'
                ];
            }
        }

        // Merge all data for instruction template
        $allData = array_merge($data, $extractedData);

        // Build instructions from template
        $instructions = $this->buildInstructions($config['activation_instructions_template'] ?? '', $allData);
        $logData['instructions_generated'] = $instructions;

        // Check for unresolved placeholders in instructions
        $unresolvedPlaceholders = $this->getUnresolvedPlaceholders($instructions);
        if (!empty($unresolvedPlaceholders)) {
            $placeholderList = implode(', ', $unresolvedPlaceholders);
            $logData['status'] = 'failed';
            $logData['error_message'] = 'Unresolved placeholders in instructions: ' . $placeholderList;
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('activation', $logData);

            return [
                'success' => false,
                'instructions' => '',
                'auto_approve' => false,
                'message' => 'Activation failed: Unable to generate complete instructions. Missing data: ' . $placeholderList . '. Please contact administrator.'
            ];
        }

        // All validations passed - log success
        $logData['status'] = 'success';
        $logData['execution_time'] = microtime(true) - $startTime;
        $this->logApiCall('activation', $logData);

        return [
            'success' => true,
            'instructions' => $instructions,
            'auto_approve' => ($config['auto_approve'] ?? 0) == 1,
            'message' => 'Product activated successfully.',
            'api_response' => $responseData
        ];
    }

    /**
     * Process product renewal
     *
     * @param int $productId
     * @param int $invoiceId Original invoice ID
     * @param int $planId Renewal plan ID
     * @param array $invoiceData Invoice data
     * @param array $userData User data
     * @param string $paymentType Payment type (acc_funds, admin, etc.)
     * @return array
     */
    public function renewProduct($productId, $invoiceId, $planId, $invoiceData, $userData, $paymentType = 'acc_funds')
    {
        $startTime = microtime(true);
        $config = $this->getProductConfig($productId);
        $logData = [
            'product_id' => $productId,
            'user_id' => $userData['id'] ?? 0,
            'invoice_id' => $invoiceId,
            'order_id' => $invoiceData['order_id'] ?? 0,
            'api_method' => 'GET'
        ];

        if (!$config) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Product not found.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);
            return ['success' => false, 'message' => 'Product not found.'];
        }

        if (empty($config['is_renewable'])) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'This product does not support renewal.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);
            return ['success' => false, 'message' => 'This product does not support renewal.'];
        }

        if (empty($config['renewal_api_url'] ?? '')) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Renewal API not configured.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);
            return ['success' => false, 'message' => 'Renewal API not configured.'];
        }

        // Get renewal plan
        $plans = $this->parseJsonSafe($config['renewal_plans'] ?? '', []);
        $selectedPlan = null;

        foreach ($plans as $plan) {
            if ($plan['id'] == $planId) {
                $selectedPlan = $plan;
                break;
            }
        }

        if (!$selectedPlan) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Invalid renewal plan selected.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);
            return ['success' => false, 'message' => 'Invalid renewal plan selected.'];
        }

        // Prepare data for placeholder replacement
        $data = [
            'api_key' => $config['activation_api_key'] ?? '',
            'activation_email' => $invoiceData['activation_email'] ?? '',
            'user_email' => $userData['email'] ?? '',
            'user_id' => $userData['id'] ?? '',
            'plan_id' => $planId,
            'plan_price' => $selectedPlan['price'],
            'plan_name' => $selectedPlan['name'],
            'plan_duration' => $selectedPlan['duration_days'],
            'payment_type' => $paymentType,
            'payment_info' => urlencode($paymentType . ' - ' . time()),
            'invoice_id' => $invoiceId,
            'timestamp' => time()
        ];

        // Build API parameters
        $params = $this->parseJsonSafe($config['renewal_api_params'] ?? '', []);
        $params = $this->replacePlaceholders($params, $data);
        $logData['request_params'] = $params;

        // Build URL
        $url = $config['renewal_api_url'] ?? '';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $logData['api_url'] = $url;

        // Call API
        $response = $this->callApi($url, 'GET');

        if ($response['success'] === false) {
            $logData['status'] = 'error';
            $logData['error_message'] = 'Unable to contact renewal API. Network error or timeout.';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);
            return ['success' => false, 'message' => 'Unable to contact renewal API. Please try again later or contact administrator.'];
        }

        $logData['response_raw'] = $response['data'];
        $responseData = json_decode($response['data'], true);
        $logData['response_parsed'] = $responseData;

        // Check if renewal was successful
        if (isset($responseData['status']) && ($responseData['status'] == 'success' || $responseData['status'] == 'Success')) {
            $logData['status'] = 'success';
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);

            return [
                'success' => true,
                'message' => 'Product renewed successfully.',
                'renewal_date' => $responseData['renewal_date'] ?? null,
                'plan' => $selectedPlan,
                'api_response' => $responseData
            ];
        } else {
            $errorMsg = $responseData['message'] ?? $responseData['error'] ?? 'Renewal failed. Please try again.';
            $logData['status'] = 'failed';
            $logData['error_message'] = $errorMsg;
            $logData['execution_time'] = microtime(true) - $startTime;
            $this->logApiCall('renewal', $logData);
            return ['success' => false, 'message' => $errorMsg . ' Please contact administrator.'];
        }
    }

    /**
     * Get renewal plans for a product
     *
     * @param int $productId
     * @return array
     */
    public function getRenewalPlans($productId)
    {
        $config = $this->getProductConfig($productId);

        if (!$config || empty($config['is_renewable'])) {
            return [];
        }

        return $this->parseJsonSafe($config['renewal_plans'] ?? '', []);
    }

    /**
     * Check if product is renewable
     *
     * @param int $productId
     * @return bool
     */
    public function isRenewable($productId)
    {
        $config = $this->getProductConfig($productId);
        return $config && !empty($config['is_renewable']);
    }

    /**
     * Check if product should auto-approve
     *
     * @param int $productId
     * @return bool
     */
    public function shouldAutoApprove($productId)
    {
        $config = $this->getProductConfig($productId);
        return $config && !empty($config['auto_approve']);
    }

    /**
     * Check if product is free
     *
     * @param int $productId
     * @return bool
     */
    public function isFreeProduct($productId)
    {
        $config = $this->getProductConfig($productId);
        return $config && !empty($config['is_free']);
    }

    /**
     * Get activation type
     *
     * @param int $productId
     * @return string
     */
    public function getActivationType($productId)
    {
        $config = $this->getProductConfig($productId);
        return $config['activation_type'] ?? 'manual';
    }

    /**
     * Build instructions from template
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function buildInstructions($template, $data)
    {
        if (empty($template)) {
            return '';
        }

        $instructions = $template;

        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $instructions = str_replace('{' . $key . '}', $value, $instructions);
            }
        }

        return $instructions;
    }

    /**
     * Replace placeholders in array values
     *
     * @param array $params
     * @param array $data
     * @return array
     */
    private function replacePlaceholders($params, $data)
    {
        $result = [];

        foreach ($params as $key => $value) {
            if (is_string($value)) {
                foreach ($data as $dataKey => $dataValue) {
                    if (is_string($dataValue) || is_numeric($dataValue)) {
                        $value = str_replace('{' . $dataKey . '}', $dataValue, $value);
                    }
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Extract values from API response based on mapping
     *
     * @param mixed $response
     * @param array $mapping
     * @param string $rawResponse
     * @return array
     */
    private function extractFromResponse($response, $mapping, $rawResponse = '')
    {
        $extracted = [];

        foreach ($mapping as $varName => $path) {
            if ($path === '_raw_response') {
                $extracted[$varName] = $rawResponse;
                continue;
            }

            $value = $this->getNestedValue($response, $path);
            if ($value !== null) {
                $extracted[$varName] = $value;
            }
        }

        return $extracted;
    }

    /**
     * Get nested value from array using dot notation
     *
     * @param mixed $data
     * @param string $path e.g., "data.username" or "result.user.name"
     * @return mixed
     */
    private function getNestedValue($data, $path)
    {
        if (!is_array($data)) {
            return null;
        }

        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Call external API
     *
     * @param string $url
     * @param string $method
     * @param array $postData
     * @param int $timeout
     * @return array ['success' => bool, 'data' => string]
     */
    private function callApi($url, $method = 'GET', $postData = [], $timeout = 10)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'method' => $method,
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $method == 'POST' ? http_build_query($postData) : ''
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return ['success' => false, 'data' => ''];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Safely parse JSON string
     *
     * @param string $json
     * @param mixed $default
     * @return mixed
     */
    private function parseJsonSafe($json, $default = [])
    {
        if (empty($json)) {
            return $default;
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        return $decoded;
    }

    /**
     * Get renewal cost for a plan
     *
     * @param int $productId
     * @param int $planId
     * @return float
     */
    public function getRenewalCost($productId, $planId)
    {
        $plans = $this->getRenewalPlans($productId);

        foreach ($plans as $plan) {
            if ($plan['id'] == $planId) {
                return $plan['price'];
            }
        }

        return 0;
    }

    /**
     * Validate product configuration
     *
     * @param int $productId
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateConfiguration($productId)
    {
        $config = $this->getProductConfig($productId);
        $errors = [];

        if (!$config) {
            return ['valid' => false, 'errors' => ['Product not found.']];
        }

        $activationType = $config['activation_type'] ?? 'manual';

        if ($activationType === 'api') {
            if (empty($config['activation_api_url'])) {
                $errors[] = 'Activation API URL is required for API activation type.';
            }
            if (empty($config['activation_api_key'])) {
                $errors[] = 'API Key is required for API activation type.';
            }
        }

        if (!empty($config['requires_email_validation'])) {
            if (empty($config['email_validation_api_url'] ?? '')) {
                $errors[] = 'Email validation API URL is required.';
            }
            if (empty($config['email_validation_success_key'] ?? '')) {
                $errors[] = 'Email validation success key is required.';
            }
        }

        if (!empty($config['is_renewable'])) {
            if (empty($config['renewal_api_url'] ?? '')) {
                $errors[] = 'Renewal API URL is required for renewable products.';
            }
            $plans = $this->parseJsonSafe($config['renewal_plans'] ?? '', []);
            if (empty($plans)) {
                $errors[] = 'At least one renewal plan is required.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}