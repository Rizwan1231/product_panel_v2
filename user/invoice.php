<?php
session_start();
include "../init.php";
include "../includes/ProductActivationHandler.php";

// Initialize the activation handler
$activationHandler = new ProductActivationHandler($db);

if(isset($_GET['token']) && !empty($_GET['token']))
{
  $token = trim($_GET['token']);
  if(!decrypt($token))
  {
   session_destroy();
   redirect("../index.php");
   exit();
  }
  $token = decrypt($token);
  $udata = explode('|', $token);
  $userId = $udata[0];
  $userEmail = $udata[1];
  $userPassword = $udata[2];
  $check = loginCheck($userEmail, $userPassword);
  if(!$check || empty($check))
  {
   session_destroy();
   redirect("../index.php");
   exit();
   }
   else
   {
     $_SESSION['user_id'] = $userId;
     $_SESSION['email'] = $userEmail;
     $_SESSION['password'] = $userPassword;
     redirect("invoice.php?invoice_id=" . $_GET['invoice_id']);
     exit();
   }
  exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'logout')
{
 session_destroy();
 redirect("../index.php");
 exit();
}
if(!isLoggedIn())
{
  redirect("../index.php");
  exit();
}

$invoice_id = trim($_GET['invoice_id']);
$invoice_id = decrypt($invoice_id);
if(!$invoice_id || !intval($invoice_id))
{
  redirect("index.php?error=no_invoice");
  exit();
}
$uid = getUserData()['id'];
$db->query("SELECT * FROM `invoices` WHERE `user_id` = '%d' AND `id` = '%d'", $uid, $invoice_id);
$invoice_data = $db->getdata();
$innum = $db->num_rows();
if($innum == 0)
{
  redirect("index.php?error=no_invoice");
  exit();
}
 $products = json_decode($invoice_data['products_data'], true);
 $requireFrist = 0;
 $rqs = array();

 $ps = array( 1 ); // products id that need to be first submit require data
 if($products && count($products) == 1) {
 $productidd = decrypt($products[0]['product']);
 $rqdata = $products[0]['require_data'] ?? null;

 if(in_array($productidd, $ps) && (!isset($rqdata) || empty($rqdata)) ) {
 $requireFrist = 1;
 $db->query("SELECT `require_data` FROM `products` WHERE `id` = '%d'", $productidd);
 $num = $db->num_rows();
 if($num == 0)
 {
  echo 0;
  exit();
 }
 $pdatas = $db->getdata();
 $rdatas = $pdatas['require_data'];
 $rdatas = explode('&&', $rdatas);
 $rqs = array();
 foreach($rdatas as $rdata)
 {
  $rdata = explode('<|>', $rdata);
 if(!empty($rdata[1]))
 {
 $rqs[] = array( "label" => $rdata[1], "id" => $rdata[0], "type" => $rdata[2] );
 }
 }

 }

 }
 $rqs = json_encode($rqs);


if(isset($_POST['p_email']) && !empty($_POST['email'])) {
    $email = trim($_POST['email']);
    $pid = decrypt($_POST['p_info']);

    // Validate product ID
    if (!$pid || !is_numeric($pid)) {
        echo json_encode(array("status" => "failed", "message" => "Invalid product information."));
        exit;
    }

    $pid = intval($pid);

    // Check if product requires email validation using the centralized handler
    if (!$activationHandler->requiresEmailValidation($pid)) {
        // Product doesn't require email validation, just update the email
        $emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if(!preg_match($emailPattern, $email)) {
            echo json_encode(array("status" => "failed", "message" => "Invalid email format."));
        } else {
            $uid = getUserData()['id'];
            $invoiceid = $invoice_id;
            $db->query("UPDATE `invoices` SET `activation_email` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", $email, $uid, $invoiceid);
            $_SESSION['p_email'] = $email;
            echo json_encode(array("status" => "success", "message" => "Product email successfully updated."));
        }
        exit;
    }

    // Validate email format
    $emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if(!preg_match($emailPattern, $email)) {
        echo json_encode(array("status" => "failed", "message" => "Invalid email format."));
        exit;
    }

    // Use the centralized handler for email validation
    $validationResult = $activationHandler->validateEmail($pid, $email);

    if ($validationResult['success']) {
        $_SESSION['p_email'] = $email;
        $uid = getUserData()['id'];
        $invoiceid = $invoice_id;
        $db->query("UPDATE `invoices` SET `activation_email` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", $email, $uid, $invoiceid);
        echo json_encode(array("status" => "success", "message" => "Product email successfully updated."));
    } else {
        echo json_encode(array("status" => "failed", "message" => $validationResult['message']));
    }
    exit;
}

