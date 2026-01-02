<?php
/**
 * Database Migration Script for Product Automation System
 *
 * This script adds new columns to the products table for:
 * - API-based activation configuration
 * - Email validation configuration
 * - Renewal/extension configuration
 * - Product behavior settings
 *
 * Run this script once to migrate the database.
 * Access: /admin/migrate_db.php
 */

session_start();
include "../init.php";

// Check admin access
if(!isLoggedIn()) {
    redirect("../login.php");
    exit();
}
if(isLoggedIn()) {
    $admin = getUserData()['is_admin'];
    if($admin != 1) {
        redirect("../user");
        exit();
    }
}

$migrations = [];
$errors = [];
$success = [];

// Function to check if column exists
function columnExists($db, $table, $column) {
    $result = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $db->num_rows() > 0;
}

// Function to add column if not exists
function addColumnIfNotExists($db, $table, $column, $definition, &$success, &$errors) {
    if (!columnExists($db, $table, $column)) {
        try {
            $db->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            $success[] = "Added column '$column' to '$table' table";
            return true;
        } catch (Exception $e) {
            $errors[] = "Failed to add column '$column': " . $e->getMessage();
            return false;
        }
    } else {
        $success[] = "Column '$column' already exists in '$table' table (skipped)";
        return true;
    }
}

