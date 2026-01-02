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

if(isset($_POST['email']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['name']))
{
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $status = $_POST['is_banned'];
  $isadmin = intval($_POST['is_admin']);
  $credits = intval($_POST['credits']);
  if(empty($isadmin)){$isadmin = 0;}
  if(empty($credits)){$credits = 0;}
  if(empty($status)){$status = 0;}
  $db->query("SELECT `id` FROM `users` WHERE `email` = '%s' LIMIT 1", $email);
  $num = $db->num_rows();
  if($num > 0)
  {
    http_response_code(400);
   echo json_encode(array("This Email Already In Use" ));
   exit();
  }
  else
  {
   $user_ip = $_SERVER["REMOTE_ADDR"];
   $vkey = generateRandomString(14);
            $db->query( "INSERT INTO `users` (`name`, `email`, `password`, `is_verified`, `user_ip`, `verify_key`, `is_banned`, `is_admin`, `credits`, `date`) VALUES('%s','%s','%s','%d','%s','%s','%d','%d','%d','%d')", $name, $email, md5($password), 1, $user_ip, $vkey, $status, $isadmin, $credits, time() );
   if($db->affected_rows() > 0)
   {
    echo json_encode(array("message" => "New User Successfully Added", "redirect" => "users.php" ));
    exit();
   }
   else
   {
    http_response_code(400);
    echo json_encode(array("Something Went Wrong While Creating New User" ));
    exit();
   }
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
<h1>Create User</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">admin</div>
<div class="breadcrumb-item">users</div>
<div class="breadcrumb-item">create</div>
</div>
</div>
</section>
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
<div class="card-header">
<h4>Create User</h4>
</div>
<div class="card-body overflow-auto" style="max-height: 600px">
<form method="POST" action="add_user.php" class="ajaxform_with_redirect">
<div class="row">
<div class="col-sm-6 mb-3">
<label for="name" class="required mb-0">Name</label>
<input type="text" name="name" id="name" class="form-control" placeholder="Enter full name" required>
</div>
<div class="col-sm-6 mb-3">
<label for="email" class="required mb-0">Email</label>
<input type="email" name="email" id="email" class="form-control" placeholder="Enter email address" required>
</div>
<div class="col-sm-6 mb-3">
<label for="name" class="required mb-0">Password</label>
<input type="text" name="password" id="password" class="form-control" placeholder="Enter password" required>
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Status</label>
<select name="is_banned" id="is_banned" class="form-control" data-control="select2" required>
<option value="0">Active</option>
<option value="1">Inactive</option>
</select>
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Is Admin</label>
<select name="is_admin" id="is_admin" class="form-control" data-control="select2" required>
<option value="0">No</option>
<option value="1">Yes</option>
</select>
</div>
<div class="col-sm-6 mb-3">
<label for="status" class="required mb-0">Account Balance( Default Currency USD)</label>
<input type="number" name="credits" id="credits" class="form-control" value ="">
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