if(isset($_POST['rdata']) && !empty($_POST['rdata']))
{
 $uid = getUserData()['id'];
 unset($_POST['rdata']);
 $pid = decrypt($_POST['product']);


 unset($_POST['product']);
 $datas = $_POST;
 $invoiceid = $invoice_id;
 $db->query("SELECT `products_data` FROM `invoices` WHERE `id` = '%d'", $invoiceid);
 $num = $db->num_rows();
 $pdatas = $db->getdata()['products_data'];
 $pdatas = json_decode($pdatas, true);
 $ndatas = array();

foreach ($pdatas as $pdata) {
    $product = decrypt($pdata['product']);
    if ($product == $pid) {
     $pdata['require_data'] = $datas;
    }
    $ndatas[] = $pdata;
}


 $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", json_encode($ndatas), $uid, $invoiceid);
 if($db->affected_rows() > 0)
 {
  $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 0, $invoiceid);
 echo json_encode(array("message" => "Successfully Submitted.", "redirect" => "invoice.php?invoice_id=".$_GET['invoice_id']."" ));
 exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("Failed To Submit Detail Please Try Again." ));
    exit();
 }
}



if(isset($_POST['purchase']) && !empty($_POST['purchase']))
{
 $products = json_decode($invoice_data['products_data'], true);
 $pInfos = getPInfos($products);
 $pids = array();
 $pidsCheck = array();
 $ndatas = array();
 $autoApprove = 0;
 $errorMessage = json_encode(array("status" => "error", "message" => "You don't have enough balance in your account." ));
 $isValidDataFound = false;
 $uCredits = getUserData()['credits'];
 
if (isset($invoice_data['extend_data']) && !is_null($invoice_data['extend_data']) && !empty($invoice_data['extend_data']))
{
    $isValidDataFound = true;
    $extend_details = json_decode($invoice_data['extend_data'], true);
    $extend_product_id = decrypt($extend_details['product']);
    $extend_id = $extend_details['extend_id'];

    // Use centralized handler to get renewal cost
    $extend_cost = $activationHandler->getRenewalCost($extend_product_id, $extend_id);

    if ($extend_cost > 0 && $extend_cost > $uCredits) {
        http_response_code(400);
        echo $errorMessage;
        exit();
    }
}else {
  foreach($products as $product)
 {
  $pidsCheck [] = $product['product'];
 }
 $totalcost = totalPrice($pidsCheck);
 $uCredits = getUserData()['credits'];
 if($totalcost && $totalcost > $uCredits)
 {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "You don't have enough balance in your account." ));
    exit();
 }
}


