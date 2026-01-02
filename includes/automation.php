<?php

/**
 * Universal Product Automation Adapter
 * Handles metadata-driven execution of product lifecycles.
 */

function executeProductAction($actionType, $product, $user, $invoice, $order = null) {
    global $db;

    // 1. Load Configuration
    if (empty($product['lifecycle_config'])) {
        return ['status' => 'skipped', 'message' => 'No lifecycle config found'];
    }

    $config = json_decode($product['lifecycle_config'], true);
    if (!isset($config[$actionType])) {
        return ['status' => 'skipped', 'message' => "Action $actionType not configured"];
    }

    $actionConfig = $config[$actionType];

    // 2. Prepare Context Variables
    $context = [
        'user' => $user,
        'product' => $product,
        'invoice' => $invoice,
        'order' => $order,
        // Add ENV variables if needed, maybe from settings
    ];

    // Create nested input context for easier access via dot notation (e.g. input.username)
    if (isset($product['require_data']) && is_array($product['require_data'])) {
        $context['input'] = [];
        foreach ($product['require_data'] as $key => $val) {
            $context['input'][$key] = $val;
        }
    }

    // 3. Execution based on Type
    $type = strtoupper($actionConfig['type'] ?? 'REST_API');

    if ($type === 'REST_API') {
        return executeRestApiAction($actionConfig, $context);
    } else {
        return ['status' => 'error', 'message' => "Unknown action type: $type"];
    }
}

function executeRestApiAction($config, $context) {
    // A. Prepare Request
    $endpoint = replaceVariables($config['endpoint'], $context);
    $method = strtoupper($config['method'] ?? 'GET');
    $headers = [];
    if (isset($config['headers']) && is_array($config['headers'])) {
        foreach ($config['headers'] as $key => $val) {
            $headers[] = $key . ': ' . replaceVariables($val, $context);
        }
    }

    $payload = null;
    if (isset($config['payload_template'])) {
        // If payload_template is a string (e.g. raw JSON), replace vars
        if (is_string($config['payload_template'])) {
             $payload = replaceVariables($config['payload_template'], $context);
        } else {
            // If it's an array, recursively replace vars then json_encode
            $payloadData = replaceVariablesRecursive($config['payload_template'], $context);
            $payload = json_encode($payloadData);
        }
    }

    // B. Execute Request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($payload && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    // Timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['status' => 'error', 'message' => "cURL Error: $error"];
    }

    // C. Handle Response
    $responseData = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        // Success
        $mappedData = [];
        if (isset($config['response_mapping']) && is_array($config['response_mapping'])) {
            foreach ($config['response_mapping'] as $localKey => $responsePath) {
                $value = getNestedValue($responseData, $responsePath);
                $mappedData[$localKey] = $value;
            }
        }

        return [
            'status' => 'success',
            'http_code' => $httpCode,
            'data' => $mappedData,
            'raw_response' => $responseData ?? $response
        ];
    } else {
        return [
            'status' => 'error',
            'http_code' => $httpCode,
            'message' => "API returned error code $httpCode",
            'raw_response' => $response
        ];
    }
}

function replaceVariables($template, $context) {
    return preg_replace_callback('/\{([a-zA-Z0-9_\.]+)\}/', function($matches) use ($context) {
        $key = $matches[1];

        // Handle ENV separately if we had a settings loader
        if (strpos($key, 'ENV.') === 0) {
            // Placeholder: Implement ENV lookup
            // return getSetting(substr($key, 4));
            return '';
        }

        return getNestedValue($context, $key) ?? $matches[0];
    }, $template);
}

function replaceVariablesRecursive($data, $context) {
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $data[$k] = replaceVariablesRecursive($v, $context);
        }
        return $data;
    } elseif (is_string($data)) {
        return replaceVariables($data, $context);
    }
    return $data;
}

function getNestedValue($data, $path) {
    $keys = explode('.', $path);
    $current = $data;
    foreach ($keys as $key) {
        if (is_array($current) && isset($current[$key])) {
            $current = $current[$key];
        } elseif (is_object($current) && isset($current->$key)) {
            $current = $current->$key;
        } else {
            return null;
        }
    }
    return is_array($current) ? json_encode($current) : $current;
}
?>