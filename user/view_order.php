<?php
session_start();

include "../init.php";
include "../includes/ProductActivationHandler.php";

// Initialize the activation handler
$activationHandler = new ProductActivationHandler($db);

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

$order_id = trim($_GET['id']);
$order_id = decrypt($order_id);
if(!$order_id || !intval($order_id))
{
  redirect("products.php?error=invalid_product");
  exit();
}
$uid = getUserData()['id'];
$db->query("SELECT * FROM `orders` WHERE `user_id` = '%d' AND `id` = '%d'", $uid, $order_id);
$order_data = $db->getdata();
$innum = $db->num_rows();
if($innum == 0)
{
  redirect("products.php?error=invalid_product");
  exit();
}

if($order_data['status'] == 0 || $order_data['status'] == 2)
{
  redirect("products.php");
  exit();
}

$db->query("SELECT * FROM `invoices` WHERE `user_id` = '%d' AND `id` = '%d'", $uid, $order_data['invoice_id']);
$p_data = $db->getdata();
$products = json_decode($p_data['products_data'], true);
$activation_email = $p_data['activation_email'];
$products_infos = getPInfos($products);


if(isset($_POST['extend_id']))
{
 $p = decrypt(trim($_POST['product']));
 $extend_id = intval($_POST['extend_id']);
 $invoiceid = $order_data['invoice_id'];

 // Check if product is renewable using the handler
 if (!$activationHandler->isRenewable($p)) {
     http_response_code(400);
     echo json_encode(array("This product does not support renewal."));
     exit();
 }

 // Validate plan id using handler
 $renewalPlans = $activationHandler->getRenewalPlans($p);
 $validPlanIds = array_column($renewalPlans, 'id');

 if (!in_array($extend_id, $validPlanIds)) {
     http_response_code(400);
     echo json_encode(array("Invalid extension period selected."));
     exit();
 }

 $uid = getUserData()['id'];
 $rdata = array( "invoice_id" => encrypt($invoiceid), "product" => encrypt($p), "extend_id" => $extend_id, "status" => 0 );
 $db->query("INSERT INTO `invoices` (`user_id`, `products_data`, `recharge_data`, `extend_data`, `date`) VALUES ('%d', '', '', '%s', '%d')", $uid, json_encode($rdata), time());
 $invoice_id = $db->inserted_id();
 echo json_encode(array( "status" => "success", "message" => "Invoice successfully generated.", "redirect" => 'invoice.php?invoice_id=' . encrypt($invoice_id) ));
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

if(isset($_POST['getRqData']) && !empty($_POST['product']))
{
 $p = trim(decrypt($_POST['product']));
 $db->query("SELECT `require_data` FROM `products` WHERE `id` = '%d'", $p);
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
 echo json_encode($rqs);
 exit();
}

if(isset($_POST['rdata']) && !empty($_POST['rdata']))
{
 $uid = getUserData()['id'];
 unset($_POST['rdata']);
 $pid = decrypt($_POST['product']);

 unset($_POST['product']);
 $datas = $_POST;
 $invoiceid = $order_data['invoice_id'];
 $db->query("SELECT `products_data` FROM `invoices` WHERE `id` = '%d'", $invoiceid);
 $num = $db->num_rows();
 $pdatas = $db->getdata()['products_data'];
 $pdatas = json_decode($pdatas, true);
 $ndatas = array();

 $db->query("UPDATE `invoices` SET `products_data` = '%s' WHERE `user_id` = '%d' AND `id` = '%d'", json_encode($ndatas), $uid, $invoiceid);
 if($db->affected_rows() > 0)
 {
  $db->query("UPDATE `orders` SET `approve_byadmin` = '%d' WHERE `invoice_id` = '%d'", 0, $invoiceid);
  echo json_encode(array("message" => "Successfully Submitted.", "redirect" => "view_order.php?id=".$_GET['id']."" ));
 exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("Failed To Submit Detail Please Try Again." ));
    exit();
 }
}
if(isset($_POST['download']) && !empty($_POST['product']))
{
 $p = decrypt($_POST['product']);
 if(!$p)
 {
    header("Location: products.php");
    exit();
 }
 $db->query("SELECT `product`, `product_type` FROM `products` WHERE `id` = '%d'", $p);
 $pnum = $db->num_rows();
 if($pnum == 0)
 {
    header("Location: products.php");
    exit();
 }
 $pdata = $db->getdata();
 if($pdata['product_type'] != 1)
 {
    header("Location: products.php");
    exit();
 }
 $file_location = "../panel-assets/uploads/" . $pdata['product'];
 $new_name = $uid . '-' . substr(basename($file_location), 11);
 header('Content-Type: application/octet-stream');
 header('Content-Disposition: attachment; filename="'.$new_name.'"');
 header('Content-Length: ' . filesize($file_location));
 header("Pragma: no-cache"); 
 header("Expires: 0"); 
 readfile($file_location);
 exit();
}
include "header.php";

