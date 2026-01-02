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




$order_id = trim($_GET['id']);
$order_id = $order_id;
if(!$order_id || !intval($order_id))
{
  redirect("orders.php?error=invalid_order");
  exit();
}
$db->query("SELECT * FROM `orders` WHERE `id` = '%d'", $order_id);
$order_data = $db->getdata();
$innum = $db->num_rows();
if($innum == 0)
{
  redirect("orders.php?error=invalid_order");
  exit();
}


$db->query("SELECT * FROM `invoices` WHERE `id` = '%d'", $order_data['invoice_id']);
$p_data = $db->getdata();
$products = json_decode($p_data['products_data'], true);
$products_infos = getPInfos($products);

if(isset($_POST['getRqData']) && !empty($_POST['product']))
{
 $p = decrypt($_POST['product']);
 $rdatas = $products;
 $output = array();
 foreach($rdatas as $rdata)
 {
  if($p == decrypt($rdata['product']))
  {
   $output = $rdata['require_data'];
  }
 }
  echo '<div class="table-responsive">';
  echo '<table class="table">';
 echo '<thead><tr><th>Field</th><th>Value</th></tr></thead>';
 echo '<tbody>';
 foreach ($output as $field => $value) {
    echo '<tr><td>' . htmlspecialchars($field) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
 }
 echo '</tbody>';
echo '</table></div>';

 exit();
}

if(isset($_POST['getInsData']) && !empty($_POST['getInsData']))
{
 $p = decrypt($_POST['product']);
 $rdatas = $products;
 $output = array();
 foreach($rdatas as $rdata)
 {
  if($p == decrypt($rdata['product']))
  {
   $output = $rdata['instructions'];
  }
 }
 echo $output;
 exit();
}

// Get renewal plans for a product dynamically
if(isset($_POST['getRenewalPlans']))
{
 $p = decrypt($_POST['product']);
 $plans = $activationHandler->getRenewalPlans($p);
 echo json_encode($plans);
 exit();
}

if(isset($_POST['extend_id']))
{
 $p = decrypt($_POST['product']);
 $extend_id = intval($_POST['extend_id']);
 $invoiceid = $order_data['invoice_id'];

 $pdatas = $products;
 $ndatas = array();

 // Check if product is renewable using the handler
 if (!$activationHandler->isRenewable($p)) {
     http_response_code(400);
     echo json_encode(array("This product does not support renewal."));
     exit();
 }

 // Get renewal plans from handler
 $renewalPlans = $activationHandler->getRenewalPlans($p);
 $validPlanIds = array_column($renewalPlans, 'id');

 if (!in_array($extend_id, $validPlanIds)) {
     http_response_code(400);
     echo json_encode(array("Invalid extension period selected."));
     exit();
 }

 $extend_cost = $activationHandler->getRenewalCost($p, $extend_id);

 foreach ($pdatas as $pdata) {
    $product = decrypt($pdata['product']);
    if ($product == $p && $activationHandler->isRenewable($product)) {
        $instructions = $pdata['instructions'];
        $insEmail = preg_match('/Product activation email:\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $instructions, $matches) ? $matches[1] : '';

        $activation_email = $p_data['activation_email'];
        if(!$activation_email || empty($activation_email)) {
            $activation_email = $insEmail;
        }
        if(!$activation_email || $activation_email == "") {
            http_response_code(400);
            echo json_encode(array("Activation email not set for this product."));
            exit();
        }

        // Update activation email
        $db->query("UPDATE `invoices` SET `activation_email` = '%s' WHERE `id` = '%d'", $activation_email, $invoiceid);

        // Get user data
        $db->query("SELECT * FROM `users` WHERE `id` = '%d'", $order_data['user_id']);
        $userData = $db->getdata();

        // Prepare invoice data for renewal
        $renewalInvoiceData = [
            'activation_email' => $activation_email
        ];

        // Use centralized handler for renewal
        $renewalResult = $activationHandler->renewProduct(
            $p,
            $invoiceid,
            $extend_id,
            $renewalInvoiceData,
            $userData,
            'admin'
        );

        if ($renewalResult['success']) {
            $renewal_date = $renewalResult['renewal_date'] ?? null;
            if ($renewal_date) {
                $db->query("UPDATE `invoices` SET `renewal_date` = '%s' WHERE `id` = '%d'", $renewal_date, $invoiceid);
            }
            $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 1, $invoiceid);
            echo json_encode(array("message" => "Successfully extended.", "redirect" => "order_detail.php?id=".$_GET['id']."" ));
            exit();
        } else {
            http_response_code(400);
            echo json_encode(array($renewalResult['message']));
            exit();
        }
    }
 }
}

