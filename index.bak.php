<?php
session_start();

include "./init.php";

$db->query("SELECT * FROM `products` WHERE `show` = '%d'", 1);
$total = $db->num_rows();
$products = $db->getall();

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

include "header.php";
?>
	<link href="css/products.css" rel="stylesheet" type="text/css">
    <!-- End Navigation -->
        <!-- section 2 -->
    <section class="section2">
        <div class="container" bis_skin_checked="1">
             <div id="alert-area"></div>
            <div class="title-new" bis_skin_checked="1">
                <h2>Available Products</h2>
            </div>
          <div class="row" bis_skin_checked="1">
               <?php foreach($products as $product){ ?>
                <form class="<?= encrypt($product['id']); ?>">
                <div class="col-sm-4 mrtop-10" bis_skin_checked="1">
                    <div class="box" bis_skin_checked="1">
                        <div class="cover-image"><img class="thumbnail3" src="./panel-assets/uploads/<?= $product['product_image']; ?>"></img></div>
                        <div style="display:inline-grid;">
							<h4 style="float: left;"><?= $product['product_name']; ?></h4>
							<h4 class="price" style="float: right;">Price: $<?= $product['price']; ?></h4>
						</div>
                        <div><?= stripText($product['product_description'], 80); ?><br><a href="./product/<?= $product['slug']; ?>">
						<span class="readmore-btn">Read more</span></a></div>
                    <center class="add-to-cart-btn"><button class="button btn btn-outline">Add To Cart</button></center>
                    </div>
                </div>
                </form>
               <?php } ?>
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