?>
<style>
.text-copy {
    height: 180px !important;
}

.product-info {
    display: flex;
    align-items: center; 
}

.avatar {
    border-radius: 6%;
    margin-right: 20px;
    height: 50px;
    width: 90px;
}
.avatar img {
    border-radius: 6%;
}

img.thumbnail2 {
    -webkit-box-shadow: 0px 0px 5px 0px rgb(25 22 23 / 37%);
    -moz-box-shadow: 0px 0px 5px 0px rgba(25 22 23 / 37%);
    box-shadow: 0px 0px 5px 0px rgb(25 22 23 / 37%);
    background: #f8f8f8;
}
</style>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Product Details</h1>
</div>
</section>
<section class="section">
<div class="row" bis_skin_checked="1">
<div class="col-lg-12 col-md-12 col-12 col-sm-12" bis_skin_checked="1">
<div class="card" bis_skin_checked="1">
<div class="card-header" bis_skin_checked="1">
<h4 class="card-header-title">Invoice Order Details #<?= $order_data['invoice_id']; ?></h4>
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
?>
<div class="col-md-12">
<div class="card">
<div class="card-body">
<div class="table-responsived">


<div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2">
    <div class="d-flex align-items-center"> <figure class="avatar"> <a href="../panel-assets/uploads/<?= htmlspecialchars($pd['product_image']); ?>" target="_blank">
                <img class="thumbnail2" 
                     src="../panel-assets/uploads/<?= htmlspecialchars($pd['product_image']); ?>" 
                     alt="<?= htmlspecialchars($pd['product_name']); ?>"> </a>
        </figure>

        <div class="product-details">
            <b>Product:</b> <?= htmlspecialchars($pd['product_name']); ?><br>
            <b>Cost:</b> $<?= htmlspecialchars(number_format($pd['product_price'], 2)); ?> 
        </div>
        
    </div>

    <div>
	<?php if($activationHandler->isRenewable($pdinfos['id'])) { ?>
        <a href="javascript:void(0);" class="btn btn-success extend-cms" data-id="<?= $pd['product']; ?>" data-product-id="<?= $pdinfos['id']; ?>">Extend License</a>
	<?php } ?>
        </div>

</div>


<hr>

<div><?php if($pdinfos['product_type'] == 2){ echo 'Purchased Info';}else{echo 'File';} ?></div>

<?php
if($pdinfos['is_require_data'] == 1)
{
?>
<?php
if(is_null($pd['require_data']) || empty($pd['require_data']))
{
?>
<button class="btn btn-info btn-sm w-100 btn-submitdata" data-id="<?= encrypt($pdinfos['id']); ?>">Some data require click to submit</button>
<?php
} else{
?>
<?php if($order_data['approve_byadmin'] == 0) { ?>
<button class="btn btn-info btn-sm w-100">Pending</button>
<?php }else{ if($pdinfos['product_type'] == 1) { ?>
<form method="post" action="">
<input type="hidden" name="product" id="product" value="<?= encrypt($pdinfos['id']); ?>">
<input type="hidden" name="download" id="download" value="1">
<button type="submit" class="btn btn-primary btn-sm w-100 basicbtn"><i class="fa fa-download" aria-hidden="true"></i> Download</button>
</form>
<?php }}} ?>
<?php
} else{
?>
<?php if($pdinfos['product_type'] == 1)
{
?>
<form method="post" action="">
<input type="hidden" name="product" id="product" value="<?= encrypt($pdinfos['id']); ?>">
<input type="hidden" name="download" id="download" value="1">
<button type="submit" class="btn btn-primary btn-sm w-100 basicbtn"><i class="fa fa-download" aria-hidden="true"></i> Download</button>
</form>
<?php }else{ ?>
<textarea class="form-control text-copy" rows="8" readonly="true"><?= $pdinfos['product']; ?></textarea>
<?php }} ?>
<hr>


