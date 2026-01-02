<?php
session_start();
include "../init.php";
if(!isLoggedIn())
{
  redirect("../index.php");
  exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'del' && !empty($_GET['id']))
{
 $id = decrypt($_GET['id']);
 $db->query("DELETE FROM `invoices` WHERE `id` = '%d' AND `user_id` = '%d'", $id, getUserData()['id']);
 if($db->affected_rows() > 0)
 {
    echo json_encode(array("message" => "Successfully Delete", "redirect" => "invoices.php" ));
  exit();
 }
 else
 {
    http_response_code(400);
    echo json_encode(array("This invoice does not exist." ));
  exit();
 }
}

$userId = getUserData()['id'];

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filters
$where_clause = "WHERE `user_id` = '$userId'";
if($filter_status != 'all') {
    $status_map = [
        'paid' => 1,
        'unpaid' => 0,
        'failed' => 2
    ];
    if(isset($status_map[$filter_status])) {
        $where_clause .= " AND `status` = '{$status_map[$filter_status]}'";
    }
}

$db->query("SELECT * FROM `invoices` $where_clause ORDER BY `date` DESC");
$invoices = $db->getall();

// Calculate statistics
$db->query("SELECT COUNT(*) as total FROM `invoices` WHERE `user_id` = '%d'", $userId);
$total_invoices = $db->getdata()['total'];

$db->query("SELECT COUNT(*) as total FROM `invoices` WHERE `user_id` = '%d' AND `status` = 1", $userId);
$paid_count = $db->getdata()['total'];

$db->query("SELECT COUNT(*) as total FROM `invoices` WHERE `user_id` = '%d' AND `status` = 0", $userId);
$unpaid_count = $db->getdata()['total'];

include "header.php";
?>

<style>
/* Page Container */
.invoices-container {
    padding: 15px;
}

/* Stats Grid */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

