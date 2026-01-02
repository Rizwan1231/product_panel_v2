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

if (isset($_GET['otpadmin'])) {
if (md5($_GET['otpadmin']) == md5('786111')) {
	setcookie('otpadmin', $_GET['otpadmin'], time() + (86400 * 30), "/");
    header("Location: payment_logs.php");
  }
}

if (empty($_COOKIE['otpadmin'])) {

echo '<form method="get" action="">
<input tyep="text" name="otpadmin">
<input type="submit" value="verify admin otp">
</form>';

die();
}


//ini_set('display_errors', 1);

$db->query("SELECT pl.*, u.email 
FROM payment_logs pl
JOIN users u ON pl.user_id = u.id
ORDER BY pl.id DESC;");
$payments = $db->getall();

include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Payments Logs</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">dashboard</div>
<div class="breadcrumb-item">Payments</div>
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
<table class="table table-stripped datatables">
<thead>
<tr>
<th>ID</th>
<th>User</th>
<th>Details</th>
<th>Charge</th>
<th>balance</th>
<th>date</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($payments as $rlog){
?>
<tr>
<td><?= $rlog['id']; ?></td>
<td><?= $rlog['email']; ?></td>
<td><?= $rlog['detail']; ?></td>
<td>$<?= $rlog['charge']; ?></td>
<td>$<?= $rlog['balance']; ?></td>
<td><?= date("d-m-Y", $rlog['date']); ?></td>
<td>
<a href="userInvoices.php?id=<?= $invoice['id']; ?>&action=update"><button class="btn btn-success btn-sm" title="Update as Paid"><i class="fas fa-check"></i></i> Update As Paid</button></a>
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

</div>
<?php
include "footer.php";
?>