<?php
if (basename($_SERVER['SCRIPT_FILENAME']) == 'header.php') {
    exit('Direct access not allowed');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title> Dashboard | Xtream-Masters</title>
    <!-- favicon -->
    <link href="../img/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link href="../img/favicon-16x16.png" rel="icon" type="image/x-icon">
<link rel="stylesheet" href="../panel-assets/plugins/bootstrap/bootstrap.min.css?v1">
<link rel="stylesheet" href="../panel-assets/plugins/fontawesome-5.15.4/css/all.css">
<link rel="stylesheet" href="../panel-assets/plugins/selectric/selectric.css">
<link rel="stylesheet" href="../panel-assets/plugins/select2/select2.min.css">
<link rel="stylesheet" href="../panel-assets/plugins/chatjs/Chart.min.css">

<link rel="stylesheet" href="../panel-assets/plugins/cropperjs/cropper.min.css">
<link rel="stylesheet" href="../panel-assets/plugins/dropzone/dropzone.css">
<link rel="stylesheet" href="../panel-assets/plugins/summernote/summernote-bs4.css">
<link rel="stylesheet" href="../panel-assets/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../panel-assets/css/style.css">
<link rel="stylesheet" href="../panel-assets/css/components.css">
<link rel="stylesheet" href="../panel-assets/css/custom.css">
<link rel="stylesheet" href="../panel-assets/css/flatpickr.min.css">
<script src="../panel-assets/js/flatpicker.js"></script>

</head>
<body>
<style>
.float-right {
    float: right!important;
    margin-left: auto;
}
.hide {
 display: none;
}
@media (max-width: 820px) {
    .main-content {
        padding-left: 10px !important;
        padding-right: 10px !important;
}
</style>
<div class="main-wrapper">

<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar">
<form class="form-inline mr-auto">
<ul class="navbar-nav mr-3">
<li>
<a href="#" data-toggle="sidebar" class="nav-link collapse_btn nav-link-lg">
<i class="fas fa-bars"></i>
</a>
</li>
</ul>
<div class="search-element"></div>
</form>
<ul class="navbar-nav navbar-right">
<li class="dropdown dropdown-list-toggle">
<a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg beep">
<i class="far fa-bell"></i></a>
<div class="dropdown-menu dropdown-list dropdown-menu-right">
<div class="dropdown-header">Notifications
<div class="float-right">
<a href="javascript:void(0)" class="mark-all-as-read">Mark All As Read</a>
</div>
</div>
<div class="dropdown-list-content dropdown-list-icons notification-content overflow-auto">
</div>
<div class="dropdown-footer text-center">
<a href="javascript:void(0)" class="notification-load-more">Load More <i class="fas fa-chevron-down"></i></a>
</div>
</div>
</li>
<li class="dropdown">
<a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
<img alt="image" src="../panel-assets/img/admin.png" class="rounded-circle profile-widget-picture">
<div class="d-sm-none d-lg-inline-block">Profile</div>
</a>
<div class="dropdown-menu dropdown-menu-right">
<a href="profile.php" class="dropdown-item has-icon">
<i class="far fa-user"></i> Profile
</a>
<div class="dropdown-divider"></div>
<a href="index.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item has-icon text-danger">
<i class="fas fa-sign-out-alt"></i> Logout
</a>
<form id="logout-form" action="index.php?action=logout" method="POST" class="d-none">
</div>
</li>
</ul>
</nav>
</form>
<div class="main-sidebar">
<aside id="sidebar-wrapper">
<div class="sidebar-brand">
<a href="index.php" style="color:#fff;">Xtream-Masters</a>
</div>
<div class="sidebar-brand sidebar-brand-sm">
<a href="index.php">d...</a>
</div>
<ul class="sidebar-menu">
<li class="<?php echo activePage('index.php'); ?>">
<a class="nav-link" href="index.php">
<i class="fa fa-home" aria-hidden="true"></i>
<span>Dashboard</span>
</a>
</li>
<!--
<li class="<?php echo activePage('product'); ?>">
<a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
<i class="fab fa-product-hunt"></i>
<span>Manage Orders</span>
</a>
<ul class="dropdown-menu">
<li class=""><a class="nav-link" style="background:#fff" target="_blank" href="https://clients.xtream-masters.com/">Place New Orders</a></li>
<li class=""><a class="nav-link" href="products.php">My Active Orders</a></li>
</ul>
</li>
-->
<li class="<?php echo activePage('product'); ?>">
<a class="nav-link" href="products.php">
<i class="fa fa-wallet" aria-hidden="true"></i>
<span>My Active Orders</span>
</a>
</li>
<?php if(!empty(intval(settingsInfo("REFERRAL_COMMISSION")))){ ?>
<li class="<?php echo activePage('referral.php'); ?>">
<a class="nav-link" href="referral.php">
<i class="fa fa-gift" aria-hidden="true"></i>
<span>Share & Earn</span>
</a>
</li>
<?php } ?>
<li class="<?php echo activePage('invoices.php'); ?>">
<a class="nav-link" href="invoices.php">
<i class="fas fa-shopping-cart" aria-hidden="true"></i>
<span>My Invoices</span>
</a>
</li>
<li class="<?php echo activePage('funds.php'); ?>">
<a class="nav-link" href="funds.php">
<i class="fa fa-money-check-alt" aria-hidden="true"></i>
<span>Add Funds</span>
</a>
</li>
<li class="<?php echo activePage('clogs.php'); ?>">
<a class="nav-link" href="clogs.php">
<i class="fa fa-money-check-alt" aria-hidden="true"></i>
<span>Credits Logs</span>
</a>
</li>
</ul>
<div class="mt-5 mb-4 p-3 hide-sidebar-mini">
<a href="<?= $client_url; ?>index.php" target="_BLANK" class="btn btn-primary btn-lg btn-block btn-icon-split">
<i class="fas fa-external-link-alt"></i> Place New Order
</a>
</div>
</aside>
</div>