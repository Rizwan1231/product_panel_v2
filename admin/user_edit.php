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

$id = trim(intval($_GET['id']));
$db->query("SELECT * FROM `users` WHERE `id` = '%d'", $id);
$unum = $db->num_rows();
$udata = $db->getdata();

if(empty($id) || $id == "" || empty($unum) || $unum == 0)
{
 redirect("users.php");
 exit();
}

if(isset($_POST['email']) && !empty($_POST['email']))
{
  $id = trim(intval($_GET['id']));
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $status = intval($_POST['is_banned']);
  $isadmin = intval($_POST['is_admin']);
  $credits = intval($_POST['credits']);
  $ref_by = intval($_POST['ref_by']);
  $ref_bonus = intval($_POST['ref_bonus']);
  if(empty($isadmin)){$isadmin = 0;}
  if(empty($credits)){$credits = 0;}
  if(empty($status)){$status = 0;}
  $db->query("SELECT `id` FROM `users` WHERE `email` = '%s' AND `id` != '%d' LIMIT 1", $email, $id);
  $num = $db->num_rows();
  if($num > 0)
  {
    http_response_code(400);
   echo json_encode(array("This Email Already In Use" ));
   exit();
  }
  else
  {

     if(!empty($password))
     {
     $db->query( "UPDATE `users` SET `password` ='%s' WHERE `id` = '%d'", md5($password), $id );
     }
   $user_ip = $_SERVER["REMOTE_ADDR"];
   $vkey = generateRandomString(14);
            $db->query( "UPDATE `users` SET `email` = '%s', `is_banned` = '%d', `ref_by` = '%d', `ref_bonus` = '%d', `is_admin` = '%d', `credits` = '%d' WHERE `id` = '%d'", $email, $status, $ref_by, $ref_bonus, $isadmin, $credits, $id );
    echo json_encode(array("message" => "User Successfully Editted", "redirect" => "users.php" ));
    exit();
  }
 exit();
}

include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<div class="section-header-back">
<a href="users.php" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
</div>
<h1>Edit User Detail</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">admin</div>
<div class="breadcrumb-item">users</div>
<div class="breadcrumb-item">edit user</div>
</div>
</div>
</section>
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
<div class="card-header">
<h4>Edit User</h4>
</div>
<div class="card-body overflow-auto" style="max-height: 600px">
<form method="POST" action="user_edit.php?id=<?= $id; ?>" class="ajaxform_with_redirect">
<div class="row">
<div class="col-sm-12 mb-3">
<label for="email" class="required mb-0">Email</label>
<input type="email" name="email" id="email" value="<?= $udata['email']; ?>" class="form-control" placeholder="Enter email address" required>
</div>
<div class="col-sm-6 mb-3">
<label for="name" class="required mb-0">Password</label>
<input type="text" name="password" id="password" class="form-control" placeholder="Leave empty if you don't want to change">
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Status</label>
<select name="is_banned" id="is_banned" class="form-control" data-control="select2" required>
<option value="0" <?php if($udata['is_banned'] == 0){ echo "selected";} ?>>Active</option>
<option value="1" <?php if($udata['is_banned'] == 1){ echo "selected";} ?>>Inactive</option>
</select>
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Is Admin</label>
<select name="is_admin" id="is_admin" class="form-control" data-control="select2" required>
<option value="0" <?php if($udata['is_admin'] != 1){ echo "selected";} ?>>No</option>
<option value="1" <?php if($udata['is_admin'] == 1){ echo "selected";} ?>>Yes</option>
</select>
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Account Balance( Default Currency USD)</label>
<input type="number" name="credits" id="credits" class="form-control" value ="<?= $udata['credits']; ?>">
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Refferal Of X User</label>
<input type="number" name="ref_by" id="ref_by" class="form-control" value ="<?= $udata['ref_by']; ?>">
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Refferal Earning</label>
<input type="number" name="ref_bonus" id="ref_bonus" class="form-control" value ="<?= $udata['ref_bonus']; ?>">
</div>
</div>
<div class="form-group">
<button class="btn btn-primary float-right basicbtn">
<i class="fas fa-save"> </i>
Save
</button>
</div>
</form>
</div>
</div>
</div>
</div>
</div>

</div>
<?php
include "footer.php";
?>