if(isset($_POST['instructions']))
{
 $p = decrypt($_POST['product']);
 $ins = trim($_POST['instructions']);
 $invoiceid = $order_data['invoice_id'];
 $pdatas = $products;
 $ndatas = array();
foreach ($pdatas as $pdata) {
    $product = decrypt($pdata['product']);
    if ($product == $p) {
     $pdata['instructions'] = $ins;
    }
    $ndatas[] = $pdata;
}
 $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `id` = '%d'", json_encode($ndatas), $invoiceid);
 $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 1, $invoiceid);
 echo json_encode(array("message" => "Successfully Updated.", "redirect" => "order_detail.php?id=".$_GET['id']."" ));
 exit();
}

if(isset($_POST['status']) && !empty($_POST['product']))
{
 $p = decrypt($_POST['product']);
 $status = intval($_POST['status']);
 $invoiceid = $order_data['invoice_id'];
if($status == 3)
{
 $pdatas = $products;
 $ndatas = array();
foreach ($pdatas as $pdata) {
    $product = decrypt($pdata['product']);
    if ($product == $p) {
     unset($pdata['require_data']);
    }
    $ndatas[] = $pdata;
}
 $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `id` = '%d'", json_encode($ndatas), $invoiceid);
 $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 0, $invoiceid);
}
elseif($status == 2)
{
 $db->query("UPDATE `orders` SET `status` = '%d' WHERE `invoice_id` = '%d'", 2, $invoiceid);
 $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 0, $invoiceid);
}
elseif($status == 1)
{
 $db->query("UPDATE `orders` SET `status` = '%d' WHERE `invoice_id` = '%d'", 1, $invoiceid);
 $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 1, $invoiceid);
 $db->query("UPDATE `orders` SET `date` = '%d' WHERE `invoice_id` = '%d'", time(), $invoiceid);
}
else
{
 $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 0, $invoiceid);
}
 echo json_encode(array("message" => "Successfully Updated.", "redirect" => "order_detail.php?id=".$_GET['id']."" ));
 exit();
}

include "header.php";
?>
<style>
.btn-sm.w-0 {
    margin-left: 10px;
}
</style>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Product Detail</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">user</div>
<div class="breadcrumb-item">product detail</div>
</div>
</div>
</section>
<section class="section">
<div class="row" bis_skin_checked="1">
<div class="col-lg-12 col-md-12 col-12 col-sm-12" bis_skin_checked="1">
<div class="card" bis_skin_checked="1">
<div class="card-header" bis_skin_checked="1">
<h4 class="card-header-title">Order Detail</h4>
<div class="float-right">
<button class="btn btn-success">Total Products: <?= count($products); ?></button>
</div>
</div>
</div>

<div class="row product-show">

<?php
foreach ($products as $pkey => $pd)
{
$pdinfos = $products_infos[$pkey];

if($order_data['status'] == 0)
{
 $status = '<button class="btn btn-info btn-sm">Pending</button>';
}
elseif($order_data['status'] == 3)
{
 $status = '<button class="btn btn-info btn-sm">Require data</button>';
}
elseif($order_data['approve_byadmin'] == 0 && $pdinfos['is_require_data'] == 1)
{
 $status = '<button class="btn btn-info btn-sm">Pending For Admin Approval</button>';
}
elseif($order_data['status'] == 1)
{
 $status = '<button class="btn btn-success btn-sm">Active</button>';
}
elseif($order_data['status'] == 2)
{
 $status = '<button class="btn btn-danger btn-sm">Rejected</button>';
}
else
{
 $status = '<button class="btn btn-primary btn-sm">Unknown</button>';
}

?>
<div class="col-md-6">
<div class="card">
<div class="card-body">
<div class="table-responsive">
<table class="table table-stripped">
<thead>
<tr>
<th>Cover</th>
<td>
<figure class="avatar avatar-sm">
<img src="../panel-assets/uploads/<?= $pd['product_image']; ?>" alt="<?= $pd['product_name']; ?>">
</figure>
</td>
</tr>
<tr>
<th>Product Status</th>
<?php
if($pdinfos['is_require_data'] == 1 && (!isset($pd['require_data']) || empty($pd['require_data'])))
{
echo '<td><button class="btn btn-info btn-sm">Data Submit Requested</button></td>';
} else {
?>
<td><?= $status; ?></td>
<?php } ?>
</tr>
<tr>
<th>Product Name</th>
<td><?= $pd['product_name']; ?></td>
</tr>
<tr>
<th>Price</th>
<td>$<?= $pd['product_price']; ?>.00</td>
</tr>
<tr>
<th>Product Type</th>
<td><?php if($pdinfos['product_type'] == 2){ echo 'Text Data';}else{echo 'Downloadable File';} ?></td>
</tr>
<tr>
<th>Require Data Before Active?</th>
<td><?php if($pdinfos['is_require_data'] == 1){ echo 'Yes => ' . checkRdata($pdinfos['require_data']);}else{echo 'No';} ?></td>
</tr>
<?php if($pdinfos['is_require_data'] == 1) {
?>
<tr>
<th>Submitted Data</th>
<?php
if(isset($pd['require_data']) && !empty($pd['require_data']))
{
?>
<td><button class="btn btn-primary btn-sm w-100 btn-viewdata" data-id="<?= encrypt($pdinfos['id']); ?>">View Data</button></td>
<?php }else{ ?>
<td>no</td>
<?php }} ?>
</tr>
</thead>
</table>
</div>
<br>
<div class="row" style="left: 78px;display: inline-flex;position: relative;">
<button type="button" class="btn btn-primary btn-sm w-0 up-status" data-id="<?= encrypt($pdinfos['id']); ?>">Update Status</button>
<button type="button" class="btn btn-success btn-sm w-0 add-ins" data-id="<?= encrypt($pdinfos['id']); ?>">Add Instrunctions</button>
<?php
// Check if product is renewable using the handler
if($activationHandler->isRenewable($pdinfos['id']) && $order_data['status'] == 1) {
?>
<button type="button" class="btn btn-info btn-sm w-0 extend-cms" data-id="<?= encrypt($pdinfos['id']); ?>" data-product-id="<?= $pdinfos['id']; ?>">Extend license</button>
<?php } ?>
</div>
</div>
</div>
</div>
<?php } ?>