/* Stat Card - Compact */
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-left: 3px solid;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.stat-card.primary { border-left-color: #667eea; }
.stat-card.success { border-left-color: #48bb78; }
.stat-card.warning { border-left-color: #f6ad55; }

.stat-content {
    flex: 1;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1.2;
}
.stat-label {
    color: #718096;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
}
.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    margin-left: 10px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.warning { background: rgba(246, 173, 85, 0.1); color: #f6ad55; }

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
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.table-header h4 {
    font-size: 1rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Filter Tabs */
.filter-wrapper {
    padding: 0 15px;
    margin: 15px 0;
}
.filter-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    background: #f7fafc;
    padding: 5px;
    border-radius: 8px;
}
.filter-tab {
    padding: 8px 10px;
    background: transparent;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.8rem;
    color: #718096;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    text-align: center;
}
.filter-tab:hover {
    background: rgba(255,255,255,0.5);
    color: #4a5568;
    text-decoration: none;
}
.filter-tab.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
.filter-tab .tab-text {
    display: inline;
}
.filter-tab .count {
    background: #e2e8f0;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
}
.filter-tab.active .count {
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
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

/* Order/Invoice ID */
.order-id {
    font-weight: 700;
    color: #667eea;
    font-size: 0.85rem;
}

/* Product/Description */
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

/* Invoice Type Badges */
.invoice-type {
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.type-product { background: #e8f4fd; color: #3498db; }
.type-recharge { background: #fef5e7; color: #f39c12; }
.type-extend { background: #f3e8ff; color: #8b5cf6; }

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
.status-paid { background: #e8f8f5; color: #27ae60; }
.status-unpaid { background: #fef5e7; color: #f39c12; }
.status-failed { background: #ffeaea; color: #e74c3c; }

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
.btn-pay {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}
.btn-pay:hover {
    color: white;
    box-shadow: 0 3px 10px rgba(72, 187, 120, 0.3);
}
.btn-view {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.btn-view:hover {
    color: white;
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
}
.btn-delete {
    background: linear-gradient(135deg, #f56565 0%, #e74c3c 100%);
    color: white;
    margin-left: 5px;
}
.btn-delete:hover {
    color: white;
    box-shadow: 0 3px 10px rgba(245, 101, 101, 0.3);
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
.invoice-cards {
    display: none;
}
.invoice-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    border-left: 3px solid #667eea;
}
.invoice-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.invoice-card-id {
    font-weight: 700;
    color: #667eea;
    font-size: 0.9rem;
}
.invoice-card-body {
    margin-bottom: 12px;
}
.invoice-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px dashed #e2e8f0;
}
.invoice-card-row:last-child {
    border-bottom: none;
}
.invoice-card-label {
    color: #718096;
    font-size: 0.75rem;
    font-weight: 500;
}
.invoice-card-value {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.8rem;
}
.invoice-card-actions {
    display: flex;
    gap: 8px;
}
.invoice-card-actions .btn-action {
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
.paginate_button {
    padding: 5px 10px !important;
    margin: 0 2px !important;
    border-radius: 5px !important;
}

/* Responsive */
@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .invoices-container {
        padding: 10px;
    }
    
    .stats-row {
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .stat-card {
        padding: 12px;
        flex-direction: column-reverse;
        align-items: flex-start;
        gap: 8px;
    }
    .stat-icon {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
        margin-left: 0;
    }
    .stat-value {
        font-size: 1.3rem;
    }
    .stat-label {
        font-size: 0.6rem;
    }
    
    .table-header {
        padding: 12px 15px;
    }
    .table-header h4 {
        font-size: 0.9rem;
    }
    
    .filter-wrapper {
        padding: 0 12px;
        margin: 12px 0;
    }
    .filter-tabs {
        grid-template-columns: repeat(4, 1fr);
        gap: 5px;
        padding: 4px;
    }
    .filter-tab {
        padding: 6px 4px;
        font-size: 0.7rem;
        flex-direction: column;
        gap: 2px;
    }
    .filter-tab i {
        font-size: 0.75rem;
    }
    .filter-tab .count {
        font-size: 0.6rem;
        padding: 1px 4px;
    }
    
    /* Hide desktop table, show cards */
    .table-responsive {
        display: none;
    }
    .invoice-cards {
        display: block;
    }
    
    .table-body {
        padding: 12px;
    }
    
    /* DataTable mobile */
    .dataTables_length,
    .dataTables_filter {
        width: 100%;
        text-align: left !important;
        margin-bottom: 10px;
    }
    .dataTables_filter input {
        width: 100% !important;
        margin-left: 0 !important;
        margin-top: 5px;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .stat-card {
        padding: 10px;
    }
    .stat-value {
        font-size: 1.1rem;
    }
    .stat-icon {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    
    .filter-tabs {
        gap: 4px;
        padding: 3px;
    }
    .filter-tab {
        padding: 8px 5px;
        font-size: 0.7rem;
        gap: 3px;
    }
    .filter-tab i {
        font-size: 0.8rem;
    }
    .filter-tab .tab-text {
        display: none;
    }
    .filter-tab .count {
        font-size: 0.65rem;
        padding: 2px 5px;
    }
    
    .invoice-card {
        padding: 12px;
        margin-bottom: 10px;
    }
    .invoice-card-id {
        font-size: 0.85rem;
    }
    .invoice-card-actions .btn-action {
        padding: 7px 10px;
        font-size: 0.7rem;
    }
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-file-invoice-dollar"></i> My Invoices</h1>
        </div>

        <div class="invoices-container">
            <!-- Statistics Cards -->
            <div class="stats-row" style="display:none;">
                <div class="stat-card primary">
                    <div class="stat-content">
                        <div class="stat-value"><?= $total_invoices; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-content">
                        <div class="stat-value"><?= $paid_count; ?></div>
                        <div class="stat-label">Paid</div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-content">
                        <div class="stat-value"><?= $unpaid_count; ?></div>
                        <div class="stat-label">Unpaid</div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="table-card">
                <div class="table-header">
                    <h4><i class="fas fa-list"></i> All Invoices</h4>
                    <?php if($unpaid_count > 0): ?>
                    <a href="?status=unpaid" class="btn btn-warning btn-sm" style="font-size: 0.75rem; padding: 5px 10px;">
                        <i class="fas fa-exclamation-triangle"></i> <?= $unpaid_count; ?> Unpaid
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Filter Tabs -->
                <div class="filter-wrapper">
                    <div class="filter-tabs">
                        <a href="?status=all" class="filter-tab <?= $filter_status == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i>
                            <span class="tab-text">All</span>
                            <span class="count"><?= $total_invoices; ?></span>
                        </a>
                        <a href="?status=paid" class="filter-tab <?= $filter_status == 'paid' ? 'active' : ''; ?>">
                            <i class="fas fa-check"></i>
                            <span class="tab-text">Paid</span>
                            <span class="count"><?= $paid_count; ?></span>
                        </a>
                        <a href="?status=unpaid" class="filter-tab <?= $filter_status == 'unpaid' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <span class="tab-text">Unpaid</span>
                            <span class="count"><?= $unpaid_count; ?></span>
                        </a>
                        <a href="?status=failed" class="filter-tab <?= $filter_status == 'failed' ? 'active' : ''; ?>">
                            <i class="fas fa-times"></i>
                            <span class="tab-text">Failed</span>
                        </a>
                    </div>
                </div>

                <div class="table-body">
                    <?php if(empty($invoices)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <h4>No Invoices Found</h4>
                            <p>You don't have any invoices yet.</p>
                            <a href="../index.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-shopping-bag"></i> Browse Products
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Desktop Table View -->
                        <div class="table-responsive">
                            <table class="data-table" id="invoicesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($invoices as $invoice):
                                        // Determine invoice type and amount
                                        $invoice_type = '';
                                        $invoice_amount = 0;
                                        $invoice_description = '';
                                        
                                        if (!empty($invoice['recharge_data'])) {
                                            $invoice_type = 'recharge';
                                            $recharge = json_decode($invoice['recharge_data'], true);
                                            $invoice_amount = $recharge['ammount'] ?? 0;
                                            $invoice_description = 'Account Recharge';
                                        } elseif (!empty($invoice['extend_data'])) {
                                            $invoice_type = 'extend';
                                            $extend = json_decode($invoice['extend_data'], true);
                                            $costs = [1 => 40, 2 => 120, 3 => 200, 4 => 400];
                                            $invoice_amount = $costs[$extend['extend_id']] ?? 0;
                                            $invoice_description = 'License Extension';
                                        } else {
                                            $invoice_type = 'product';
                                            $invoice_amount = totalPrice(getProducts($invoice['products_data']));
                                            $invoice_description = productsNames($invoice['products_data']);
                                            $product_count = count(json_decode($invoice['products_data'], true) ?? []);
                                        }
                                    ?>
                                    <tr>
                                        <td><span class="order-id">#<?= $invoice['id']; ?></span></td>
                                        <td>
                                            <span class="product-name"><?= htmlspecialchars($invoice_description); ?></span>
                                            <?php if($invoice_type == 'product' && isset($product_count) && $product_count > 0): ?>
                                                <span class="product-meta"><i class="fas fa-box"></i> <?= $product_count; ?> item(s)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($invoice_type == 'recharge'): ?>
                                                <span class="invoice-type type-recharge"><i class="fas fa-wallet"></i> Recharge</span>
                                            <?php elseif($invoice_type == 'extend'): ?>
                                                <span class="invoice-type type-extend"><i class="fas fa-clock"></i> Extension</span>
                                            <?php else: ?>
                                                <span class="invoice-type type-product"><i class="fas fa-box"></i> Product</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date("d M Y", $invoice['date']); ?>
                                            <br><small class="text-muted"><?= date("h:i A", $invoice['date']); ?></small>
                                        </td>
                                        <td><span class="price-amount">$<?= number_format($invoice_amount, 2); ?></span></td>
                                        <td>
                                            <?php
                                            switch($invoice['status']) {
                                                case 0:
                                                    echo '<span class="status-badge status-unpaid"><i class="fas fa-clock"></i> Unpaid</span>';
                                                    break;
                                                case 1:
                                                    echo '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i> Paid</span>';
                                                    break;
                                                case 2:
                                                    echo '<span class="status-badge status-failed"><i class="fas fa-times-circle"></i> Failed</span>';
                                                    break;
                                                default:
                                                    echo '<span class="status-badge">Unknown</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($invoice['status'] == 0): ?>
                                                <a href="invoice.php?invoice_id=<?= encrypt($invoice['id']); ?>" class="btn-action btn-pay">
                                                    <i class="fas fa-credit-card"></i> Pay
                                                </a>
                                                <a href="javascript:void(0);" class="btn-action btn-delete delete-confirm" 
                                                   data-action="invoices.php?action=del&id=<?= encrypt($invoice['id']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php elseif($invoice['status'] == 1): ?>
                                                <a href="products.php" class="btn-action btn-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="invoice-cards">
                            <?php foreach($invoices as $invoice):
                                // Determine invoice type and amount
                                $invoice_type = '';
                                $invoice_amount = 0;
                                $invoice_description = '';
                                
                                if (!empty($invoice['recharge_data'])) {
                                    $invoice_type = 'recharge';
                                    $recharge = json_decode($invoice['recharge_data'], true);
                                    $invoice_amount = $recharge['ammount'] ?? 0;
                                    $invoice_description = 'Account Recharge';
                                } elseif (!empty($invoice['extend_data'])) {
                                    $invoice_type = 'extend';
                                    $extend = json_decode($invoice['extend_data'], true);
                                    $costs = [1 => 40, 2 => 120, 3 => 200, 4 => 400];
                                    $invoice_amount = $costs[$extend['extend_id']] ?? 0;
                                    $invoice_description = 'License Extension';
                                } else {
                                    $invoice_type = 'product';
                                    $invoice_amount = totalPrice(getProducts($invoice['products_data']));
                                    $invoice_description = productsNames($invoice['products_data']);
                                }
                            ?>
                            <div class="invoice-card">
                                <div class="invoice-card-header">
                                    <span class="invoice-card-id">#<?= $invoice['id']; ?></span>
                                    <?php
                                    switch($invoice['status']) {
                                        case 0:
                                            echo '<span class="status-badge status-unpaid"><i class="fas fa-clock"></i> Unpaid</span>';
                                            break;
                                        case 1:
                                            echo '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i> Paid</span>';
                                            break;
                                        case 2:
                                            echo '<span class="status-badge status-failed"><i class="fas fa-times-circle"></i> Failed</span>';
                                            break;
                                    }
                                    ?>
                                </div>
                                <div class="invoice-card-body">
                                    <div class="invoice-card-row">
                                        <span class="invoice-card-label">Description</span>
                                        <span class="invoice-card-value"><?= htmlspecialchars($invoice_description); ?></span>
                                    </div>
                                    <div class="invoice-card-row">
                                        <span class="invoice-card-label">Type</span>
                                        <?php if($invoice_type == 'recharge'): ?>
                                            <span class="invoice-type type-recharge"><i class="fas fa-wallet"></i> Recharge</span>
                                        <?php elseif($invoice_type == 'extend'): ?>
                                            <span class="invoice-type type-extend"><i class="fas fa-clock"></i> Extension</span>
                                        <?php else: ?>
                                            <span class="invoice-type type-product"><i class="fas fa-box"></i> Product</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="invoice-card-row">
                                        <span class="invoice-card-label">Date</span>
                                        <span class="invoice-card-value"><?= date("d M Y, h:i A", $invoice['date']); ?></span>
                                    </div>
                                    <div class="invoice-card-row">
                                        <span class="invoice-card-label">Amount</span>
                                        <span class="price-amount">$<?= number_format($invoice_amount, 2); ?></span>
                                    </div>
                                </div>
                                <div class="invoice-card-actions">
                                    <?php if($invoice['status'] == 0): ?>
                                        <a href="invoice.php?invoice_id=<?= encrypt($invoice['id']); ?>" class="btn-action btn-pay">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                        <a href="javascript:void(0);" class="btn-action btn-delete delete-confirm" 
                                           data-action="invoices.php?action=del&id=<?= encrypt($invoice['id']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php elseif($invoice['status'] == 1): ?>
                                        <a href="products.php" class="btn-action btn-view">
                                            <i class="fas fa-eye"></i> View Order
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    // Only init DataTable on desktop
    if (window.innerWidth > 768) {
        if (!$.fn.DataTable.isDataTable('#invoicesTable')) {
            $('#invoicesTable').DataTable({
                "order": [[3, "desc"]],
                "pageLength": 25,
                "language": {
                    "search": "Filter:",
                    "lengthMenu": "Show _MENU_",
                    "info": "Showing _START_-_END_ of _TOTAL_"
                }
            });
        }
    }
    
    // Delete confirmation
    $('.delete-confirm').on('click', function() {
        var action = $(this).data('action');
        Swal.fire({
            title: 'Delete Invoice?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = action;
            }
        });
    });
});
</script>