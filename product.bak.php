<?php
session_start();
include "./init.php";

$pslug = $_GET['id'];
if(!$pslug || empty($pslug)) {
  redirect("../index.php");
  exit();
}

$db->query("SELECT * FROM `products` WHERE `slug` = '%s'", $pslug);
$num = $db->num_rows();
$product = $db->getdata();
$pId = $product['id'];

if(!$pId || empty($pId)) {
  redirect("../index.php");
  exit();
}


if(isset($_POST['addtocart']) && !empty($_POST['product']))
{
 $p = decrypt($_POST['product']);
 if(!$p)
 {
  echo json_encode(array( "status" => "danger", "message" => "Failed To Add Product" ));
  exit();
 }
 $db->query("SELECT * FROM `products` WHERE `id` = '%d'", $p);
 $pdata = $db->getdata();
 $num = $db->num_rows();
 if($num == 0)
 {
  echo json_encode(array( "status" => "danger", "message" => "Failed To Add Product" ));
  exit();
 }
 else
 {
  $cartnew = array(
  "product" => encrypt($pdata['id']),
  "product_name" => $pdata['product_name'],
  "product_image" => $pdata['product_image'],
  "product_price" => $pdata['price']
  );
  echo json_encode($cartnew);
  }
 exit();
}
$rTitle = $product['product_name'];
include "header.php";
?>
    <!-- End Navigation -->
	<style>
	img.thumbnail2o { 
    display: inline-block;
	-webkit-box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
    -moz-box-shadow: 0px 0px 5px 0px rgba(54, 45, 50, 0.29);
    box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
	border-radius: 14px !important;
    padding: 5px;
    background: #f8f8f8;
	}
	.breadcrumb2 {
	display: block;
    padding: 25px 15px;
    margin: 20px 0px 20px 0px;
    list-style: none;
    background-color: #f3f6f9;
    border-left: 8px #739fb5 solid;
    -webkit-box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
    -moz-box-shadow: 0px 0px 5px 0px rgba(54, 45, 50, 0.29);
    box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
    border-radius: 5px !important;
}

.breadcrumb3 {
	display: block;
    padding: 25px 15px;
    margin: 20px 0px 20px 0px;
    list-style: none;
    background-color: #fdffff;
    border-left: 8px #8BC34A solid;
    -webkit-box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
    -moz-box-shadow: 0px 0px 5px 0px rgba(54, 45, 50, 0.29);
    box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
    border-radius: 7px !important;
}
span.readmore-btn {
    color: black;
    border: 1px solid #a4a4a4;
    background: #ffffff;
    padding: 4px;
    margin-top: 10px;
    display: block;
    border-radius: 5px;
}
img.thumbnail2 { 
    display: inline-block;
    -webkit-box-shadow: 0px 0px 5px 0px rgb(54 45 50 / 29%);
    -moz-box-shadow: 0px 0px 5px 0px rgba(54, 45, 50, 0.29);
    box-shadow: 0px 0px 2px 0px rgb(54 45 50 / 29%);
    border-radius: 4px !important;
    padding: 1px;
    background: #f8f8f8;
    height: 37px;
    width: 50px;
}
span.readmore-btn:hover {
    color: black;
    border: 1px solid #a4a4a4;
    background: #f3f6f9;
    padding: 4px;
    margin-top: 10px;
    display: block;
    border-radius: 5px;
}
.mrtop-10 { margin-top: 25px; }
button.delete-item.btn.btn-danger {
    font-size: 11px !important;
    border-radius: 4px !important;
    padding: 5px 14px !important;
	color: #FF5722;
    background-color: #ffffff;
    font-weight: 700;
    border: 1.5px solid #FF5722;
}
tr {
    border-bottom: 1px solid #aaaaaa !important;
}
td, th {
    padding: 12px !important;
}
	</style>
        <!-- section 2 -->
		
		
				<?php 
					// Determine payment frequency tag
					$badge_text = '';
					$badge_class = '';
					
					if ($product['expiry_duration'] == 0) {
						$badge_text = 'One-time';
						$badge_class = 'badge-primary';
					} else {
						$duration = $product['expiry_duration'];
						$unit = $product['expiry_duration_in'];
						
						// Map units to readable formats
						$unit_map = [
							'days' => 'day',
							'months' => 'month',
							'years' => 'year'
						];
						
						$unit_text = $unit_map[$unit] ?? $unit;
						
						if ($duration == 1) {
							$badge_text = ucfirst($unit_text) . 'ly'; // Monthly, Yearly, etc
							$badge_class = 'badge-info';
						} else {
							$badge_text = "Every $duration {$unit_text}s";
							$badge_class = 'badge-info';
						}
					}
				?>
		
    <section class="section2">
        <div class="container" bis_skin_checked="1">
             <div id="alert-area"></div>
            <div class="title-new" bis_skin_checked="1">
                <h2><?= $product['product_name']; ?></h2><br>
				<form style="display: flex; justify-content: center;" class="<?= encrypt($product['id']); ?>">
                    <div class="col-md-12 d-flex justify-content-center add-to-cart-btn"><button class="btn btn-lg btn-success btn-lock">Add To Cart + $<?= $product['price']; ?> <?= $badge_text; ?></button></div>
                </form>
                <p><?= $product['product_description']; ?></p><br>
                <div class="main-img"><a href="./panel-assets/uploads/<?= $product['product_image']; ?>" target="_blank"><img style="width: 95%;" class="thumbnail2o" src="./panel-assets/uploads/<?= $product['product_image']; ?>"></img></a></div>
            </div>
          <div class="row breadcrumb3" style="margin-left: 4px;margin-right: 4px;">
          <p><?= $product['long_product_description']; ?></p>
                <form style="display: flex; justify-content: center;" class="<?= encrypt($product['id']); ?>">
                    <div class="col-md-12 d-flex justify-content-center add-to-cart-btn"><button class="btn btn-lg btn-success btn-block">Add To Cart + $<?= $product['price']; ?> <?= $badge_text; ?></button></div>
                </form>
          </div>
        </div>
    </section>
    <!-- section 2 end -->
<script>
var client_url = "<?php echo $sub_url; ?>";
</script>
<?php
if(isset($_GET['action']) && $_GET['action'] == 'loggedout')
{
   session_destroy();
   echo "<script>localStorage.removeItem('loginKey'); loggedin = 0; localStorage.setItem('loginKey', '');</script>";
   echo "<script>window.location.href = 'index.php';</script>";
}
include "footer.php";
?>