</div>

</div>
  <link rel="stylesheet" href="../panel-assets/modal/style.css">
<div class="modal">
    <div class="modal-content">
    <p modal-header"><rr id="mdheader"></rr> <span class="close-button">x</span></p>
<form action="" method="post" class="ajaxform_with_redirect">
<div class="col-lg-12">
<div class="card">
<div class="card-body">
<div class="form-row rqdata">
<center><img style="width: 50%;left: 147px;position: relative;" src="../assets/img/loader.gif"></img></center>
</div>
<input type="hidden" name="rdata" value="1">
<input type="hidden" name="product" id="productt" value="">
<div class="btn-publish submit-rdata hide">
<button type="submit" class="btn btn-primary col-12 basicbtn"><i class="fa fa-save"></i> Save</button>
</div>

</div>
</div>

</div>
</form>

    </div>
</div>

</section>
<?php
include "footer.php";
?>
<script>
var modal = document.querySelector(".modal");
var trigger = document.querySelector(".trigger");
var closeButton = document.querySelector(".close-button");

function toggleModal() {
    modal.classList.toggle("show-modal");
}

function windowOnClick(event) {
    if (event.target === modal) {
        toggleModal();
    }
}
closeButton.addEventListener("click", toggleModal);
window.addEventListener("click", windowOnClick);

    $('.btn-viewdata').on('click', function() {
        var id = $(this).data('id');
   $('#mdheader').html("Buyer Submitted Data");
        toggleModal();
  $.post(window.location.href, {getRqData: 1, product: id}, function(response) {
    if(response == 0)
    {
      $('.rqdata').html("Information not found");
      $('.submit-rdata').addClass("hide");
      return;
    }
    $('.rqdata').empty();
        $('.submit-rdata').addClass("hide");
      $('.rqdata').html(response);
  });

});

$('.add-ins').on('click', function() {
        var id = $(this).data('id');
        $('#mdheader').html("Instructions About Product Usage or License Etc.");
        toggleModal();
        $('.rqdata').empty();
        $('#productt').val("");
        $('#productt').val(id);
        $.post(window.location.href, {getInsData: 1, product: id}, function(response)
        {
        var html = '<div class="col-md-12"><div class="form-group"><label>Instructions</label><textarea class="form-control" placeholder="eg. your license detail is ...." name="instructions" id="instructions" style="height: 279px;">' + response + '</textarea></div></div>';
        $('.rqdata').html(html);
        $('.submit-rdata').removeClass("hide");
    });
});

$('.extend-cms').on('click', function() {
        var id = $(this).data('id');
        var productId = $(this).data('product-id');
        $('#mdheader').html("Extend license");
        toggleModal();
        $('.rqdata').empty();
        $('#productt').val("");
        $('#productt').val(id);

        // Fetch renewal plans dynamically
        $.post(window.location.href, {getRenewalPlans: 1, product: id}, function(response) {
            var plans = JSON.parse(response);
            var html = '<div class="col-md-12">';
            html += '<div class="form-group">';
            html += '<label for="extend_id">Select Extend Period</label>';
            html += '<select class="form-control" name="extend_id" id="extend_id">';
            for (var i = 0; i < plans.length; i++) {
                html += '<option value="' + plans[i].id + '">' + plans[i].name + ' ($' + plans[i].price + ')</option>';
            }
            html += '</select>';
            html += '</div>';
            html += '</div>';
            $('.rqdata').html(html);
            $('.submit-rdata').removeClass("hide");
        });
});

$('.up-status').on('click', function() {
        var id = $(this).data('id');
        $('#mdheader').html("Update Product Status");
        toggleModal();
        $('.rqdata').empty();
        $('#productt').val("");
        $('#productt').val(id);
        var html = '<div class="col-md-12"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="0">Pending</option><option value="1">Active (This will reset start date if there is expiry for this product)</option><option value="2">Rejected (This will set complete order as rejected)</option><option value="3">Submit Require Data</option></select></div></div>';
        $('.rqdata').html(html);
        $('.submit-rdata').removeClass("hide");
});


</script>