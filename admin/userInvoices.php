<?php
session_start();
include "../init.php";
include "../includes/ProductActivationHandler.php";

if(!isLoggedIn())
{
  redirect("../login.php");
  exit();
}
if(isLoggedIn())
{
$admin = getUserData()['is_admin'];
if($admin != 1)
{
  redirect("../user");
  exit();
}
}

// Initialize the activation handler
$activationHandler = new ProductActivationHandler($db);

// Email validation for products that require it
if(isset($_POST['email']) && !empty($_POST['email'])) {
    $email = trim($_POST['email']);
    $invoice_id = $_POST['invoice_id'];

    $db->query("SELECT `products_data`, `recharge_data`, `user_id` FROM `invoices` WHERE `id` = '%d'", $invoice_id);
    $num = $db->num_rows();
    $pdt = $db->getdata();
    $user_id = $pdt['user_id'];

    $pDetails = json_decode($pdt['products_data'], true);
    $pid = decrypt($pDetails[0]['product']);

    // Get product configuration
    $productConfig = $activationHandler->getProductConfig($pid);

    if (!$productConfig) {
        echo json_encode(array("status" => "failed", "message" => "Product not found."));
        exit;
    }

    // Check if product requires email validation
    if (!$activationHandler->requiresEmailValidation($pid)) {
        // Product doesn't require email validation, just update the email
        $emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if(!preg_match($emailPattern, $email)) {
            echo json_encode(array("status" => "failed", "message" => "Invalid email format."));
        } else {
            $db->query("UPDATE `invoices` SET `activation_email` = '%s' WHERE `id` = '%d'", $email, $invoice_id);
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
        $db->query("UPDATE `invoices` SET `activation_email` = '%s' WHERE `id` = '%d'", $email, $invoice_id);
        echo json_encode(array("status" => "success", "message" => "Product email successfully updated."));
    } else {
        echo json_encode(array("status" => "failed", "message" => $validationResult['message']));
    }
    exit;
}


if(isset($_GET['action']) && $_GET['action'] == 'del' && !empty($_GET['id']))
{
 $id = intval($_GET['id']);
 $db->query("DELETE FROM `invoices` WHERE `id` = '%d'", $id);

 if($db->affected_rows() > 0)
 {
    echo json_encode(array("message" => "Successfully Delete", "redirect" => "userInvoices.php" ));
  exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("Something Went Wrong While Deleting Order" ));
  exit();
 }
}

if(isset($_GET['action']) && $_GET['action'] == 'update' && !empty($_GET['id']))
{
 $id = intval($_GET['id']);

 $db->query("SELECT `user_id` FROM `invoices` WHERE `id` = '%d'", $id);
 $invoice = $db->getdata();
 $uid = $invoice['user_id'];

 // Get invoice data
 $db->query("SELECT `products_data`, `recharge_data`, `user_id`, `activation_email` FROM `invoices` WHERE `id` = '%d'", $id);
 $num = $db->num_rows();
 $pdt = $db->getdata();
 $user_id = $pdt['user_id'];
 $pdatas = $pdt['products_data'];
 $activation_email = $pdt['activation_email'];
 $products = json_decode($pdatas, true);
 $recharge_data = $pdt['recharge_data'];
 $rechargeData = json_decode($recharge_data, true);
 $pInfos = getPInfos($products);
 $pids = array();
 $ndatas = array();
 $autoApprove = 0;

 // Track if any activation failed
 $activationFailed = false;
 $activationError = '';

 // Get user data for activation
 $db->query("SELECT * FROM `users` WHERE `id` = '%d'", $user_id);
 $userData = $db->getdata();

 foreach($products as $product)
 {
  $pids[] = $product['product'];
  $pid = decrypt($product['product']);
  $pDetailRqData = $product['require_data'] ?? [];
  $pEmailRq = $activation_email;

  // Prepare invoice data for the handler
  $invoiceData = [
      'require_data' => $pDetailRqData,
      'activation_email' => $pEmailRq,
      'invoice_id' => $id,
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
      // Activation failed - check if it's an API-based product
      $activationType = $activationHandler->getActivationType($pid);
      if ($activationType === 'api') {
          // Critical failure - API activation failed
          $activationFailed = true;
          $activationError = $activationResult['message'];
          break;
      }
  }

  $ndatas[] = $product;
 }

 // If API activation failed, redirect with error
 if ($activationFailed) {
    Header("Location: userInvoices.php?error=activation&msg=" . urlencode($activationError));
    exit();
 }

 // Update invoice status only after successful activation
 $db->query("UPDATE `invoices` SET `status` = 1 WHERE `id` = '%d'", $id);

 if ($autoApprove == 1) {
    $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `id` = '%d'", json_encode($ndatas), $id);
    $db->query( "INSERT INTO `orders` (`user_id`, `invoice_id`, `status`, `date`, `approve_byadmin`) VALUES('%d','%d','%d','%d','%d')", $uid, $id, 1, time(), 1);
 } else {
    // For manual approval products, still create the order but with approve_byadmin = 0
    $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `id` = '%d'", json_encode($ndatas), $id);
    $db->query( "INSERT INTO `orders` (`user_id`, `invoice_id`, `status`, `date`, `approve_byadmin`) VALUES('%d','%d','%d','%d','%d')", $uid, $id, 1, time(), 0);
 }

 // Handle recharge data
 if (isset($rechargeData['ammount'])) {
    $rUserID = intval($rechargeData['user_id']);
    $rAmmount = intval($rechargeData['ammount']);
    $db->query("UPDATE `invoices` SET `status` = '1' WHERE `id` = '%d'", $id);
    $db->query( "INSERT INTO `orders` (`user_id`, `invoice_id`, `status`, `date`) VALUES('%d','%d','%d','%d')", $uid, $id, 1, time());
    $db->query("UPDATE `users` SET `credits` = `credits`+$rAmmount WHERE `id` = '".$rUserID."'");
 }

 if($db->affected_rows() > 0)
 {
    Header("Location: userInvoices.php?success");
  exit();
 }
 else
 {
    Header("Location: userInvoices.php?error");
  exit();
 }
}

