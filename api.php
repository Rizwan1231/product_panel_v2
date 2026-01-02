<?php
include "init.php";
$key = '';

// /api.php?action=extend_date&access_key=&activation_email=rizwanlucky482@gmail.com&date=1737473734

function send_json_response($status, $message) {
    echo json_encode(["status" => $status, "message" => $message]);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action === 'extend_date') {
    $activation_email = isset($_GET['activation_email']) ? $_GET['activation_email'] : null;
	$access_key = isset($_GET['access_key']) ? $_GET['access_key'] : null;
    $renewal_date = isset($_GET['date']) ? $_GET['date'] : null;
	
	if($access_key != $key) {
		send_json_response("failed", "Invalid access key provided.");
	}

    if (!$activation_email || !$access_key || $renewal_date === null) {
        send_json_response("failed", "Missing required parameters (activation_email, product_id, date, access_key).");
    }

    $db->query("SELECT * FROM `invoices` WHERE `activation_email` = '%s'", $activation_email);
    $num_invoices = $db->num_rows();

    if ($num_invoices === 0) {
        send_json_response("failed", "Invalid data provided (no invoices found for this email).");
    } else {
        $invoice_data = $db->getall();
        $update_successful = false;

        foreach ($invoice_data as $invoice) {
            if (empty($invoice['products_data'])) {
                continue;
            }

            $products = json_decode($invoice['products_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($products)) {
                 continue;
            }

            foreach ($products as $product_item) {
                if (!isset($product_item['product'])) {
                    continue;
                }

                $encrypted_product = $product_item['product'];
                $decrypted_product_id = decrypt($encrypted_product);


                    $invoice_id_to_update = $invoice['id'];

                    $update_query_result = $db->query(
                        "UPDATE `invoices` SET `renewal_date` = '%s' WHERE `id` = '%d'",
                        $renewal_date,
                        $invoice_id_to_update
                    );
                       send_json_response("success", "Renewal date successfully updated.");
            
            }
        }
        if (!$update_successful) {
            send_json_response("failed", "Product ID not found within the invoices for the specified email.");
        }
    }

} else {
    send_json_response("failed", "Invalid or missing action parameter.");
}

?>