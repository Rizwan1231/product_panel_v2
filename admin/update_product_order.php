<?php
session_start();
include "../init.php";

if(!isLoggedIn() || (isLoggedIn() && getUserData()['is_admin'] != 1)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if(isset($_POST['order']) && is_array($_POST['order'])) {
    foreach($_POST['order'] as $position => $productId) {
        $productId = intval($productId);
        $position = intval($position) + 1; // Start from 1 instead of 0
        
        $db->query("UPDATE `products` SET `sort` = '%d' WHERE `id` = '%d'", $position, $productId);
    }
    
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);