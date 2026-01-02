<?php
session_start();
include "./init.php";

if(isLoggedIn())
{
 $admin = getUserData()['is_admin'];
 if($admin == 1)
 {
  redirect("admin/");
  exit();
 }
 else
 {
  redirect("user/");
  exit();
 }
}

if(isset($_POST['email']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['name']) && !empty($_POST['cpassword']))
{
 $name = trim($_POST['name']);
 $email = trim($_POST['email']);
 $password = trim($_POST['password']);  
 $cpassword = trim($_POST['cpassword']);  
 if($password != $cpassword)
 {
  $alert = makeBootstrapAlert("Confirm Password Is Incorrect Please Try Again!", "info");
 }
 else
 {
  $db->query("SELECT `id` FROM `users` WHERE `email` = '%s' LIMIT 1", $email);
  $num = $db->num_rows();
  if($num > 0)
  {
   $alert = makeBootstrapAlert("This Email Address Already In Use!", "info");
  }
  else
  {
   $user_ip = $_SERVER["REMOTE_ADDR"];
   $vkey = generateRandomString(14);
            $db->query( "INSERT INTO `users` (`name`, `email`, `password`, `is_verified`, `user_ip`, `verify_key`, `date`) VALUES('%s','%s','%s','%d','%s','%s','%d')", $name, $email, md5($password), 1, $user_ip, $vkey, time() );
   if($db->affected_rows() > 0)
   {
    $alert = makeBootstrapAlert("Registration Successfull Redirecting To Login Page...", "success");
    echo '<script>setTimeout(function(){ window.location.href = "login.php";}, 4000);</script>';
   }
   else
   {
    $alert = makeBootstrapAlert("Something Went Wrong While Registring Acocunt Please Contact With Administrator.", "danger");
   }
  }
 }
}
else
{
$alert = makeBootstrapAlert("Please Complete All Fields To Register An Account", "info");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <!-- seo tools -->
    <meta content="We have developed and built one of the most advanced cardsharing networks in the Europe, intended for all capable CCcam receivers." name="description">
    <meta content="cccam,cline,best cccam,cccam server,oscam,newcamd server,ccam,premium cccam,c line,iptv,reseller" name="keywords">
    <meta content="ThemeKolor" name="Pleurat">
    <!-- seo tools -->
    <!-- page title -->
    <title>CCCAM SERVER PREMIUM | Best Cccam Oscam Clines Buy Online | cccamserver.xyz?</title>
    <!-- page title -->
    <!-- favicon -->
    <link href="assets/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link href="assets/favicon.ico" rel="icon" type="image/x-icon">
    <!-- bootstrap css -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- default css / Style Pages -->
    <link href="style/style.css" rel="stylesheet" type="text/css">
    <link href="style/navigation.css" rel="stylesheet">
    <link href="style/preloader.css" rel="stylesheet">
    <link href="style/megamenu-style.css" rel="stylesheet">
    <!-- responsive css -->
    <link href="style/responsive.css" rel="stylesheet" type="text/css">
    <!-- animations -->
    <link href="style/animate.css" rel="stylesheet" type="text/css">
    <!-- fontawesome -->
    <link href="fonts/font-awesome.min.css" rel="stylesheet" type="text/css">
    <!-- google fonts -->
    <link href="https://fonts.googleapis.com/css?family=Maven+Pro:400,500,700,900|Noto+Sans:400,700|Nunito+Sans:400,400i,600,600i,700,700i,800,900,900i" rel="stylesheet">
</head>
<body>
    <!-- preloader -->
    <div id="preloader">
        <div class="text-center " id="status">
            <img src="assets/Preloader.svg" alt="Preloader" class="img-responsive" style="margin: 0 auto">
        </div>
    </div>
    <!-- preloader -->
    <!--START OF TOP-BAR -->
    <div class="container-fluid top-bar">
        <div class="container">
            <div class="col-sm-5 col-xs-5 top-list">
                <ul>
                    <li>
                        <a href="#"><i aria-hidden="true" class="fa fa-facebook fa-md hvr-grow"></i></a>
                    </li>
                    <li>
                        <a href="#"><i aria-hidden="true" class="fa fa-twitter fa-md hvr-grow"></i></a>
                    </li>
                    <li>
                        <a href="#"><i aria-hidden="true" class="fa fa-google-plus fa-md hvr-grow"></i></a>
                    </li>
                    <li>
                        <a href="#"><i aria-hidden="true" class="fa fa-linkedin fa-md hvr-grow"></i></a>
                    </li>
                </ul>
            </div>
            <div class="col-sm-7 col-xs-7 top-list-right">
                <ul>
                    <li class="account"><a href="clientarea.html">My Account</a></li>
                    <li class="toplist-3"><a href="about.html">About</a></li>
                    <li class="toplist-5"><a href="#">Dtblocker</a></li>
                    <li class="toplist-2"><a href="cdn-cgi/l/email-protection.html" class="__cf_email__" data-cfemail="98fbfbfbf9f5ebfdeaeefdeafdedeaf7d8fff5f9f1f4b6fbf7f5">[email&#160;protected]</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!--END OF TOP-BAR  -->
    <!-- Start Navigation -->
    <nav class="navbar navbar-default navbar-sticky navbar-mobile bootsnav">
        <div class="container">
            <!-- Start Atribute Navigation -->
            <div class="attr-nav">
                <ul>
                    <li class="side-menu"><a href="#"><i class="fa fa-bars"></i></a></li>
                </ul>
            </div>
            <!-- End Atribute Navigation -->
            <!-- Start Header Navigation -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                    <i class="fa fa-bars"></i>
                </button>
                <a class="navbar-brand" href="index-2.html"><img src="img/logo/logo2.png" class="logo" alt=""></a>
            </div>
            <!-- End Header Navigation -->
       <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="navbar-menu">
                <ul class="nav navbar-nav navbar-right" data-in="slideInUp" data-out="fadeOut">
                    <li class="dropdown active">
                      <a href="index.html">HOME <span class="new">PAGE</span></a>
                    <li class="dropdown megamenu-fw">
                        <a href="#" class="dropdown-toggle before" data-toggle="dropdown">BUY SERVICES</a>
                        <ul class="dropdown-menu megamenu-content" role="menu">
                            <li>
                                <div class="row">
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">Premium CCcam</h6>
                                        <div class="content">
                                            <img src="templates/cccamserver/img/wp.jpg" alt="ColorHosting" class="img-responsive">
                                            <h5>Starting from <b>€8.00</b></h5>
                                            <p>Premium Cccam Cline</p>
                                            <a href="cart.html" class="button btn btn-outline">Buy Now</a>
                                        </div>
                                    </div>
                                    <!-- end col-3 -->
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">Premium Oscam</h6>
                                        <div class="content">
                                            <img src="templates/cccamserver/img/cloud.jpg" alt="ColorHosting" class="img-responsive">
                                            <h5>Starting from <b>€10.00</b></h5>
                                            <p>Premium Oscam Cline</p>
                                            <a href="cart.html" class="button btn btn-outline">Buy Now</a>
                                        </div>
                                    </div>
                                    <!-- end col-3 -->
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">IPTV SERVER</h6>
                                        <div class="content">
                                            <img src="templates/cccamserver/img/vps.jpg" alt="ColorHosting" class="img-responsive">
                                            <h5>Starting from <b>€12.00</b></h5>
                                            <p>Premium Iptv Server (4000+)</p>
                                            <a href="cart.html" class="button btn btn-outline">Buy Now</a>
                                        </div>
                                    </div>
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">RESELLER CCCAM SERVER</h6>
                                        <div class="Reseller Plan">
                                            <h2>SALE 50%</h2>
                                            <p>Easy-to-use control panel</p>
                                            <a href="reseller.html" class="button btn btn-outline">Read More</a>
                                        </div>
                                    </div>
                                    <!-- end col-3 -->
                                </div>
                                <!-- end row -->
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="oscam.html">OSCAM <span class="new">CLINE</span></a></li>
                    <li class="dropdown">
                        <a href="cccam.html">CCCAM <span class="new">CLINE</span></a></li>
                    <li class="dropdown">
                        <a href="iptv.html">IPTV <span class="new">SERVER</span></a>
                    </li>
                    <li><a href="setup.html">SETUP</a></li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- Side Menu -->
        <div class="side">
            <a href="#" class="close-side"><i class="fa fa-times"></i></a>
            <div class="widget">
                <h6 class="title">BEST CCCAM LINE</h6>
                <ul class="link">
                    <li><a href="cccam.html">Cccam Cline</a></li>
                    <li><a href="cccam.html">Cccam Premium</a></li>
                    <li><a href="cccam.html">Cccam Server</a></li>
                    <li><a href="cccam.html">Cccam Cline</a></li>
                    <li><a href="cccam.html">Best Cccam</a></li>
                </ul>
            </div>
            <div class="widget">
                <h6 class="title">BEST OSCAM LINE</h6>
                <ul class="link">
                    <li><a href="oscam.html">Oscam Cline</a></li>
                    <li><a href="oscam.html">Oscam Pemium</a></li>
                    <li><a href="newcamd.html">Oscam Nline</a></li>
                    <li><a href="oscam.html">Oscam Server</a></li>
                    <li><a href="oscam-2.html">Best Oscam</a></li>
                </ul>
            </div>
        </div>
        <!-- End Side Menu -->
    </nav>
    <!-- End Navigation -->
    <!-- home-header -->
    <div class="new-header">
        <div class="container">

<div class="col-xs-12 main-content" bis_skin_checked="1">
<div class="logincontainer" bis_skin_checked="1">

    <div class="header-lined" bis_skin_checked="1">
    <h1>Signup <small>Create New Account</small></h1>
    </div>
    <div class="row" bis_skin_checked="1">
        <div class="col-sm-12" bis_skin_checked="1">
             <?php if(isset($alert) && !empty($alert)){echo $alert;} ?>
            <form method="post" action="register.php" class="login-form" role="form">
                <div class="form-group" bis_skin_checked="1">
                    <label for="inputEmail">Full Name</label>
                    <input type="text" name="name" class="form-control" id="inputEmail" placeholder="Enter name" autofocus="">
                </div>
                <div class="form-group" bis_skin_checked="1">
                    <label for="inputEmail">Email Address</label>
                    <input type="email" name="email" class="form-control" id="inputEmail" placeholder="Enter email" autofocus="">
                </div>
                <div class="form-group" bis_skin_checked="1">
                    <label for="inputPassword">Password</label>
                    <input type="password" name="password" class="form-control" id="inputPassword" placeholder="Password" autocomplete="off">
                </div>
                <div class="form-group" bis_skin_checked="1">
                    <label for="inputPassword">Confirm Password</label>
                    <input type="password" name="cpassword" class="form-control" id="inputPassword" placeholder="Enter Password Again" autocomplete="off">
                </div>
                                <div align="center" bis_skin_checked="1">
                    <input id="login" type="submit" class="btn btn-primary btn-recaptcha" value="Signup Now"> <a href="login.php" class="btn btn-default">Already Have A Account?</a>
                </div>
            </form>

        </div>
    </div>
</div>

                </div>

            </div>
        </div>
        <!-- container -->
    </section>
    <!-- end of section -->
    <!-- ===== FOOTER ===== -->
    <!-- start of section -->
    <section id="contact-parts">
        <div class="container-fluid">
            <div class="col-sm-6 contact-p1">
                <h2>WhatsApp : +90-505-171-3527</h2>
            </div>
            <div class="col-sm-6 contact-p2">
                <h2><a href="cdn-cgi/l/email-protection.html" class="__cf_email__" data-cfemail="3d54535b527d5e5e5e5c504e584f4b584f13454447">[email&#160;protected]</a></h2>
            </div>
        </div>
    </section>
    <!-- end of first section -->
    <footer>
        <div class="container-fluid footer-fluid">
            <div class="container-fluid partners">
                <div class="col-sm-2 col-xs-6">
                  <img src="img/other/bestcccam.png" class="img-responsive" alt="Hosting Template">
                </div>
                <div class="col-sm-2 col-xs-6">
                    <img src="img/other/iptv.png" class="img-responsive" alt="Hosting Template">
                </div>
                <div class="col-sm-2 col-xs-6">
                    <img src="img/other/oscam.png" class="img-responsive" alt="Hosting Template">
                </div>
                <div class="col-sm-2 col-xs-6">
                    <img src="img/other/paypal1.png" class="img-responsive" alt="Hosting Template">
                </div>
                <div class="col-sm-2 col-xs-6">
                    <img src="img/other/xtream.png" class="img-responsive" alt="Hosting Template">
                </div>
                <div class="col-sm-2 col-xs-6">
                  <img src="img/other/cccam_services1.png" class="img-responsive" alt="Hosting Template">
                </div>
            </div>
            <div class="row">
                <!-- row -->
                <div class="col-lg-4 col-md-4 col-sm-3">
                    <!-- widgets column left -->
                    <ul class="list-unstyled clear-margins">
                        <!-- widgets -->
                        <li class="widget-container widget_nav_menu">
                            <!-- widgets list -->
                            <h2 class="title-widget">About Best Cccam Server</h2>
                            <p>cccamserver.xyz brings a refined and performance-oriented Cardsharing network for fascinating TV watching experience. One single subscription can grant access to the most popular satellite channels at the price you wish to pay. Fast and reliable, our Best Cardsharing server is capable of producing high-level quality free from issues like blurring and freezing.
The CCcam server we offer works best with Dreambox receivers and many other DVB set-top boxes and receivers. Choose among the best cccam server versions, the latest being 2.3.1 to enjoy the advanced features and improved performance. We offer a number of full HD and 3D packages to meet diverse preferences and budgets of our customers.</p>
                        </li>
                    </ul>
                </div>
                <!-- widgets column left end -->
                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-6">
                    <!-- widgets column left -->
                    <ul class="list-unstyled clear-margins">
                        <!-- widgets -->
                        <li class="widget-container widget_nav_menu">
                            <!-- widgets list -->
                            <h2 class="title-widget">Services</h2>
                            <ul>
                                <li><a href="cccam.html"><i class="fa fa-angle-right"></i> Cccam Server</a></li>
                                <li><a href="oscam.html"><i class="fa fa-angle-right"></i>Oscam Server</a></li>
                                <li><a href="newcamd.html"><i class="fa fa-angle-right"></i>Oscam Newcamd</a></li>
                                <li><a href="iptv.html"><i class="fa fa-angle-right"></i>Iptv Server</a></li>
                                <li><a href="reseller-2.html"><i class="fa fa-angle-right"></i>Oscam Reseller</a></li>
                                <li><a href="reseller.html"><i class="fa fa-angle-right"></i>Cccam Reseller</a></li>
                                <li><a href="iptv.html"><i class="fa fa-angle-right"></i>Iptv Reseller</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-6">
                    <!-- widgets column left -->
                    <ul class="list-unstyled clear-margins">
                        <!-- widgets -->
                        <li class="widget-container widget_nav_menu">
                            <!-- widgets list -->
                            <h2 class="title-widget">Best Cccam</h2>
                            <ul>
                                <li><a href="#"><i class="fa fa-angle-right"></i> Best Cccam</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Vip Cccam</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i> Best Oscam</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Vip Oscam</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Newcamd NLine</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Reseller Panel</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Vip Iptv</a>                            </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!-- widgets column left end -->
                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-6">
                    <!-- widgets column left -->
                    <ul class="list-unstyled clear-margins">
                        <!-- widgets -->
                        <li class="widget-container widget_nav_menu">
                            <!-- widgets list -->
                            <h2 class="title-widget">Best Iptv</h2>
                            <ul>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Best Iptv</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Vip Iptv</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Iptv Server</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Iptv Panel</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Iptv Source</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Cccam Cline</a></li>
                                <li><a href="#"><i class="fa fa-angle-right"></i>Oscam Cline</a>                            </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <!-- widgets column left end -->
                <div class="col-lg-2 col-md-2 col-sm-6 col-xs-6">
                    <!-- widgets column center -->
                    <ul class="list-unstyled clear-margins">
                        <!-- widgets -->
                        <li class="widget-container widget_recent_news">
                            <!-- widgets list -->
                            <h2 class="title-widget">Contact </h2>
                            <div class="footerp">
                                <img src="img/logo/logo2.png" class="img-responsive" alt="Hosting Template">
                                <br>
                                <h2 class="title-median">Cccam Server</h2>
                                <p>Email: <a href="cdn-cgi/l/email-protection.html#325b5c545d72515151535f4157404457401c4a4b48"><span class="__cf_email__" data-cfemail="abc2c5cdc4ebc8c8c8cac6d8ced9ddced985d3d2d1">[email&#160;protected]</span></a></p>
                                <p>WhatsApp:+90-505-171-3527</p>
                                <p>Skype : <a href="cdn-cgi/l/email-protection.html" class="__cf_email__" data-cfemail="264252444a49454d4354664e49524b474f4a0845494b">[email&#160;protected]</a></p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <div class="footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 half">
                    <div class="copyright">
                        © 2018, cccamserver.xyz, All rights reserved
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6 half">
                    <div class="design">
                        <a href="cccam.html">Cccam Server</a> | <a href="oscam.html"> Oscam Server</a> | <a href="iptv.html"> Iptv Server</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ===== FOOTER / END ===== -->
    <!-- javascript -->
    <script src="javascript/jquery-3.1.1.min.js"></script>
    <script src="javascript/jquery.scroll-with-ease.min.js"></script>
    <script src="javascript/contact.js"></script>
    <script src="javascript/validator.js"></script>
    <script src="javascript/parallax.js"></script>
    <script src="javascript/bootsnav.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="javascript/javascript.js"></script>
    <!-- javascript -->
</body>
</html>