// first checking if extending/renewing product
if (isset($invoice_data['extend_data']) && !is_null($invoice_data['extend_data']) && !empty($invoice_data['extend_data']))
{
    $extend_details = json_decode($invoice_data['extend_data'], true);
    $extend_invoice_id = decrypt($extend_details['invoice_id']);
    $extend_product_id = decrypt($extend_details['product']);
    $extend_id = $extend_details['extend_id'];

    $db->query("SELECT * FROM `invoices` WHERE `user_id` = '%d' AND `id` = '%d'", $uid, $extend_invoice_id);
    $extend_invoice_data = $db->getdata();
    $product_activation_email = $extend_invoice_data['activation_email'];
    $products = json_decode($extend_invoice_data['products_data'], true);

    foreach($products as $product) {
        if(decrypt($product['product']) == $extend_product_id) {
            $instructions = $product['instructions'];
            $insEmail = preg_match('/Product activation email:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $instructions, $matches) ? $matches[1] : '';
            if(!$product_activation_email || empty($product_activation_email)) {
                $db->query("UPDATE `invoices` SET `activation_email` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", $insEmail, $uid, $extend_invoice_id);
                $product_activation_email = $insEmail;
            }
        }
    }

    if(!$product_activation_email || empty($product_activation_email)) {
        echo json_encode(array("status" => "error", "message" => "Product activation email not found please set it first." ));
        exit;
    }

    // Use centralized handler for renewal
    $extend_cost = $activationHandler->getRenewalCost($extend_product_id, $extend_id);

    // Get user data for renewal
    $db->query("SELECT * FROM `users` WHERE `id` = '%d'", $uid);
    $userData = $db->getdata();

    // Prepare invoice data for renewal
    $renewalInvoiceData = [
        'activation_email' => $product_activation_email
    ];

    // Use centralized handler for renewal
    $renewalResult = $activationHandler->renewProduct(
        $extend_product_id,
        $extend_invoice_id,
        $extend_id,
        $renewalInvoiceData,
        $userData,
        'acc_funds'
    );

    if ($renewalResult['success']) {
        $renewal_date = $renewalResult['renewal_date'] ?? null;
        if ($renewal_date) {
            $db->query("UPDATE `invoices` SET `renewal_date` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", $renewal_date, $uid, $extend_invoice_id);
        }

        // Process payment
        saveCreditsLogs($uid, $extend_cost, '-', '#'.$invoice_id. " Invoice Payment Successfull with account fund.");

        // Referral commission
        $refby = getUserData()['ref_by'];
        if(!is_null($refby) && !empty(intval($refby)) && checkUser($refby)) {
            $commission = roundup(commissionCalculate($extend_cost));
            saveCreditsLogs($refby, $commission, '+', "Referral Commission Added.");
            $db->query("UPDATE `users` SET `ref_bonus` = `ref_bonus` + $commission WHERE `id` = '%d'", $refby);
        }

        $db->query("UPDATE `users` SET `credits` = `credits` - $extend_cost WHERE `id` = '%d'", $uid);
        $db->query("INSERT INTO `orders` (`user_id`, `invoice_id`, `status`, `date`) VALUES ('%d', '%d', '%d', '%d')", $uid, $invoice_id, 1, time());
        $db->query("UPDATE `invoices` SET `status` = '%d' WHERE `id` = '%d'", 1, $invoice_id);
        $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 1, $invoice_id);

        echo json_encode(array("status" => "success", "message" => "Payment Successfull.", "redirect" => "products.php" ));
        exit;
    } else {
        echo json_encode(array("status" => "error", "message" => $renewalResult['message'] ));
        exit;
    }
}else {
 // Get user data for activation
 $db->query("SELECT * FROM `users` WHERE `id` = '%d'", $uid);
 $userData = $db->getdata();

 // Track if any activation failed
 $activationFailed = false;
 $activationError = '';

 foreach($products as $product)
 {
  $pids[] = $product['product'];
  $pid = decrypt($product['product']);
  $pActive = 0;

  // Prepare invoice data for the handler
  $invoiceData = [
      'require_data' => $product['require_data'] ?? [],
      'activation_email' => $invoice_data['activation_email'],
      'invoice_id' => $invoice_id,
      'order_id' => 0
  ];

  // Use the centralized handler for product activation
  $activationResult = $activationHandler->activateProduct($pid, $invoiceData, $userData);

  if ($activationResult['success']) {
      if (!empty($activationResult['instructions'])) {
          $product['instructions'] = $activationResult['instructions'];
      }
      if ($activationResult['auto_approve']) {
          $autoApprove = 1;
      }
  } else {
      // Activation failed - check if it's an API-based product that requires activation
      $activationType = $activationHandler->getActivationType($pid);
      if ($activationType === 'api') {
          // Critical failure - API activation failed, don't proceed with payment
          $activationFailed = true;
          $activationError = $activationResult['message'];
          break;
      }
  }

  $ndatas[] = $product;
 }

 // If API activation failed, return error without deducting funds
 if ($activationFailed) {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => $activationError ?: "Product activation failed. Please contact administrator."
    ));
    exit();
 }

 $totalcost = totalPrice($pids);
 $uCredits = getUserData()['credits'];
 if($totalcost && $totalcost > $uCredits)
 {
    http_response_code(400);
    echo json_encode(array("status" => "error", "message" => "You don't have enough balance in your account." ));
    exit();
 }
 else
 {
    $stat = 0;
    foreach($pInfos as $pInfo)
    {
     if($pInfo['is_require_data'] == 0 || empty($pInfo['is_require_data']))
     {
      $stat = 1;
     }
    }
    saveCreditsLogs($uid, $totalcost, '-', '#'.$invoice_id. " Invoice Payment Successfull with account fund.");
    // if user is ref by someone then add some percent of there total spend
    $refby = getUserData()['ref_by'];
    if(!is_null($refby) && !empty(intval($refby)) && checkUser($refby))
    {
      $commission = roundup(commissionCalculate($totalcost));
      saveCreditsLogs($refby, $commission, '+', "Referral Commission Added.");
      $db->query("UPDATE `users` SET `ref_bonus` = `ref_bonus` + $commission WHERE `id` = '%d'", $refby);
    }
    $db->query("UPDATE `users` SET `credits` = `credits` - $totalcost WHERE `id` = '%d'", $uid);
    $db->query("INSERT INTO `orders` (`user_id`, `invoice_id`, `status`, `date`) VALUES ('%d', '%d', '%d', '%d')", $uid, $invoice_id, 1, time());
    $db->query("UPDATE `invoices` SET `status` = '%d' WHERE `id` = '%d'", 1, $invoice_id);

    if($autoApprove == 1) {
     $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 1, $invoice_id);
    }
    $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", json_encode($ndatas), $uid, $invoice_id);

    echo json_encode(array("status" => "success", "message" => "Payment Successfull.", "redirect" => "products.php" ));
    exit();
 }
}
 exit();
}

