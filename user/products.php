<?php
session_start();
include "../init.php";
if(!isLoggedIn())
{
  redirect("../index.php");
  exit();
}

$userId = getUserData()['id'];

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query based on filters
$where_clause = "WHERE `user_id` = '$userId'";
if($filter_status != 'all') {
    $status_map = [
        'active' => 1,
        'pending' => 0,
        'rejected' => 2,
        'requires_action' => 3
    ];
    if(isset($status_map[$filter_status])) {
        $where_clause .= " AND `status` = '{$status_map[$filter_status]}'";
    }
}

$db->query("SELECT * FROM `orders` $where_clause ORDER BY `date` DESC");
$orders = $db->getall();

include "header.php";
?>

<style>
/* Page Container */
.orders-container {
    padding: 15px;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}
.table-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f4f8;
	background: #fcfcfc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
.table-header h4 {
    font-size: 1rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Search Box */
.search-box {
    position: relative;
    width: 220px;
}
.search-box input {
    width: 100%;
    padding: 8px 35px 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.8rem;
}
.search-box button {
    position: absolute;
    right: 3px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.75rem;
}

/* Table Body */
.table-body {
    padding: 15px;
}

/* Desktop Table */
.data-table {
    width: 100%;
    display: table;
}
.data-table thead th {
    background: #f8fafc;
    color: #4a5568;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.3px;
    padding: 10px 12px;
    border: none;
}
.data-table tbody tr {
    border-bottom: 1px solid #e8ecf1;
}
.data-table tbody tr:hover {
    background: #f8fafc;
}
.data-table tbody td {
    padding: 12px;
    vertical-align: middle;
    color: #2d3748;
    font-size: 0.85rem;
}

/* Order ID */
.order-id {
    font-weight: 700;
    color: #667eea;
    font-size: 0.85rem;
}

/* Product Details */
.product-name {
    font-weight: 600;
    color: #2d3748;
    display: block;
    font-size: 0.85rem;
    margin-bottom: 2px;
}
.product-meta {
    font-size: 0.75rem;
    color: #718096;
}

/* Price */
.price-amount {
    font-weight: 700;
    color: #2d3748;
    font-size: 0.95rem;
}

/* Status Badges */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.status-active { background: #e8f8f5; color: #27ae60; }
.status-pending { background: #fef5e7; color: #f39c12; }
.status-rejected { background: #ffeaea; color: #e74c3c; }
.status-action { background: #e8f4fd; color: #3498db; }

/* Renewal Badge */
.renewal-badge {
    background: #fef5e7;
    color: #f39c12;
    padding: 3px 6px;
    border-radius: 5px;
    font-size: 0.65rem;
    font-weight: 600;
    display: inline-block;
    margin-top: 3px;
}
.renewal-badge.expiring-soon {
    background: #ffeaea;
    color: #e74c3c;
}

/* Action Buttons */
.btn-action {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-view {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-view:hover {
    color: white;
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
}
.btn-pay {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}
.btn-pay:hover {
    color: white;
    box-shadow: 0 3px 10px rgba(72, 187, 120, 0.3);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}
.empty-state i {
    font-size: 3rem;
    color: #cbd5e0;
    margin-bottom: 15px;
}
.empty-state h4 {
    color: #4a5568;
    margin-bottom: 8px;
    font-size: 1rem;
}
.empty-state p {
    color: #718096;
    margin-bottom: 15px;
    font-size: 0.85rem;
}

/* Mobile Card View */
.order-cards {
    display: none;
}
.order-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    border-left: 3px solid #667eea;
}
.order-card.status-1 { border-left-color: #48bb78; }
.order-card.status-0 { border-left-color: #f6ad55; }
.order-card.status-2 { border-left-color: #f56565; }
.order-card.status-3 { border-left-color: #4299e1; }

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.order-card-id {
    font-weight: 700;
    color: #667eea;
    font-size: 0.9rem;
}
.order-card-body {
    margin-bottom: 12px;
}
.order-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #e2e8f0;
}
.order-card-row:last-child {
    border-bottom: none;
}
.order-card-label {
    color: #718096;
    font-size: 0.75rem;
    font-weight: 500;
}
.order-card-value {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.8rem;
    text-align: right;
    max-width: 60%;
}
.order-card-actions {
    display: flex;
    gap: 8px;
}
.order-card-actions .btn-action {
    flex: 1;
    justify-content: center;
    padding: 8px 12px;
}

/* DataTable Override */
.dataTables_wrapper {
    font-size: 0.85rem;
}
.dataTables_length,
.dataTables_filter {
    margin-bottom: 15px;
}
.dataTables_length select,
.dataTables_filter input {
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.8rem;
}
.dataTables_info,
.dataTables_paginate {
    margin-top: 15px;
    font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 768px) {
    .orders-container {
        padding: 10px;
    }
    
    .table-header {
        padding: 12px 15px;
        flex-direction: column;
        align-items: stretch;
    }
    .table-header h4 {
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    .search-box {
        width: 100%;
    }
    
    /* Hide desktop table, show cards */
    .table-responsive {
        display: none;
    }
    .order-cards {
        display: block;
    }
    
    .table-body {
        padding: 12px;
    }
}

@media (max-width: 480px) {
    .order-card {
        padding: 12px;
        margin-bottom: 10px;
    }
    .order-card-id {
        font-size: 0.85rem;
    }
    .order-card-value {
        font-size: 0.75rem;
    }
    .order-card-actions .btn-action {
        padding: 7px 10px;
        font-size: 0.7rem;
    }
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-shopping-bag"></i> My Orders & Invoices</h1>
        </div>

        <div class="orders-container">
            <!-- Orders Table -->
            <div class="table-card">
                <div class="table-header">
                    <h4><i class="fas fa-list"></i> Active Orders</h4>
                    <form method="GET" class="search-box">
                        <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <div class="table-body">
                    <?php if(empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>No Orders Found</h4>
                            <p>You haven't placed any orders yet.</p>
                            <a href="../index.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-shopping-bag"></i> Browse Products
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Desktop Table View -->
                        <div class="table-responsive">
                            <table class="data-table" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th width="10%">Order ID</th>
                                        <th width="25%">Product Details</th>
                                        <th width="15%">Activation Date</th>
                                        <th width="15%">Renewal Date</th>
                                        <th width="10%">Price</th>
                                        <th width="10%">Status</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $order):
                                        $db->query("SELECT `products_data`, `renewal_date` FROM `invoices` WHERE `id` = '%d'", $order['invoice_id']);
                                        $invInfo = $db->getdata();
                                        $pdatas = $invInfo['products_data'];
                                        
                                        if (!empty($pdatas)):
                                            $totalpaid = totalPrice(getProducts($pdatas));
                                            $renewal_date = $invInfo['renewal_date'];
                                            $days_until_renewal = 0;
                                            if($renewal_date) {
                                                $days_until_renewal = ceil(($renewal_date - time()) / 86400);
                                            }
                                    ?>
                                    <tr>
                                        <td><span class="order-id">#<?= $order['invoice_id']; ?></span></td>
                                        <td>
                                            <span class="product-name"><?= productsNames($pdatas); ?></span>
                                            <span class="product-meta">
                                                <i class="fas fa-tag"></i> Invoice #<?= $order['invoice_id']; ?>
                                            </span>
                                        </td>
                                        <td><i class="far fa-calendar"></i> <?= date("d M Y", $order['date']); ?></td>
                                        <td>
                                            <?php if($renewal_date): ?>
                                                <div>
                                                    <i class="far fa-calendar-alt"></i> <?= date("d M Y", $renewal_date); ?>
                                                    <?php if($days_until_renewal > 0 && $days_until_renewal <= 7): ?>
                                                        <br><span class="renewal-badge expiring-soon">
                                                            <i class="fas fa-exclamation-triangle"></i> <?= $days_until_renewal; ?> days left
                                                        </span>
                                                    <?php elseif($days_until_renewal > 0 && $days_until_renewal <= 30): ?>
                                                        <br><span class="renewal-badge"><?= $days_until_renewal; ?> days left</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="price-amount">$<?= number_format($totalpaid, 2); ?></span></td>
                                        <td>
                                            <?php
                                            switch($order['status']) {
                                                case 0:
                                                    echo '<span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>';
                                                    break;
                                                case 1:
                                                    echo '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span>';
                                                    break;
                                                case 2:
                                                    echo '<span class="status-badge status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
                                                    break;
                                                case 3:
                                                    echo '<span class="status-badge status-action"><i class="fas fa-exclamation-circle"></i> Action Required</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($order['status'] == 1 || $order['status'] == 3): ?>
                                                <a href="view_order.php?id=<?= encrypt($order['id']); ?>" class="btn-action btn-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php elseif($order['status'] == 0): ?>
                                                <a href="invoice.php?invoice_id=<?= encrypt($order['invoice_id']); ?>" class="btn-action btn-pay">
                                                    <i class="fas fa-file-invoice"></i> Pay
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="order-cards">
                            <?php foreach($orders as $order):
                                $db->query("SELECT `products_data`, `renewal_date` FROM `invoices` WHERE `id` = '%d'", $order['invoice_id']);
                                $invInfo = $db->getdata();
                                $pdatas = $invInfo['products_data'];
                                
                                if (!empty($pdatas)):
                                    $totalpaid = totalPrice(getProducts($pdatas));
                                    $renewal_date = $invInfo['renewal_date'];
                                    $days_until_renewal = 0;
                                    if($renewal_date) {
                                        $days_until_renewal = ceil(($renewal_date - time()) / 86400);
                                    }
                            ?>
                            <div class="order-card status-<?= $order['status']; ?>">
                                <div class="order-card-header">
                                    <span class="order-card-id">#<?= $order['invoice_id']; ?></span>
                                    <?php
                                    switch($order['status']) {
                                        case 0:
                                            echo '<span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>';
                                            break;
                                        case 1:
                                            echo '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span>';
                                            break;
                                        case 2:
                                            echo '<span class="status-badge status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
                                            break;
                                        case 3:
                                            echo '<span class="status-badge status-action"><i class="fas fa-exclamation-circle"></i> Action</span>';
                                            break;
                                    }
                                    ?>
                                </div>
                                <div class="order-card-body">
                                    <div class="order-card-row">
                                        <span class="order-card-label">Product</span>
                                        <span class="order-card-value"><?= productsNames($pdatas); ?></span>
                                    </div>
                                    <div class="order-card-row">
                                        <span class="order-card-label">Activated</span>
                                        <span class="order-card-value"><?= date("d M Y", $order['date']); ?></span>
                                    </div>
                                    <div class="order-card-row">
                                        <span class="order-card-label">Renewal</span>
                                        <span class="order-card-value">
                                            <?php if($renewal_date): ?>
                                                <?= date("d M Y", $renewal_date); ?>
                                                <?php if($days_until_renewal > 0 && $days_until_renewal <= 7): ?>
                                                    <span class="renewal-badge expiring-soon"><?= $days_until_renewal; ?>d</span>
                                                <?php elseif($days_until_renewal > 0 && $days_until_renewal <= 30): ?>
                                                    <span class="renewal-badge"><?= $days_until_renewal; ?>d</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="order-card-row">
                                        <span class="order-card-label">Price</span>
                                        <span class="price-amount">$<?= number_format($totalpaid, 2); ?></span>
                                    </div>
                                </div>
                                <div class="order-card-actions">
                                    <?php if($order['status'] == 1 || $order['status'] == 3): ?>
                                        <a href="view_order.php?id=<?= encrypt($order['id']); ?>" class="btn-action btn-view">
                                            <i class="fas fa-eye"></i> View Order
                                        </a>
                                    <?php elseif($order['status'] == 0): ?>
                                        <a href="invoice.php?invoice_id=<?= encrypt($order['invoice_id']); ?>" class="btn-action btn-pay">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<br><br>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    // Only init DataTable on desktop
    if (window.innerWidth > 768) {
        if (!$.fn.DataTable.isDataTable('#ordersTable')) {
            $('#ordersTable').DataTable({
                "order": [[2, "desc"]],
                "pageLength": 25,
                "language": {
                    "search": "Filter:",
                    "lengthMenu": "Show _MENU_ orders",
                    "info": "Showing _START_ to _END_ of _TOTAL_ orders"
                }
            });
        }
    }
});
</script>