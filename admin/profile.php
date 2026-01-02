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

if(isset($_POST['email']) && !empty($_POST['email']))
{
 $uid = getUserData()['id'];
 $email = trim($_POST['email']);
  $db->query("SELECT `id` FROM `users` WHERE `email` = '%s' AND `id` != '%d' LIMIT 1", $email, $uid);
  $num = $db->num_rows();
  if($num > 0)
  {
    http_response_code(400);
   echo json_encode(array("This Email Already In Use" ));
   exit();
  }
 $db->query("UPDATE `users` SET `email` = '%s' WHERE `id` = '%d'", $email, $uid);
 echo json_encode(array("message" => "Successfully Updated", "redirect" => "profile.php" ));
 exit();
}

if(isset($_POST['current_password']) && !empty($_POST['password']) && !empty($_POST['password_confirmation']))
{
 $uid = getUserData()['id'];
 $current_password = trim($_POST['current_password']);
 $password = trim($_POST['password']);
 $cpassword = trim($_POST['password_confirmation']);
  if($password != $cpassword)
  {
    http_response_code(400);
   echo json_encode(array("Confirmation Password Did't Match." ));
   exit();
  }
  $db->query("SELECT `id` FROM `users` WHERE `password` = '%s' AND `id` = '%d' LIMIT 1", md5($current_password), $uid);
  $num = $db->num_rows();
  if($num == 0)
  {
    http_response_code(400);
   echo json_encode(array("Current Passowrd In Invalid Please Provide Valid Current Password." ));
   exit();
  }
 $db->query("UPDATE `users` SET `password` = '%s' WHERE `id` = '%d'", md5($password), $uid);
 echo json_encode(array("message" => "Successfully Updated", "redirect" => "profile.php" ));
 exit();
}

include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Profile Settings</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">admin</div>
<div class="breadcrumb-item">settings</div>
</div>
</div>
</section>
<div class="card">
<div class="card-body">
<div class="row">
<div class="col-md-12">
</div>
<div class="col-md-6">
<form method="post" class="ajaxform_with_redirect" action="profile.php">
<h4 class="mb-20">Edit General Settings</h4>
<div class="custom-form">
<div class="form-group">
<label for="email">Email</label>
<input type="text" name="email" id="email" class="form-control" required placeholder="Enter Email" value="<?= getUserData()['email']; ?>">
</div>
<div class="form-group">
<button type="submit" class="btn btn-primary basicbtn">Update</button>
</div>
</div>
</form>
</div>
<div class="col-md-6">
<form method="post" class="ajaxform_with_redirect" action="profile.php">
<h4 class="mb-20">Change Password</h4>
<div class="custom-form">
<div class="form-group">
<label for="current_password">Current Password</label>
<input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter Current Password" required>
</div>
<div class="form-group">
<label for="password">New Password</label>
<input type="password" name="password" id="password" class="form-control" placeholder="Enter New Password" required>
</div>
<div class="form-group">
<label for="password_confirmation">Confirmation Password</label>
<input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Enter Confirmation Password" required>
</div>
<div class="form-group">
<button type="submit" class="btn btn-primary basicbtn">Update</button>
</div>
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