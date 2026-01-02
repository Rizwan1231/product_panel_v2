<?php
session_start();
include "../init.php";
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



if(isset($_GET['action']) && $_GET['action'] == 'del' && !empty($_GET['id']))
{
 $id = intval($_GET['id']);
 $db->query("DELETE orders, invoices FROM `orders` INNER JOIN `invoices` ON orders.invoice_id = invoices.id WHERE orders.id = '%d'", $id);

 if($db->affected_rows() > 0)
 {
    echo json_encode(array("message" => "Successfully Delete", "redirect" => "orders.php" ));
  exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("Something Went Wrong While Deleting Order" ));
  exit();
 }
}
$db->query("SELECT * FROM `orders`");
$orders = $db->getall();

include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Total Orders</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">dashboard</div>
<div class="breadcrumb-item">orders</div>
</div>
</div>
</section>
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
<th>OrderID</th>
<th>UserEmail</th>
<th>Status</th>
<th>ActivationDate</th>
<th>RenewalDate</th>
<th>Product Name</th>
<th>Total Payment</th>
<th>Total Products</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($orders as $order){
if($order['status'] == 0)
{
 $status = '<button class="btn btn-info btn-sm">Pending</button>';
}
elseif($order['status'] == 1)
{
 $status = '<button class="btn btn-success btn-sm">Active</button>';
}
elseif($order['status'] == 2)
{
 $status = '<button class="btn btn-danger btn-sm">Rejected</button>';
}
elseif($order['status'] == 3)
{
 $status = '<button class="btn btn-info btn-sm">Require data</button>';
}
else
{
 $status = '<button class="btn btn-primary btn-sm">Unknown</button>';
}
$db->query("SELECT `products_data`, `recharge_data`, `extend_data`, `renewal_date` FROM `invoices` WHERE `id` = '%d'", $order['invoice_id']);
$invInfo = $db->getdata();
$pdatas = $invInfo['products_data'];



$totalpaid = totalPrice(getProducts($pdatas));

$isempty = 1;
if (!empty($pdatas)) {
	$isempty = 0;
	$ProductName = productsNames($pdatas);
}

if ($isempty == 1) {
	if (!empty($invInfo['recharge_data'])) {
		$isempty = 0;
		$totalpaid = json_decode($invInfo['recharge_data'], true)['ammount'];
		$ProductName = productsNames($pdatas).'.';
	}
}

if ($isempty == 1) {
	if (!empty($invInfo['extend_data'])) {
		$ProductName = 'Extend License';
	}
}

if ($totalpaid <= 1) {
	$totalpaid = 'Check Payments Logs';
}

$numofp = count(json_decode($pdatas, true));
?>
<tr>
<td><?= $order['invoice_id']; ?></td>
<td><?= userNameByID($order['user_id']); ?></td>
<td><?= $status; ?></td>
<td><?= date("d-m-Y", $order['date']); ?></td>
<td><?= !empty($invInfo['renewal_date']) ? date("d-m-Y", $invInfo['renewal_date']) : '---' ?></td>
<td><?= $ProductName; ?></td>
<td>$<?= $totalpaid; ?>.00</td>
<td><?= $numofp; ?></td>
<td>
<a href="order_detail.php?id=<?= $order['id']; ?>"><button class="btn btn-success btn-sm" title="View Order Detail"><i class="fas fa-info"></i></i> Update Info</button></a>
<a class="delete-confirm" data-action="orders.php?action=del&id=<?= $order['id']; ?>" href="javascript:void(0);"><button class="btn btn-danger btn-sm" title="Delete Order"><i class="fas fa-trash"></i> Delete</button></a>
</td>

</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

</div>
<?php
include "footer.php";
?>