<?php if($pdinfos['id'] == 1) {
$ins = $pd['instructions'];
$licenseKey = preg_match('/License Key:.*$/m', $ins, $matches) ? $matches[0] : "";
$licenseKey = trim(str_replace('License Key:', '', $licenseKey));
?>

<div>License Domain</div>
<?= $pd['require_data']['license_domain']; ?>
<hr>

<div>License Key</div>
<textarea style="height: 73px;resize: none;" class="form-control cplicense" readonly><?= $licenseKey; ?></textarea>
<hr>

<?php } ?>

<?php if($order_data['status'] == 1 && !is_null($pdinfos['expiry_duration']) && !is_null($pdinfos['expiry_duration_in']) && $order_data['approve_byadmin'] != 0)
{
?>

<div>License Date</div>
Start Date: <?= date("d-m-Y", $order_data['date']); ?><br>
Renew Date: <?= date("d-m-Y", strtotime("+1 months", $order_data['date'])); ?>
<hr>
<?php } ?>
<?php if(isset($products[$pkey]['instructions']) && !empty($products[$pkey]['instructions']) && $order_data['approve_byadmin'] == 1) { ?>

<div>Instructions About Product</div>

<textarea rows="10" style="height: 380px;" class="form-control" readonly><?php echo $products[$pkey]['instructions']; ?></textarea>

<hr>

<?php } ?>

</div>
</div>
</div>
</div>
<?php } if (empty($products)) { ?>


<div class="col-md-12">
<div class="card">
<div class="card-body">


<div class="d-flex3 align-items-center justify-content-between p-2">
    <div class="align-items-center"> 
		<h4>Please contact to support to complete your order asap manually.</h4>
	</div>
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
    <p modal-header"><rr id="mdheader">Please Submit This Data Before Order Activation.</rr> <span class="close-button" title="Close">x</span></p>
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
<button type="submit" class="btn btn-primary col-12 basicbtn"><i class="fa fa-paper-plane"></i> Submit</button>
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
    $('.btn-submitdata').on('click', function() {
        var id = $(this).data('id');
        $('#productt').val("");
        $('#productt').val(id);
        toggleModal();
  $.post(window.location.href, {getRqData: 1, product: id}, function(response) {
    if(response == 0)
    {
      $('.rqdata').html("Information not found");
      $('.submit-rdata').addClass("hide");
      return;
    }
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
});

<?php if (strlen($activation_email) > 9) { ?>

$('.extend-cms').on('click', function() {
        var id = $(this).data('id');
        var productId = $(this).data('product-id');
        $('#mdheader').html("Extend License");
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

<?php } else { ?>

$('.extend-cms').on('click', function() {
        var id = $(this).data('id');
        $('#mdheader').html("Extend License");
        toggleModal();
        $('.rqdata').empty();
        $('#productt').val("");
        $('#productt').val(id);
        var html = '<div class="col-md-12">';
        html += '<div class="form-group">';
        html += 'Please set your activation email first to extend your license.<br><br>You can set it from your order page or contact support.';
        html += '</div>';
        html += '</div>';
        $('.rqdata').html(html);
});

<?php }?>

$('.btn-vwins').on('click', function()
{
   toggleModal();
   $('#mdheader').html("Instructions About Product");
   $('.submit-rdata').addClass("hide");
   var ins = $(this).data('instructionsbyadmin');
   var html = '<div class="col-md-12"><div class="form-group"><label>Instructions</label><textarea class="form-control" placeholder="" name="instructions" id="instructions" readonly="true" style="height: 340px;">' + atob(ins) + '</textarea></div></div>';
   $('.rqdata').html(html);
});


$(".cplicense").click(function() {
  var value = $(".cplicense").val();
  var $temp = $("<input>");
  $("body").append($temp);
  $temp.val(value).select();
  document.execCommand("copy");
  $temp.remove();
  
  Swal.fire({
    icon: 'success',
    title: 'License Key Copied',
    text: 'The license key has been copied to your clipboard.',
    showConfirmButton: false,
    timer: 2000
  });
});
</script>