$db->query("SELECT * FROM `invoices` WHERE `status` = 0");
$invoices = $db->getall();

include "header.php";
?>
<link rel="stylesheet" href="../panel-assets/modal/style.css">
<link rel="stylesheet" href="../panel-assets/css/p_email.css">
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Unpaid Invoices</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">dashboard</div>
<div class="breadcrumb-item">Invoices</div>
</div>
</div>
</section>

<?php if (isset($_GET['error']) && $_GET['error'] == 'activation'): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-exclamation-triangle"></i> Activation Failed!</strong>
    <?= htmlspecialchars($_GET['msg'] ?? 'Product activation failed. Please check automation logs for details.') ?>
    <br><small><a href="automation_logs.php" class="text-white"><u>View Automation Logs</u></a></small>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-check-circle"></i> Success!</strong> Invoice has been updated successfully.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>
<div class="card">
<div class="card-header">
<div class="float-right">
<!-- <a href="add_user.php" class="btn btn-primary">Add New</a> -->
</div>
</div>
<div class="card-body">
<div class="table-responsive product-table">
<table id="tbRecords" class="table table-stripped datatables">
<thead>
<tr>
<th>InvoiceID</th>
<th>UserEmail</th>
<th>Product Name</th>
<th>Total Payment</th>
<th>InvoiceDate</th>
<th>Total Products</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($invoices as $invoice){

if (!empty($invoice['products_data'])) {
    $totalpaid = totalPrice(getProducts($invoice['products_data']));
} else {
    $totalpaid = json_decode($invoice['recharge_data'], true)['ammount'];
}

$numofp = count(json_decode($invoice['products_data'], true));
?>
<tr>
<td><?= $invoice['id']; ?></td>
<td><?= userNameByID($invoice['user_id']); ?></td>
<td><?= productsNames($invoice['products_data']); ?></td>
<td>$<?= $totalpaid; ?>.00</td>
<td><?= date("d-m-Y", $invoice['date']); ?></td>
<td><?= $numofp; ?></td>
<td>
<?php
$emailVerifP = 0;
if($numofp == 1) {
    $pDetails = json_decode($invoice['products_data'], true);
    $pDetailId = decrypt($pDetails[0]['product']);
    $pEmailRq = $invoice['activation_email'];

    // Check if this product requires email validation using the handler
    if($activationHandler->requiresEmailValidation($pDetailId)) {
        $emailVerifP = 1;
        echo '<a href="javascript:void(0);" onclick="PemailopenModal(\''.$invoice['id'].'\', \''.$pEmailRq.'\')"><button class="btn btn-info btn-sm" title="Update activation details"><i class="fas fa-info"></i></i> Update Activation Email</button></a>';
    }
}
?>

<?php
if($emailVerifP == 1) {
    if($pEmailRq && !empty($pEmailRq)) {
        ?>
        <a href="userInvoices.php?id=<?= $invoice['id']; ?>&action=update"><button class="btn btn-success btn-sm" title="Update as Paid"><i class="fas fa-check"></i></i> Update As Paid</button></a>
        <?php
    }
}else {
?>
<a href="userInvoices.php?id=<?= $invoice['id']; ?>&action=update"><button class="btn btn-success btn-sm" title="Update as Paid"><i class="fas fa-check"></i></i> Update As Paid</button></a>
<?php } ?>
<a class="delete-confirm" data-action="userInvoices.php?action=del&id=<?= $invoice['id']; ?>" href="javascript:void(0);"><button class="btn btn-danger btn-sm" title="Delete invoice"><i class="fas fa-trash"></i> Delete</button></a>
</td>

</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<div id="pEmailModal" class="modal-cstm-overlay">
  <div class="modal-cstm-content">
    <div class="modal-cstm-header">
      <h2>Set product Activation email</h2>
      <span class="close" onclick="PemailcloseModal()">&times;</span>
    </div>
    <div class="modal-cstm-body">
      <rr id="info-alert"></rr>
      <form id="emailForm" method="post" action="">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br>
        <input type="hidden" value="" id="invoice_id" name="invoice_id" value="">
        <input type="submit" value="Update" id="updateButton">
      </form>
    </div>
  </div>
</div>


</div>
<?php
include "footer.php";
?>
<script>

  function PemailopenModal(invoidID, pEmail) {
    document.getElementById("pEmailModal").style.display = "block";
    document.getElementById("email").value = pEmail;
    document.getElementById("invoice_id").value = invoidID;
  }

  function PemailcloseModal() {
    document.getElementById("pEmailModal").style.display = "none";
  }

  document.querySelector(".modal-cstm-overlay").addEventListener("click", function(event) {
    if (event.target == this) {
      PemailcloseModal();
    }
  });

  document.addEventListener("keydown", function(event) {
    if (event.key === "Escape" && document.getElementById("pEmailModal").style.display === "block") {
      PemailcloseModal();
    }
  });

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

</script>