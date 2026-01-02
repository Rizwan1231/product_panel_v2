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

if(isset($_GET['action']) && $_GET['action'] == 'del' && !empty($_GET['id']))
{
 $id = intval($_GET['id']);
 $db->query("DELETE FROM `products` WHERE `id` = '%d'", $id);
 if($db->affected_rows() > 0)
 {
    echo json_encode(array("message" => "Successfully Delete", "redirect" => "products.php" ));
  exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("Something Went Wrong While Deleting Product" ));
  exit();
 }
}
$db->query("SELECT * FROM `products` ORDER BY `id` DESC");
$products = $db->getall();

include "header.php";
?>
<style>
.ui-sortable-helper {
    background: #f8f9fa;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    display: table;
}
.ui-sortable-helper td {
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}
.ui-sortable-placeholder {
    visibility: visible !important;
    background: #f1f1f1;
}
</style>

<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Products</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">dashboard</div>
<div class="breadcrumb-item">products</div>
</div>
</div>
</section>
<div class="card">
<div class="card-header">
<h4>All Products</h4>
<div class="float-right">
<a href="add_product.php" class="btn btn-primary">Add New</a>
</div>
</div>
<div class="card-body">
<div class="table-responsive product-table">
<table class="table table-stripped datatables">
<thead>
<tr>
<th style="width: 30px;">#</th>
<th>Cover</th>
<th>Product Name</th>
<th>Product Type</th>
<th>Price</th>
<th>Created At</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($products as $product){ ?>
<tr data-id="<?= $product['id']; ?>">
<td><i class="fas fa-arrows-alt handle" style="cursor: move;"></i> <?= $product['sort']; ?></td>
<td>
<figure class="avatar avatar-sm">
<img src="../panel-assets/uploads/<?= $product['product_image']; ?>" alt="<?= $product['product_name']; ?>">
</figure>
</td>
<td><?= $product['product_name']; ?></td>
<td><?php if($product['product_type'] ==1){echo "Downloadable File";}else{echo "Text Data";} ?></td>
<td>$<?= $product['price']; ?></td>
<td><?= date("d-m-Y", $product['date_add']); ?></td>
<td>
<div class="dropdown d-inline">
<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
Action
</button>
<div class="dropdown-menu">
<a class="dropdown-item has-icon" href="edit_product.php?id=<?= $product['id']; ?>"><i class="fas fa-edit"></i></i> Edit</a>
<a class="dropdown-item has-icon delete-confirm" href="javascript:void(0);" data-action="products.php?action=del&id=<?= $product['id']; ?>"><i class="fas fa-trash"></i> Delete</a>
</div>
</div>
</td>

</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

</div>

<?php
include "footer.php";
?>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$(document).ready(function() {
    // Make table rows sortable
    $("tbody").sortable({
        update: function(event, ui) {
            var productOrder = $(this).sortable('toArray', {attribute: 'data-id'});
            updateProductOrder(productOrder);
        }
    }).disableSelection();

    function updateProductOrder(order) {
        $.ajax({
            url: 'update_product_order.php',
            type: 'POST',
            dataType: 'json',
            data: {order: order},
            success: function(response) {
                if(response.success) {
                    toastr.success('Product order updated successfully');
                } else {
                    toastr.error('Error updating product order');
                }
            },
            error: function() {
                toastr.error('Error updating product order');
            }
        });
    }
});
</script>