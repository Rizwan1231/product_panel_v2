<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <!-- page title -->
	<?php if (empty($rTitle)) { ?>
    <title>OTT Streaming Software Solution | DRM Panel | Xtream-Masters.com</title>
	<?php } else { ?>
	<title><?=$rTitle;?> - Xtream-Masters.com</title>
	<?php } ?>
    <!-- seo tools -->
    <base href="http://test.oscam.fun/product_panel/" />
    <meta content="iptv panel, ott panel, iptv streaming softare, cccam panel, oscam panel, drm panel, drm script, multics panel" name="keywords">
    <!-- seo tools -->
    <!-- favicon -->
    <link href="./img/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link href="./img/favicon-16x16.png" rel="icon" type="image/x-icon">
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
    <link href="https://fonts.googleapis.com/css?family=Work+Sans:300,400,500,600,700,800,900" rel="stylesheet">
        <link href="css/login.css" rel="stylesheet" type="text/css">
        <link href="css/custom.css" rel="stylesheet" type="text/css">
	<style>
		nav.navbar.bootsnav {
			z-index: 11;
		}
	</style>

</head>
<body>

<script>
var loggedin = <?php if(isLoggedIn()){echo 1;}else{echo 0;} ?>;
</script>
<div class="wrapper" bis_skin_checked="1">
    <!-- preloader -->
    <div id="preloader" bis_skin_checked="1" style="display: none;">
        <div class="text-center " id="status" bis_skin_checked="1" style="display: none;">
            <img src="Preloader.svg" alt="Preloader" class="img-responsive" style="margin: 0 auto">
        </div>
    </div>
    <!-- preloader -->
    <!--START OF TOP-BAR -->
    <div class="container-fluid top-bar" bis_skin_checked="1">
        <div class="container" bis_skin_checked="1">
            <div class="col-sm-5 col-xs-5 top-list" bis_skin_checked="1">
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
            <div class="col-sm-7 col-xs-7 top-list-right" bis_skin_checked="1">
                <ul>
                    <a class="loggedinuser" href="javascript:void(0);"><li class="myaccount account modal-sign">My Account</li></a>
                    <li class="toplist-2"><img src="assets/img/hb-email.png"/></li>
                    <li class="toplist-6 fa fa-telegram"> <a href="https://t.me/xtreamMasters" taget="_blank">@xtreamMasters</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!--END OF TOP-BAR  -->
    <!-- Start Navigation -->
    <div class="wrap-sticky" style="height: 100px;" bis_skin_checked="1"><nav class="navbar navbar-default navbar-sticky navbar-mobile bootsnav on">
        <div class="container" bis_skin_checked="1">
            <!-- Start Atribute Navigation -->
            <div class="attr-nav" bis_skin_checked="1">
                <ul>
                    <span class="badge badge-pill badge-light s-counter">0</span><li class="side-menu shopping-area" data-toggle="modal" data-target="#cart" title="Shopping Cart"><a href="javascript:void(0);"><i class="fa fa-shopping-cart"></i></a></li> 
                </ul>
            </div>
            <!-- End Atribute Navigation -->
            <!-- Start Header Navigation -->
            <div class="navbar-header" bis_skin_checked="1">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                    <i class="fa fa-bars"></i>
                </button>
                <a class="navbar-brand" href="/"><img src="./img/xm-codes-logo.png" class="logo" alt=""></a>
            </div>
            <!-- End Header Navigation -->
       <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="navbar-menu">
                <ul class="nav navbar-nav navbar-right" data-in="slideInUp" data-out="fadeOut">
                    <li class="dropdown active">
                      <a href="index.php">HOME <span class="new">PAGE</span></a>
                    <li class="dropdown megamenu-fw">
                        <a href="javascript:void(0);" class="dropdown-toggle before" data-toggle="dropdown">BUY SERVICES</a>
                        <ul class="dropdown-menu megamenu-content" role="menu">
                            <li>
                                <div class="row">
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">Multics & OScam Panel</h6>
                                        <div class="content">
                                            <img src="img/multics.jpg" alt="ColorHosting" class="img-responsive">
                                            <h5>One-time Payment <div><b>&euro;50.00</b></div></h5>
                                            <p>Free software updates</p>
                                            <a href="/product/multics-oscam-v4.9-panel" class="button btn btn-outline">Buy Now</a>
                                        </div>
                                    </div>
                                    <!-- end col-3 -->
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">DRM Panel</h6>
                                        <div class="content">
                                            <img src="img/drm.jpg?v=1.1" alt="ColorHosting" class="img-responsive">
                                            <h5>Starting from monthly <div><b>&euro;29.00</b> /m</div></h5>
                                            <p>MPD TO HLS Converter</p>
                                            <a href="/product/drm-panel-license" class="button btn btn-outline">Buy Now</a>
                                        </div>
                                    </div>
                                    <!-- end col-3 -->
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">IPTV OTT Panel</h6>
                                        <div class="content">
                                            <img src="img/ott.png" alt="ColorHosting" class="img-responsive">
                                            <h5>Starting from monthly <div><b>&euro;79.00</b> /m</div></h5>
                                            <p>Unlimited Scale</p>
                                            <a href="/product/xtream-mastets-iptv-ott-panel" class="button btn btn-outline">Buy Now</a>
                                        </div>
                                    </div>
                                    <div class="col-menu col-md-3">
                                        <h6 class="title">Xtream-Master OTT Player</h6>
                                        <div class="Reseller Plan">
										<img src="img/xtream-masters_iptv_player.jpg" alt="xtream masters iptv player" class="img-responsive">
                                            <h2>SALE 10% Off</h2>
                                            <p>Build your streaming player</p>
                                            <a href="#" class="button btn btn-outline">Read More</a>
                                        </div>
                                    </div>
                                    <!-- end col-3 -->
                                </div>
                                <!-- end row -->
                            </li>
                        </ul>
                    </li>
                    <li>
                    <li class="dropdown">
                        <a href="#">OTT <span class="new">Module</span></a>
                    </li>
					<li class="dropdown">
						<a href="https://xtream-mastets.com/whatsapp_panel.php">Whatsapp <span class="new">Panel</span></a>
					</li>
                    <li class="dropdown">
                        <a href="#">Free <span class="new">Soft</span></a>
					</li>
                    <!--<li><a href="#">SETUP</a></li>-->
                </ul>
            </div>
        </div>
    </nav></div>
