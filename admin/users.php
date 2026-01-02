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
    header("Location: users.php");
  }
}

if (empty($_COOKIE['otpadmin'])) {

echo '<form method="get" action="">
<input tyep="text" name="otpadmin">
<input type="submit" value="verify admin otp">
</form>';

die();
}



if(isset($_GET['action']) && $_GET['action'] == 'del' && !empty($_GET['id']))
{
 $id = intval($_GET['id']);
 $db->query("DELETE FROM `users` WHERE `id` = '%d'", $id);
 if($db->affected_rows() > 0)
 {
    echo json_encode(array("message" => "Successfully Delete", "redirect" => "users.php" ));
  exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("Something Went Wrong While Deleting User" ));
  exit();
 }
}

if(isset($_GET['action']) && $_GET['action'] == 'del_invalid') {
 $db->query("DELETE FROM `users` WHERE (`last_login` IS NULL OR `last_login` = '') AND `is_admin` = 0");
    echo json_encode(array("message" => "Invalid Users Successfully Deleted", "redirect" => "users.php" ));
  exit();
}

$db->query("SELECT * FROM `users`");
$users = $db->getall();

include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Users</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">dashboard</div>
<div class="breadcrumb-item">users</div>
</div>
</div>
</section>
<div class="card">
<div class="card-header">
<h4>Registred Users</h4>
<div class="float-right">
<a href="javascript:void(0);" data-action="users.php?action=del_invalid" class="btn btn-danger delete-confirm">Delete Invalid User</a>
<a href="add_user.php" class="btn btn-primary">Add New</a>
</div>
</div>
<div class="card-body">
<div class="table-responsive product-table">
<table class="table table-stripped datatables">
<thead>
<tr>
<th>ID</th>
<th>Email</th>
<!-- <th>User IP</th> -->
<th>Credits</th>
<th>Is Verified</th>
<th>Status</th>
<th>Total Orders</th>
<th>Total Ref</th>
<th>Ref Earning</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($users as $user){ ?>
<tr>
<td><?= $user['id']; ?></td>
<td><?= $user['email']; ?></td>
<!-- <td><?= $user['user_ip']; ?></td> -->
<td><?= $user['credits']; ?></td>
<td>
<?php if($user['is_verified'] == 1){ echo '<span class="badge badge-success">Verified</span>';}else{echo '<span class="badge badge-danger">Unverified</span>';} ?>
</td>
<td>
<?php if($user['is_banned'] == 1){ echo '<span class="badge badge-danger">Banned</span>';}else{echo '<span class="badge badge-success">Active</span>';} ?>
</td>
<td><?= getTotalOrders($user['id']); ?></td>
<td><?= getTotalRefs($user['id']); ?></td>
<td>
<?php
if(getTotalRefs($user['id']) > 0)
{
echo roundup(getMyComission(totalOrdersOfRefAdmin($user['id']))) . " credits";
}
else
{
echo "--";
}
?>
</td>
<td>
<div class="dropdown d-inline">
<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
Action
</button>
<div class="dropdown-menu">
<a class="dropdown-item has-icon" href="user_edit.php?id=<?= $user['id']; ?>"><i class="fas fa-edit"></i></i> Edit</a>
<a class="dropdown-item has-icon delete-confirm" href="javascript:void(0);" data-action="users.php?action=del&id=<?= $user['id']; ?>"><i class="fas fa-trash"></i> Delete</a>
</div>
</div>
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