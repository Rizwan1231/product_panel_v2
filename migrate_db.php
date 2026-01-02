<?php
include "init.php";

$queries = [
    "ALTER TABLE `products` ADD COLUMN `is_api` INT DEFAULT 0",
    "ALTER TABLE `products` ADD COLUMN `api_url` TEXT",
    "ALTER TABLE `products` ADD COLUMN `api_method` VARCHAR(10) DEFAULT 'GET'",
    "ALTER TABLE `products` ADD COLUMN `api_params` TEXT",
    "ALTER TABLE `products` ADD COLUMN `response_type` VARCHAR(10) DEFAULT 'JSON'",
    "ALTER TABLE `products` ADD COLUMN `response_map` TEXT",
    "ALTER TABLE `products` ADD COLUMN `activation_text` TEXT"
];

foreach ($queries as $query) {
    if ($db->query($query)) {
        echo "Query executed successfully: " . $query . "\n";
    } else {
        echo "Error executing query: " . $query . " - " . $db->error . "\n";
    }
}

echo "Migration completed.\n";
?>