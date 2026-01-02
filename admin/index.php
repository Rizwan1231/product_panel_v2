<?php
session_start();
include "../init.php";

if(isset($_GET['action']) && $_GET['action'] == 'logout')
{
 session_destroy();
 redirect("../login.php");
 exit();
}
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


include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Dashboard</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">user</div>
<div class="breadcrumb-item">dashboard</div>
</div>
</div>
</section>
<section class="section">
<div class="row" bis_skin_checked="1">
<div class="col-lg-4 col-md-6 col-sm-6 col-12" bis_skin_checked="1">
<div class="card card-statistic-1" bis_skin_checked="1">
<div class="card-icon bg-primary" bis_skin_checked="1">
<i class="fas fa-users"></i>
</div>
<div class="card-wrap" bis_skin_checked="1">
<div class="card-header" bis_skin_checked="1">
<h4>Customers</h4>
</div>
<div class="card-body" id="total_customers" bis_skin_checked="1">101</div>
</div>
</div>
</div>
<div class="col-lg-4 col-md-6 col-sm-6 col-12" bis_skin_checked="1">
<div class="card card-statistic-1" bis_skin_checked="1">
<div class="card-icon bg-danger" bis_skin_checked="1">
<i class="fab fa-product-hunt"></i>
</div>
<div class="card-wrap" bis_skin_checked="1">
<div class="card-header" bis_skin_checked="1">
<h4>Total Products</h4>
</div>
<div class="card-body" id="active_plan_users" bis_skin_checked="1">18</div>
</div>
</div>
</div>
<div class="col-lg-4 col-md-6 col-sm-6 col-12" bis_skin_checked="1">
<div class="card card-statistic-1" bis_skin_checked="1">
<div class="card-icon bg-warning" bis_skin_checked="1">
<i class="fas fa-wallet"></i>
</div>
<div class="card-wrap" bis_skin_checked="1">
<div class="card-header" bis_skin_checked="1">
<h4>Total Earnings</h4>
</div>
<div class="card-body" id="total_earnings" bis_skin_checked="1">$87.00</div>
</div>
</div>
</div>
</div>
<div class="row" bis_skin_checked="1">
<div class="col-lg-12 col-md-12 col-12 col-sm-12" bis_skin_checked="1">
<div class="card" bis_skin_checked="1">
<div class="card-header" bis_skin_checked="1">
<h4 class="card-header-title">Earnings performance</h4>
<div class="card-header-action" bis_skin_checked="1">
</div>
</div>
<div class="card-body" bis_skin_checked="1">
body content here
</div>
</div>
</div>
</section>
<?php
include "footer.php";
?>