$products = json_decode($invoice_data['products_data'], true);

include "header.php";
?>
<style>
/* Page Container */
.invoice-container {
    padding: 15px;
    max-width: 900px;
    margin: 0 auto;
}

/* Invoice Header */
.invoice-header-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.invoice-header-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}
.invoice-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
    gap: 15px;
}
.invoice-left {
    flex: 1;
    min-width: 200px;
}
.invoice-number {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.invoice-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.balance-badge {
    background: white;
    color: #667eea;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}

/* Invoice Card */
.invoice-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 20px;
}
.invoice-card-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #4a5568;
    display: flex;
    align-items: center;
    gap: 8px;
}
.invoice-card-header i {
    color: #718096;
}
.invoice-card-body {
    padding: 20px;
}

/* Invoice Table */
.invoice-table {
    width: 100%;
    border-collapse: collapse;
}
.invoice-table thead th {
    background: #f8fafc;
    color: #4a5568;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.3px;
    padding: 10px 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}
.invoice-table tbody tr {
    border-bottom: 1px solid #f0f4f8;
}
.invoice-table tbody td {
    padding: 12px;
    vertical-align: middle;
    font-size: 0.85rem;
}
.product-image {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    object-fit: cover;
}
.price-display {
    font-weight: 700;
    color: #2d3748;
}

/* Mobile Product Cards */
.product-cards {
    display: none;
}
.product-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    border-left: 3px solid #667eea;
}
.product-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.product-card-image {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
}
.product-card-info h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #2d3748;
}
.product-card-info .badge {
    font-size: 0.7rem;
}
.product-card-body {
    border-top: 1px dashed #e2e8f0;
    padding-top: 12px;
}
.product-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
}
.product-card-label {
    font-size: 0.75rem;
    color: #718096;
}
.product-card-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2d3748;
}
.product-card-details {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
}
.product-card-details small {
    display: block;
    font-size: 0.75rem;
    color: #718096;
    margin-bottom: 3px;
}

