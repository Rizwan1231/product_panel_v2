<?php
include "init.php";

$query = "ALTER TABLE `products` ADD COLUMN `lifecycle_config` LONGTEXT DEFAULT NULL";

if ($db->query($query)) {
    echo "Query executed successfully: " . $query . "\n";
} else {
    echo "Error executing query: " . $query . " - " . $db->error . "\n";
}

echo "Migration completed.\n";
?>