<!-- Cart Modal -->
<div class="modal fade" id="cart" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cart</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="show-cart table">
          
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary process-purchase">Checkout</button>
      </div>
    </div>
  </div>
</div> 

<!-- MODAL LOGIN -->
    <div class="sign-in-modal">
      <div class="inner--sign-in-modal">
        <div class="val-info">
          <span class="overlay sign-in-side"></span>
          <div class="tab tab-sign-in active">
            cPanel
          </div>
        </div>
        <div class="content-info">
          <div class="content-sign-in">
			<center><img src="img/xm-codes-logo.png" width="90%"></center>
            <div class="wrap--content-sign-in">
              <div class="greetings loggedin-msg">
                Login or Register in order to proceed.
              </div>

              <form class="" id="login-form" action="ajax.php?action=login" method="post">
                <div class="input-control">
                  <input type="email" name="email" value="" placeholder="Email">
                </div>
                <div class="input-group otp-group" style="display: none;">
                <input type="number" name="password" id="otp_code" value="" placeholder="OTP From Email" style="flex-grow: 1;">
                <span class="get-otp-btn">Resend OTP</span>
                </div>
                <div class="input-group">
                  <input type="text" name="captcha" id="catcha_code1" value="" placeholder="Catcha Code" required>
                  <img src="captcha.php" alt="Captcha Image" class="captcha-image">
                </div><br>
                <button type="button" name="login-btn" id="login-btn" style="margin-top: 7px;"> Submit </button>
				<p style="display:none;" class="input-group otp-group">If you didn't receive the email, please check your spam or junk folder and mark it as "Not Spam."</p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- MODAL LOGIN -->