/* Recharge/Extend Display */
.invoice-type-display {
    text-align: center;
    padding: 30px 15px;
}
.invoice-type-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
    opacity: 0.8;
}
.invoice-type-icon.text-primary { color: #667eea; }
.invoice-type-icon.text-warning { color: #f6ad55; }
.invoice-type-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
}
.invoice-type-amount {
    font-size: 2rem;
    font-weight: 700;
    color: #48bb78;
    margin-bottom: 5px;
}
.invoice-type-date {
    color: #718096;
    font-size: 0.8rem;
}

/* Total Section */
.total-section {
    background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
    border-radius: 10px;
    padding: 15px 20px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #e2e8f0;
}
.total-left {
    flex: 1;
}
.total-label {
    font-size: 0.75rem;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.total-invoice-id {
    font-size: 0.75rem;
    color: #a0aec0;
}
.total-amount {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2d3748;
}

/* Action Buttons */
.action-section {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
.btn-pay {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(72, 187, 120, 0.3);
    color: white;
    text-decoration: none;
}
.btn-submit-data {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-submit-data:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(102, 126, 234, 0.3);
}

/* Support Card */
.support-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.support-card h5 {
    font-size: 0.95rem;
    margin-bottom: 15px;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 8px;
}
.support-items {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.support-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    transition: all 0.2s;
}
.support-item:hover {
    background: #edf2f7;
}
.support-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 1rem;
    flex-shrink: 0;
}
.support-whatsapp { background: rgba(37, 211, 102, 0.1); color: #25d366; }
.support-telegram { background: rgba(0, 136, 204, 0.1); color: #0088cc; }
.support-email { background: rgba(234, 67, 53, 0.1); color: #ea4335; }
.support-text {
    font-size: 0.8rem;
    min-width: 0;
}
.support-text strong {
    display: block;
    margin-bottom: 2px;
    font-size: 0.8rem;
}
.support-text span {
    color: #718096;
    font-size: 0.75rem;
    word-break: break-all;
}

/* Responsive */
@media (max-width: 768px) {
    .invoice-container {
        padding: 10px;
    }
    
    .invoice-header-card {
        padding: 15px;
    }
    .invoice-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .invoice-left {
        width: 100%;
    }
    .invoice-number {
        font-size: 1.3rem;
    }
    .invoice-status {
        font-size: 0.75rem;
        padding: 4px 10px;
    }
    .balance-badge {
        width: 100%;
        justify-content: center;
        padding: 10px 15px;
        font-size: 0.85rem;
    }
    
    .invoice-card-header {
        padding: 12px 15px;
        font-size: 0.9rem;
    }
    .invoice-card-body {
        padding: 15px;
    }
    
    /* Hide table, show cards on mobile */
    .table-responsive {
        display: none;
    }
    .product-cards {
        display: block;
    }
    
    .invoice-type-display {
        padding: 25px 15px;
    }
    .invoice-type-icon {
        font-size: 2rem;
    }
    .invoice-type-title {
        font-size: 1rem;
    }
    .invoice-type-amount {
        font-size: 1.7rem;
    }
    
    .total-section {
        padding: 12px 15px;
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    .total-left {
        width: 100%;
    }
    .total-amount {
        font-size: 1.6rem;
    }
    
    .action-section {
        justify-content: stretch;
    }
    .btn-pay,
    .btn-submit-data {
        flex: 1;
        justify-content: center;
        padding: 12px 20px;
        font-size: 0.9rem;
    }
    
    .support-card {
        padding: 15px;
    }
    .support-card h5 {
        font-size: 0.9rem;
    }
    .support-items {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .support-item {
        padding: 10px;
    }
    .support-icon {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .invoice-number {
        font-size: 1.2rem;
    }
    
    .product-card {
        padding: 12px;
    }
    .product-card-image {
        width: 45px;
        height: 45px;
    }
    .product-card-info h4 {
        font-size: 0.85rem;
    }
    
    .total-amount {
        font-size: 1.4rem;
    }
    
    .btn-pay,
    .btn-submit-data {
        font-size: 0.85rem;
        padding: 10px 15px;
    }
}

/* Modal Improvements */
.modal-cstm-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
.modal-cstm-overlay.show {
    display: flex;
}
.modal-cstm-content {
    background: white;
    border-radius: 12px;
    max-width: 450px;
    width: calc(100% - 30px);
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}
.modal-cstm-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-cstm-header h2 {
    font-size: 1rem;
    margin: 0;
}
.modal-cstm-header .close {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #f0f4f8;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.2rem;
    color: #718096;
}
.modal-cstm-body {
    padding: 20px;
}
.modal-cstm-body label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 8px;
    display: block;
}
.modal-cstm-body input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 15px;
}
.modal-cstm-body input[type="email"]:focus {
    border-color: #667eea;
    outline: none;
}
.modal-cstm-body input[type="submit"] {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-file-invoice-dollar"></i> Invoice Checkout</h1>
        </div>

        <div class="invoice-container">
            <!-- Invoice Header -->
            <div class="invoice-header-card">
                <div class="invoice-info">
                    <div class="invoice-left">
                        <div class="invoice-number">Invoice #<?php echo $invoice_id; ?></div>
                        <div class="invoice-status">
                            <i class="fas fa-<?= $invoice_data['status'] == 0 ? 'clock' : 'check-circle' ?>"></i> 
                            <?= $invoice_data['status'] == 0 ? 'Awaiting Payment' : 'Paid' ?>
                        </div>
                    </div>
                    <div class="balance-badge">
                        <i class="fas fa-wallet"></i> Balance: $<?= number_format(getUserData()['credits'], 2); ?>
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="invoice-card">
                <div class="invoice-card-header">
                    <i class="fas fa-shopping-cart"></i> Invoice Details
                </div>
                <div class="invoice-card-body">
                    <?php
                    // 1. Products Data
                    if (!is_null($invoice_data['products_data']) && !empty($invoice_data['products_data'])) {
                    ?>
                        <!-- Desktop Table -->
                        <div class="table-responsive">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalproducts = array();
                                    foreach ($products as $product) {
                                        $totalproducts[] = $product["product"];
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="../panel-assets/uploads/<?= htmlspecialchars($product['product_image']); ?>" 
                                                 alt="Product" class="product-image">
                                        </td>
                                        <td><?= htmlspecialchars($product['product_name']); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?= htmlspecialchars(productType($product['product'])); ?></span>
                                        </td>
                                        <td class="price-display">$<?= number_format((float)$product['product_price'], 2); ?></td>
                                        <td>
                                            <?php
                                            if (isset($product['require_data']) && is_array($product['require_data']) && count($product['require_data']) > 0) {
                                                foreach ($product['require_data'] as $key => $value) {
                                                    echo '<small>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ': <strong>' . htmlspecialchars($value) . "</strong></small><br>";
                                                }
                                            } else {
                                                echo '<span class="text-muted">—</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Product Cards -->
                        <div class="product-cards">
                            <?php
                            $totalproducts = array();
                            foreach ($products as $product) {
                                $totalproducts[] = $product["product"];
                            ?>
                            <div class="product-card">
                                <div class="product-card-header">
                                    <img src="../panel-assets/uploads/<?= htmlspecialchars($product['product_image']); ?>" 
                                         alt="Product" class="product-card-image">
                                    <div class="product-card-info">
                                        <h4><?= htmlspecialchars($product['product_name']); ?></h4>
                                        <span class="badge badge-info"><?= htmlspecialchars(productType($product['product'])); ?></span>
                                    </div>
                                </div>
                                <div class="product-card-body">
                                    <div class="product-card-row">
                                        <span class="product-card-label">Price</span>
                                        <span class="product-card-value price-display">$<?= number_format((float)$product['product_price'], 2); ?></span>
                                    </div>
                                    <?php if (isset($product['require_data']) && is_array($product['require_data']) && count($product['require_data']) > 0): ?>
                                    <div class="product-card-details">
                                        <?php foreach ($product['require_data'] as $key => $value): ?>
                                        <small><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?>: <strong><?= htmlspecialchars($value); ?></strong></small>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>

                    <?php
                    // 2. Recharge Data
                    } elseif (!is_null($invoice_data['recharge_data']) && !empty($invoice_data['recharge_data'])) {
                        $recharge_details = json_decode($invoice_data['recharge_data'], true);
                        $recharge_amount = isset($recharge_details['ammount']) ? $recharge_details['ammount'] : 0;
                    ?>
                        <div class="invoice-type-display">
                            <div class="invoice-type-icon text-primary">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="invoice-type-title">Account Balance Recharge</div>
                            <div class="invoice-type-amount">$<?= number_format((float)$recharge_amount, 2); ?></div>
                            <div class="invoice-type-date">
                                <i class="far fa-calendar"></i> <?= date('d M Y', $invoice_data['date']); ?>
                            </div>
                        </div>

                    <?php
                    // 3. Extend Data
                    } elseif (!is_null($invoice_data['extend_data']) && !empty($invoice_data['extend_data'])) {
                        $extend_details = json_decode($invoice_data['extend_data'], true);
                        $cost = 0;
                        $extend_duration = "Unknown";
                        
                        if (isset($extend_details['extend_id'])) {
                            switch ($extend_details['extend_id']) {
                                case 1: $cost = 40; $extend_duration = "1 Month"; break;
                                case 2: $cost = 220; $extend_duration = "6 Months"; break;
                                case 3: $cost = 400; $extend_duration = "12 Months"; break;
                                case 5: $cost = 120; $extend_duration = "3 Months"; break;
                            }
                        }
                    ?>
                        <div class="invoice-type-display">
                            <div class="invoice-type-icon text-warning">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="invoice-type-title">License Extension - <?= $extend_duration; ?></div>
                            <div class="invoice-type-amount">$<?= number_format((float)$cost, 2); ?></div>
                            <div class="invoice-type-date">
                                <i class="far fa-calendar"></i> <?= date('d M Y', $invoice_data['date']); ?>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Total Section -->
                    <div class="total-section">
                        <div class="total-left">
                            <div class="total-label">Total Amount Due</div>
                            <div class="total-invoice-id">Invoice #<?= $invoice_id; ?></div>
                        </div>
                        <div class="total-amount">
                            $<?php
                            $totalAmount = 0;
                            if (!empty($totalproducts)) {
                                $totalAmount = totalPrice($totalproducts);
                            } elseif (!empty($invoice_data['recharge_data'])) {
                                $recharge = json_decode($invoice_data['recharge_data'], true);
                                $totalAmount = $recharge['ammount'] ?? 0;
                            } elseif (!empty($invoice_data['extend_data'])) {
                                $extend = json_decode($invoice_data['extend_data'], true);
                                $costs = [1 => 40, 5 => 120, 2 => 220, 3 => 400];
                                $totalAmount = $costs[$extend['extend_id']] ?? 0;
                            }
                            echo number_format($totalAmount, 2);
                            ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-section">
                        <?php if ($activationHandler->requiresEmailValidation($productidd) && empty($invoice_data['activation_email'])): ?>
						<div class="email-notice" style="width: 100%; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px 15px; margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px;">
							<i class="fas fa-info-circle" style="color: #856404; margin-top: 3px;"></i>
							<div style="flex: 1;">
								<strong style="color: #856404; font-size: 0.9rem;">Email Required For Open Account</strong>
								<p style="margin: 5px 0 0; font-size: 0.82rem; color: #856404;">Please set your email first. Your panel login credentials will be sent to this email after payment.</p>
							</div>
						</div>
						<button type="button" onclick="PemailopenModal()" class="btn-submit-data">
							<i class="fas fa-envelope"></i> Set Email to Continue
						</button>
                        <?php elseif($invoice_data['status'] == 0): ?>
                            <?php if($requireFrist == 0): ?>
                                <input type="hidden" name="purchase" value="1">
                                <button type="button" class="btn-pay buybtn">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-submit-data btn-submitdata" data-id="<?= encrypt($productidd); ?>">
                                    <i class="fas fa-edit"></i> Submit Data
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="products.php" class="btn-pay">
                                <i class="fas fa-check-circle"></i> View Orders
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Support Section -->
            <div class="support-card">
                <h5><i class="fas fa-headset"></i> Need Help?</h5>
					<hr>
                    <p><center><b>Working Hours:</b><br> 01 PM - 10 PM Europe (Friday off)</center></p>
                <div class="support-items">
                    <a href="https://wa.me/<?= $rWhatsapp; ?>" target="_blank" class="support-item" style="text-decoration: none;">
                        <div class="support-icon support-whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="support-text">
                            <strong>WhatsApp</strong>
                            <span><?= $rWhatsapp; ?></span>
                        </div>
                    </a>
                    
                    <a href="https://t.me/<?= $rTelegram; ?>" target="_blank" class="support-item" style="text-decoration: none;">
                        <div class="support-icon support-telegram">
                            <i class="fab fa-telegram"></i>
                        </div>
                        <div class="support-text">
                            <strong>Telegram</strong>
                            <span>@<?= $rTelegram; ?></span>
                        </div>
                    </a>
                    
                    <a href="mailto:<?= $rEmail; ?>" class="support-item" style="text-decoration: none;">
                        <div class="support-icon support-email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="support-text">
                            <strong>Email</strong>
                            <span><?= $rEmail; ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<link rel="stylesheet" href="../panel-assets/modal/style.css">
<link rel="stylesheet" href="../panel-assets/css/p_email.css">

<!-- Payment/Data Modal -->
<?php if($requireFrist == 0) { ?>
<div class="modal">
    <div class="modal-content  modal-lg">
        <div class="modal-header">
            <h3 id="mdheader">Choose Payment Method</h3>
            <span class="close-button" title="Close">×</span>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="form-row rqdata">
                    <?php include "pay.php"; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } else { ?>
<div class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="mdheader">Submit Required Data</h3>
            <span class="close-button" title="Close">×</span>
        </div>
        <form action="" method="post" class="ajaxform_with_redirect">
            <div class="card">
                <div class="card-body">
                    <div class="form-row rqdata">
                        <center><img style="width: 50%;" src="../assets/img/loader.gif"></center>
                    </div>
                    <input type="hidden" name="rdata" value="1">
                    <input type="hidden" name="product" id="productt" value="">
                    <div class="btn-publish submit-rdata hide">
                        <button type="submit" class="btn btn-primary col-12 basicbtn">
                            <i class="fa fa-paper-plane"></i> Submit
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<!-- Email Modal -->
<div id="pEmailModal" class="modal-cstm-overlay">
    <div class="modal-cstm-content">
        <div class="modal-cstm-header">
            <h2>Set Panel Account Email</h2>
            <span class="close" onclick="PemailcloseModal()">&times;</span>
        </div>
        <div class="modal-cstm-body">
            <div id="info-alert"></div>
            <form id="emailForm" method="post" action="">
                <label for="email">Your panel account will be created on this email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <input type="hidden" value="1" name="p_email">
                <input type="hidden" value="<?= encrypt($productidd); ?>" name="p_info">
                <input type="submit" value="Update Email" id="updateButton">
            </form>
        </div>
    </div>
</div>

<script>
function PemailopenModal() {
    document.getElementById('pEmailModal').classList.add('show');
}
function PemailcloseModal() {
    document.getElementById('pEmailModal').classList.remove('show');
}
</script>

<br><br>
<?php include "footer.php"; ?>

<script src="../panel-assets/js/p_email.js"></script>
<script>

document.getElementById('emailForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('#updateButton');
    submitButton.disabled = true;
    submitButton.value = 'Wait...';
	document.getElementById('info-alert').innerHTML = '';
	
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitButton.disabled = false;
        if (data.status === 'success') {
            submitButton.value = 'Update';
            document.getElementById('info-alert').innerHTML = `<span class="p-email-alert success" role="alert">${data.message}</span>`;
            location.reload();
        } else if (data.status === 'failed') {
            submitButton.value = 'Update';
            document.getElementById('info-alert').innerHTML = `<span class="p-email-alert failure" role="alert">${data.message}</span>`;
        }
    })
    .catch(error => {
        submitButton.disabled = false;
        submitButton.value = 'Update';
        document.getElementById('info-alert').innerHTML = `<span class="p-email-alert failure" role="alert">An error occurred: ${error}</span>`;
    });
});

    $('.btn-submitdata').on('click', function() {
        var id = $(this).data('id');
        $('#productt').val("");
        $('#productt').val(id);
        toggleModal();
    var response = '<?php echo $rqs; ?>';
    var data = JSON.parse(response);
    $('.rqdata').empty();
    $.each(data, function(index, item) {
      var html = '<div class="col-md-6">' +
        '<div class="form-group">' +
        '<label>' + item.label + '</label>' +
        '<input type="' + item.type + '" class="form-control" placeholder="' + item.label + '" required="" name="' + item.id + '" id="' + item.id + '">' +
        '</div>' +
        '</div>';
      $('.rqdata').append(html);
      $('.submit-rdata').removeClass("hide");
    });
});


$('.buybtn').on('click', function() {
   $('#mdheader').html("Choose Your Payment Method.");
    toggleModal();
        $('.invoiceid').html("Invoice checkout.");
<?php
if (!is_null($invoice_data['recharge_data']) && !empty($invoice_data['recharge_data'])) {
?>
    $("#tab4").removeAttr("id");
    $("label[for='tab4']").remove();
    $("#content4").remove();
<?php
}
?>
<?php
if (isset($invoice_data['extend_data']) && !is_null($invoice_data['extend_data']) && !empty($invoice_data['extend_data']))
{
    $extend_details = json_decode($invoice_data['extend_data'], true);
    $cost = 0;
    if ($extend_details && isset($extend_details['extend_id'])) {
        switch ($extend_details['extend_id']) {
            case 1: $cost = 40; break;
            case 2: $cost = 220; break;
            case 3: $cost = 400; break;
            case 5: $cost = 120; break;
        }
    }
?>
$('.totalpayment').html("Payment: $" + "<?php echo $cost; ?>");
<?php
}
elseif (isset($invoice_data['products_data']) && !is_null($invoice_data['products_data']) && !empty($invoice_data['products_data']))
{
    $productTotal = 0;
    if (isset($totalproducts) && is_array($totalproducts)) {
       $productTotal = totalPrice($totalproducts);
    }
    $productTotal = is_numeric($productTotal) ? $productTotal : 0;
?>
$('.totalpayment').html("Payment: $" + "<?php echo $productTotal; ?>");
<?php
}
elseif (isset($invoice_data['recharge_data']) && !is_null($invoice_data['recharge_data']) && !empty($invoice_data['recharge_data']))
{
    $recharge_details = json_decode($invoice_data['recharge_data'], true);
    $displayAmount = '0'; // Default
    if ($recharge_details && isset($recharge_details['ammount'])) {
        $displayAmount = htmlspecialchars($recharge_details['ammount'], ENT_QUOTES, 'UTF-8');
    }
?>
$('.totalpayment').html("Payment: $" + "<?php echo $displayAmount; ?>");
$("#tab2").prop("checked", true);
$("#content2").show();

<?php
}
else
{
?>
$('.totalpayment').html("Payment: $0");
<?php
}
?>
        $('.checkoutlink').each(function() {
        var href = $(this).attr('href');
        var id = "<?php echo $_GET['invoice_id']; ?>";
        $(this).attr('href', href + '?invoice=' + id);
        });
});

function processPurchase(type, elm) {
var pybl = document.querySelector('.button-finish');
if(type == 'acc_bal')
{
    $(elm).text("Processing...");
    pybl.style.pointerEvents = 'none';
    pybl.style.opacity = '0.6';

    $.post(window.location.href, { purchase: 1 })
        .done(function(response) {
            var json = JSON.parse(response);
            if (json.status === 'success') {
                Sweet('success', json.message);
                setTimeout(function() {
                    if (json.redirect) {
                        toggleModal();
                        $(elm).text("Complete Order");
                        window.location.href = json.redirect;
                    }
                }, 2000);
            } else {
                $(elm).text("Complete Order");
                pybl.style.pointerEvents = 'auto';
                pybl.style.opacity = '1';

                Sweet('error', json.message);
                toggleModal();
            }
        })
        .fail(function(xhr) {
            var errorMsg = 'Could not complete purchase';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.message) {
                    errorMsg = json.message;
                }
            } catch(e) {}
            Sweet('error', errorMsg);
            $(elm).text("Complete Order");
            pybl.style.pointerEvents = 'auto';
            pybl.style.opacity = '1';

            toggleModal();
        });
}

}

function closeModel() {
  toggleModal();
}

$('.tab-content').hide();
$('input[name=tabs]:checked').each(function() {
    $('#' + $(this).attr('id').replace('tab', 'content')).show();
});

$('input[name=tabs]').on('change', function() {
    $('.tab-content').hide();
    $('#' + $(this).attr('id').replace('tab', 'content')).show();
    
    $('.modal-content').animate({
        scrollTop: $('.modal-content').scrollTop() + 150
    }, 500);
});
</script>