// Run migrations when form is submitted
if (isset($_POST['run_migration'])) {

    // ========================================
    // AUTOMATION LOGS TABLE
    // ========================================

    // Check if automation_logs table exists
    $db->query("SHOW TABLES LIKE 'automation_logs'");
    if ($db->num_rows() == 0) {
        $db->query("CREATE TABLE `automation_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `log_type` ENUM('activation', 'email_validation', 'renewal') NOT NULL COMMENT 'Type of automation action',
            `product_id` INT NOT NULL COMMENT 'Product ID',
            `product_name` VARCHAR(255) NULL COMMENT 'Product name at time of log',
            `user_id` INT NULL COMMENT 'User ID',
            `invoice_id` INT NULL COMMENT 'Invoice ID',
            `order_id` INT NULL COMMENT 'Order ID',
            `api_url` TEXT NULL COMMENT 'Full API URL called',
            `api_method` VARCHAR(10) NULL COMMENT 'HTTP method used',
            `request_params` JSON NULL COMMENT 'Request parameters sent',
            `response_raw` TEXT NULL COMMENT 'Raw API response',
            `response_parsed` JSON NULL COMMENT 'Parsed API response',
            `status` ENUM('success', 'failed', 'error') NOT NULL COMMENT 'Result status',
            `error_message` TEXT NULL COMMENT 'Error message if failed',
            `extracted_data` JSON NULL COMMENT 'Data extracted from response',
            `instructions_generated` TEXT NULL COMMENT 'Instructions generated',
            `ip_address` VARCHAR(45) NULL COMMENT 'IP address of requester',
            `user_agent` TEXT NULL COMMENT 'User agent',
            `execution_time` DECIMAL(10,4) NULL COMMENT 'API call execution time in seconds',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Log creation time',
            INDEX `idx_product_id` (`product_id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_invoice_id` (`invoice_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_log_type` (`log_type`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs for product automation API calls'");
        $success[] = "Created 'automation_logs' table";
    } else {
        $success[] = "Table 'automation_logs' already exists (skipped)";
    }

    // ========================================
    // PRODUCTS TABLE MIGRATIONS
    // ========================================

    // Activation Configuration
    addColumnIfNotExists($db, 'products', 'activation_type',
        "ENUM('none', 'instant', 'api', 'manual') DEFAULT 'manual' COMMENT 'Type of activation: none=no activation, instant=immediate access, api=external API, manual=admin approval'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'activation_api_url',
        "TEXT NULL COMMENT 'API endpoint URL for product activation'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'activation_api_method',
        "ENUM('GET', 'POST') DEFAULT 'GET' COMMENT 'HTTP method for activation API'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'activation_api_key',
        "TEXT NULL COMMENT 'API authentication key/secret'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'activation_api_params',
        "JSON NULL COMMENT 'JSON object mapping request parameters with placeholders'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'activation_response_mapping',
        "JSON NULL COMMENT 'JSON object mapping API response fields to instruction variables'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'activation_instructions_template',
        "TEXT NULL COMMENT 'Template for generating instructions with placeholders like {username}, {password}'",
        $success, $errors);

    // Email Validation Configuration
    addColumnIfNotExists($db, 'products', 'requires_email_validation',
        "TINYINT(1) DEFAULT 0 COMMENT 'Whether product requires email validation before activation'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'email_validation_api_url',
        "TEXT NULL COMMENT 'API endpoint for email validation'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'email_validation_api_params',
        "JSON NULL COMMENT 'JSON object for email validation API parameters'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'email_validation_success_key',
        "VARCHAR(100) NULL COMMENT 'Response key to check for success (e.g., isfree, status)'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'email_validation_success_value',
        "VARCHAR(100) NULL COMMENT 'Expected value for success (e.g., 1, success)'",
        $success, $errors);

    // Renewal/Extension Configuration
    addColumnIfNotExists($db, 'products', 'is_renewable',
        "TINYINT(1) DEFAULT 0 COMMENT 'Whether product supports renewal/extension'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'renewal_api_url',
        "TEXT NULL COMMENT 'API endpoint for renewal'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'renewal_api_params',
        "JSON NULL COMMENT 'JSON object for renewal API parameters'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'renewal_plans',
        "JSON NULL COMMENT 'JSON array of renewal plans [{id, name, price, duration_days}]'",
        $success, $errors);

    // Product Behavior
    addColumnIfNotExists($db, 'products', 'is_free',
        "TINYINT(1) DEFAULT 0 COMMENT 'Whether product is free (no payment required)'",
        $success, $errors);

    addColumnIfNotExists($db, 'products', 'auto_approve',
        "TINYINT(1) DEFAULT 0 COMMENT 'Whether to auto-approve order on payment'",
        $success, $errors);

    // ========================================
    // MIGRATE EXISTING HARDCODED PRODUCTS
    // ========================================

    if (isset($_POST['migrate_products'])) {

        // Product 1: License Key Generator
        $product1Config = [
            'activation_type' => 'api',
            'activation_api_url' => 'https://paksat.pk/lkm_api.php',
            'activation_api_method' => 'GET',
            'activation_api_key' => 'xxxxxxxxxxxxx', // Replace with actual key
            'activation_api_params' => json_encode([
                'key' => '{api_key}',
                'license' => '{license_domain}',
                'days' => '9999'
            ]),
            'activation_response_mapping' => json_encode([
                'license_key' => '_raw_response'
            ]),
            'activation_instructions_template' => "Here is your order details:\n\nDomain: {license_domain}\n\nLicense Key: {license_key}\n\nInstallation instructions include license key verification and file download link.\n\nhttps://xtream-masters.com/trail_license.php",
            'requires_email_validation' => 0,
            'is_renewable' => 0,
            'is_free' => 0,
            'auto_approve' => 1
        ];

        $db->query("UPDATE `products` SET
            `activation_type` = '%s',
            `activation_api_url` = '%s',
            `activation_api_method` = '%s',
            `activation_api_key` = '%s',
            `activation_api_params` = '%s',
            `activation_response_mapping` = '%s',
            `activation_instructions_template` = '%s',
            `requires_email_validation` = '%d',
            `is_renewable` = '%d',
            `is_free` = '%d',
            `auto_approve` = '%d'
            WHERE `id` = 1",
            $product1Config['activation_type'],
            $product1Config['activation_api_url'],
            $product1Config['activation_api_method'],
            $product1Config['activation_api_key'],
            $product1Config['activation_api_params'],
            $product1Config['activation_response_mapping'],
            $product1Config['activation_instructions_template'],
            $product1Config['requires_email_validation'],
            $product1Config['is_renewable'],
            $product1Config['is_free'],
            $product1Config['auto_approve']
        );
        $success[] = "Migrated Product 1 (License Key Generator) configuration";

        // Product 6: DRM Panel Account
        $product6Config = [
            'activation_type' => 'api',
            'activation_api_url' => 'https://drm.xtream-masters.com/reg_api.php',
            'activation_api_method' => 'GET',
            'activation_api_key' => 'xxxxxxxxxxxxx', // Replace with actual key
            'activation_api_params' => json_encode([
                'pin' => '{api_key}',
                'email' => '{activation_email}'
            ]),
            'activation_response_mapping' => json_encode([
                'username' => 'username',
                'password' => 'password'
            ]),
            'activation_instructions_template' => "Here is your drm account details:\n\nURL: https://drm.xtream-masters.com/\nUsername: {username}\nPassword: {password}\n\nProduct activation email: {activation_email}\n\nActivation Date: {activation_date}\nLicense duration: 30 Days\n\nYou can extend or renew license now directly from drm panel.\nBest Regards.",
            'requires_email_validation' => 1,
            'email_validation_api_url' => 'https://drm.xtream-masters.com/reg_api.php',
            'email_validation_api_params' => json_encode([
                'pin' => '{api_key}',
                'type' => 'checkemail',
                'email' => '{email}'
            ]),
            'email_validation_success_key' => 'isfree',
            'email_validation_success_value' => '1',
            'is_renewable' => 0,
            'is_free' => 0,
            'auto_approve' => 1
        ];

        $db->query("UPDATE `products` SET
            `activation_type` = '%s',
            `activation_api_url` = '%s',
            `activation_api_method` = '%s',
            `activation_api_key` = '%s',
            `activation_api_params` = '%s',
            `activation_response_mapping` = '%s',
            `activation_instructions_template` = '%s',
            `requires_email_validation` = '%d',
            `email_validation_api_url` = '%s',
            `email_validation_api_params` = '%s',
            `email_validation_success_key` = '%s',
            `email_validation_success_value` = '%s',
            `is_renewable` = '%d',
            `is_free` = '%d',
            `auto_approve` = '%d'
            WHERE `id` = 6",
            $product6Config['activation_type'],
            $product6Config['activation_api_url'],
            $product6Config['activation_api_method'],
            $product6Config['activation_api_key'],
            $product6Config['activation_api_params'],
            $product6Config['activation_response_mapping'],
            $product6Config['activation_instructions_template'],
            $product6Config['requires_email_validation'],
            $product6Config['email_validation_api_url'],
            $product6Config['email_validation_api_params'],
            $product6Config['email_validation_success_key'],
            $product6Config['email_validation_success_value'],
            $product6Config['is_renewable'],
            $product6Config['is_free'],
            $product6Config['auto_approve']
        );
        $success[] = "Migrated Product 6 (DRM Panel Account) configuration";

        // Product 7: IPTV CMS Panel
        $product7Config = [
            'activation_type' => 'api',
            'activation_api_url' => 'https://iptv-admin.xtream-masters.com/install_api.php',
            'activation_api_method' => 'GET',
            'activation_api_key' => 'xxxxxxxxxxxxx', // Replace with actual key
            'activation_api_params' => json_encode([
                'action' => 'add_user',
                'reg_key' => '{api_key}',
                'email' => '{activation_email}'
            ]),
            'activation_response_mapping' => json_encode([
                'username' => 'data.username',
                'password' => 'data.password',
                'unique_id' => 'data.unique_id'
            ]),
            'activation_instructions_template' => "Here is your iptv cms master account details:\n\nURL: https://iptv-admin.xtream-masters.com/login.php\nUsername: {username}\nPassword: {password}\nLogin Key: {unique_id}\n\nActivation Date: {activation_date}\nLicense duration: 30 Days\n\nProduct activation email: {activation_email}\n\nPlease login into your account and add main server after adding main server your license duration will be started.\n\nYou can extend or renew license now directly from iptv cms panel.\nBest Regards.",
            'requires_email_validation' => 1,
            'email_validation_api_url' => 'https://iptv-admin.xtream-masters.com/install_api.php',
            'email_validation_api_params' => json_encode([
                'action' => 'check_email',
                'reg_key' => '{api_key}',
                'email' => '{email}'
            ]),
            'email_validation_success_key' => 'status',
            'email_validation_success_value' => 'success',
            'is_renewable' => 1,
            'renewal_api_url' => 'https://iptv-admin.xtream-masters.com/install_api.php',
            'renewal_api_params' => json_encode([
                'action' => 'extend',
                'reg_key' => '{api_key}',
                'extend_email' => '{activation_email}',
                'plan' => '{plan_id}',
                'amount' => '{plan_price}',
                'user_email' => '{user_email}',
                'payment_info' => '{payment_info}',
                'payment_type' => '{payment_type}',
                'invoice_name' => 'XM IPTV CMS License Extend'
            ]),
            'renewal_plans' => json_encode([
                ['id' => 1, 'name' => '1 Month', 'price' => 40, 'duration_days' => 30],
                ['id' => 5, 'name' => '3 Months', 'price' => 120, 'duration_days' => 90],
                ['id' => 2, 'name' => '6 Months', 'price' => 220, 'duration_days' => 180],
                ['id' => 3, 'name' => '12 Months', 'price' => 400, 'duration_days' => 365]
            ]),
            'is_free' => 0,
            'auto_approve' => 1
        ];

        $db->query("UPDATE `products` SET
            `activation_type` = '%s',
            `activation_api_url` = '%s',
            `activation_api_method` = '%s',
            `activation_api_key` = '%s',
            `activation_api_params` = '%s',
            `activation_response_mapping` = '%s',
            `activation_instructions_template` = '%s',
            `requires_email_validation` = '%d',
            `email_validation_api_url` = '%s',
            `email_validation_api_params` = '%s',
            `email_validation_success_key` = '%s',
            `email_validation_success_value` = '%s',
            `is_renewable` = '%d',
            `renewal_api_url` = '%s',
            `renewal_api_params` = '%s',
            `renewal_plans` = '%s',
            `is_free` = '%d',
            `auto_approve` = '%d'
            WHERE `id` = 7",
            $product7Config['activation_type'],
            $product7Config['activation_api_url'],
            $product7Config['activation_api_method'],
            $product7Config['activation_api_key'],
            $product7Config['activation_api_params'],
            $product7Config['activation_response_mapping'],
            $product7Config['activation_instructions_template'],
            $product7Config['requires_email_validation'],
            $product7Config['email_validation_api_url'],
            $product7Config['email_validation_api_params'],
            $product7Config['email_validation_success_key'],
            $product7Config['email_validation_success_value'],
            $product7Config['is_renewable'],
            $product7Config['renewal_api_url'],
            $product7Config['renewal_api_params'],
            $product7Config['renewal_plans'],
            $product7Config['is_free'],
            $product7Config['auto_approve']
        );
        $success[] = "Migrated Product 7 (IPTV CMS Panel) configuration";

        // Set default values for other products
        $db->query("UPDATE `products` SET
            `activation_type` = 'manual',
            `auto_approve` = 0,
            `is_free` = 0,
            `is_renewable` = 0,
            `requires_email_validation` = 0
            WHERE `id` NOT IN (1, 6, 7) AND `activation_type` IS NULL");
        $success[] = "Set default configuration for other products";
    }
}

include "header.php";
?>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-database"></i> Database Migration</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item">admin</div>
                <div class="breadcrumb-item">migration</div>
            </div>
        </div>
    </section>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Product Automation System Migration</h4>
                </div>
                <div class="card-body">

                    <?php if (!empty($success) || !empty($errors)): ?>
                        <!-- Migration Results -->
                        <div class="migration-results mb-4">
                            <?php if (!empty($success)): ?>
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle"></i> Migration Successful</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($success as $msg): ?>
                                            <li><?= htmlspecialchars($msg); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-exclamation-circle"></i> Errors Occurred</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $msg): ?>
                                            <li><?= htmlspecialchars($msg); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> About This Migration</h5>
                        <p>This migration will add the following new columns to the <code>products</code> table:</p>
                        <ul>
                            <li><strong>Activation Settings:</strong> activation_type, activation_api_url, activation_api_method, activation_api_key, activation_api_params, activation_response_mapping, activation_instructions_template</li>
                            <li><strong>Email Validation:</strong> requires_email_validation, email_validation_api_url, email_validation_api_params, email_validation_success_key, email_validation_success_value</li>
                            <li><strong>Renewal Settings:</strong> is_renewable, renewal_api_url, renewal_api_params, renewal_plans</li>
                            <li><strong>Behavior Settings:</strong> is_free, auto_approve</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Important Notes</h5>
                        <ul class="mb-0">
                            <li>This migration is <strong>safe to run multiple times</strong> - it will skip columns that already exist.</li>
                            <li>Make sure to <strong>backup your database</strong> before running migrations.</li>
                            <li>After migration, update the API keys in product settings with your actual keys.</li>
                        </ul>
                    </div>

                    <form method="post" action="">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="migrate_products" name="migrate_products" value="1" checked>
                                <label class="custom-control-label" for="migrate_products">
                                    Also migrate existing hardcoded product configurations (Products 1, 6, 7)
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                            <i class="fas fa-play"></i> Run Migration
                        </button>

                        <a href="products.php" class="btn btn-secondary btn-lg ml-2">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </form>

                </div>
            </div>

            <!-- Current Database Status -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4>Current Products Table Structure</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $requiredColumns = [
                                    'activation_type' => 'Activation Type',
                                    'activation_api_url' => 'Activation API URL',
                                    'activation_api_method' => 'API Method',
                                    'activation_api_key' => 'API Key',
                                    'activation_api_params' => 'API Parameters',
                                    'activation_response_mapping' => 'Response Mapping',
                                    'activation_instructions_template' => 'Instructions Template',
                                    'requires_email_validation' => 'Email Validation Required',
                                    'email_validation_api_url' => 'Email Validation API',
                                    'email_validation_api_params' => 'Email Validation Params',
                                    'email_validation_success_key' => 'Validation Success Key',
                                    'email_validation_success_value' => 'Validation Success Value',
                                    'is_renewable' => 'Is Renewable',
                                    'renewal_api_url' => 'Renewal API URL',
                                    'renewal_api_params' => 'Renewal API Params',
                                    'renewal_plans' => 'Renewal Plans',
                                    'is_free' => 'Is Free',
                                    'auto_approve' => 'Auto Approve'
                                ];

                                foreach ($requiredColumns as $column => $label):
                                    $exists = columnExists($db, 'products', $column);
                                ?>
                                <tr>
                                    <td><code><?= $column; ?></code></td>
                                    <td><?= $label; ?></td>
                                    <td>
                                        <?php if ($exists): ?>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Exists</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fas fa-times"></i> Missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>