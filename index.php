<?php
session_start();

include "./init.php";

$db->query("SELECT * FROM `products` WHERE `show` = '%d' order by `sort` ASC", 1);
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
 $db->query("SELECT * FROM `products` WHERE `id` = '%d' order by `sort` ASC", $p);
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
    <link href="css/products.css?v=1.3" rel="stylesheet" type="text/css">

    <section class="section2">
        <div class="container">
            <div class="alert-area" id="alert-area"></div>
            
            <div class="section-header">
                <h2>Available Products</h2>
            </div>
            
            <div class="product-grid">
				<?php foreach($products as $product): 
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
				<form class="product-form" data-product="<?= encrypt($product['id']); ?>">
					<div class="product-card">
						<div class="product-image-container">
							<a href="./product/<?= $product['slug']; ?>" class="readmore-link">
								<img src="./panel-assets/uploads/<?= $product['product_image']; ?>" 
									 alt="<?= htmlspecialchars($product['product_name']); ?>"
									 class="product-image">
								<!-- Payment frequency badge in top-right corner -->
								<div class="payment-frequency <?= $badge_class; ?>">
									<?= $badge_text; ?>
								</div>
							</a>
						</div>
						
						<div class="product-body">
							<div class="product-header">
								<h3 class="product-name"><?= $product['product_name']; ?></h3>
								<div class="product-price">$<?= number_format($product['price'], 2); ?></div>
							</div>
							
							<p class="product-description">
								<?= stripText($product['product_description'], 100); ?>
								<a href="./product/<?= $product['slug']; ?>" class="readmore-link">
									Read more
								</a>
							</p>
							
							<button type="button" class="add-to-cart-btn">
								Add To Cart
							</button>
						</div>
					</div>
				</form>
				<?php endforeach; ?>
            </div>
        </div>
    </section>

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