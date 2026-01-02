<?php
session_start();
include "../init.php";
if(!isLoggedIn()) {
    redirect("../index.php");
    exit();
}

if(empty(intval(settingsInfo("REFERRAL_COMMISSION")))) {
    redirect("./index.php");
    exit();
}

$userId = getUserData()['id'];
$userRef = getUserData();

// Get available products for sharing
$db->query("SELECT id, product_name, slug FROM `products` WHERE `show` = '1' ORDER BY `sort` ASC");
$products = $db->getall();

// Get referrals
$db->query("SELECT * FROM `users` WHERE `ref_by` = '%d' ORDER BY date DESC", $userId);
$refs = $db->getall();

// Get confirmed balance
$confirmedBalance = floatval($userRef['ref_bonus']);

// Get pending balance
$pendingBalance = floatval($userRef['ref_pending_bonus']);

// Get recent transactions
$db->query("SELECT * FROM `referral_transactions` WHERE `user_id` = '%d' ORDER BY created_at DESC LIMIT 10", $userId);
$recentTransactions = $db->getall();

// Get withdrawal requests
$db->query("SELECT * FROM `withdrawal_requests` WHERE `user_id` = '%d' ORDER BY requested_at DESC LIMIT 5", $userId);
$withdrawalRequests = $db->getall();

// Calculate stats
$totalReferrals = count($refs);
$totalEarned = $confirmedBalance + $pendingBalance;

// Handle withdrawal request
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = floatval($_POST['amount']);
    $type = $_POST['withdrawal_type'];
    $account = $_POST['account_details'];
    
    if($amount >= 50 && $amount <= $confirmedBalance) {
        $db->query("INSERT INTO `withdrawal_requests` (user_id, amount, withdrawal_type, account_details) 
                    VALUES ('%d', '%s', '%s', '%s')", $userId, $amount, $type, $account);
        
        $db->query("UPDATE `users` SET `ref_bonus` = ref_bonus - '%s', `ref_pending_bonus` = ref_pending_bonus + '%s' WHERE `id` = '%d'", 
                   $amount, $amount, $userId);
        
        $db->query("INSERT INTO `referral_transactions` (user_id, type, amount, description, status) 
                    VALUES ('%d', 'withdrawal', '%s', 'Withdrawal request submitted', 'pending')", 
                    $userId, $amount);
        
        $successMsg = "Withdrawal request submitted successfully!";
    } else {
        $errorMsg = "Invalid withdrawal amount. Minimum is $50 and must not exceed available balance.";
    }
}

// Handle conversion to store credit
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['convert_to_credit'])) {
    $convertAmount = floatval($_POST['convert_amount']);
    
    if($convertAmount > 0 && $convertAmount <= $confirmedBalance) {
        $db->query("UPDATE `users` SET `ref_bonus` = ref_bonus - '%s', `credits` = credits + '%s' WHERE `id` = '%d'", 
                   $convertAmount, $convertAmount, $userId);
        
        saveCreditsLogs($userId, $convertAmount, '+', 'Fund added from earned bonus');
        
        $db->query("INSERT INTO `referral_transactions` (user_id, type, amount, description, status) 
                    VALUES ('%d', 'conversion', '%s', 'Converted to store credits', 'confirmed')", 
                    $userId, $convertAmount);
        
        $successMsg = "Successfully converted $" . $convertAmount . " to store credits!";
        header("Refresh:2");
    } else {
        $errorMsg = "Invalid conversion amount.";
    }
}

include "header.php";
?>

<style>
/* Page Container */
.referral-container {
    padding: 15px;
}

/* Referral Link Section */
.ref-link-container {
    background: linear-gradient(135deg, #1c76d5 0%, #0b6ed8 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.ref-link-container h3 {
    font-size: 1.1rem;
    margin-bottom: 5px;
}
.ref-link-container > p {
    font-size: 0.85rem;
    margin-bottom: 15px;
    opacity: 0.95;
}
.product-selector {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
}
.product-selector label {
    font-size: 0.8rem;
    margin-bottom: 6px;
    display: block;
}
.product-select {
    width: 100%;
    padding: 10px 12px;
    border: none;
    border-radius: 8px;
    background: white;
    color: #2d3748;
    font-weight: 500;
    font-size: 0.85rem;
}
.ref-link-box {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    padding: 12px;
}
.link-type-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.25);
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 8px;
}
.ref-link-wrapper {
    display: flex;
    gap: 8px;
}
.ref-link-input {
    flex: 1;
    background: white;
    border: none;
    padding: 10px 12px;
    border-radius: 8px;
    color: #2d3748;
    font-size: 0.8rem;
    min-width: 0;
}
.copy-btn {
    background: white;
    color: #159b34;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    white-space: nowrap;
    cursor: pointer;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    border-left: 3px solid;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.stat-card.primary { border-left-color: #667eea; }
.stat-card.success { border-left-color: #48bb78; }
.stat-card.warning { border-left-color: #f6ad55; }
.stat-card.info { border-left-color: #4299e1; }

.stat-content { flex: 1; }
.stat-value {
    font-size: 1.4rem;
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
    margin-left: 10px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.warning { background: rgba(246, 173, 85, 0.1); color: #f6ad55; }
.stat-icon.info { background: rgba(66, 153, 225, 0.1); color: #4299e1; }

/* Balance Cards Row */
.balance-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}
.balance-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.balance-card h4 {
    font-size: 0.95rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.balance-amount {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 15px;
}
.confirmed-amount { color: #48bb78; }

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.action-btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s;
}
.btn-withdraw {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}
.btn-convert {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: white;
}
.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Tabs */
.content-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 20px;
}
.custom-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #f7fafc;
    padding: 5px;
    gap: 5px;
}
.custom-tab {
    padding: 12px 15px;
    border: none;
    background: transparent;
    color: #718096;
    font-weight: 600;
    font-size: 0.85rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.custom-tab:hover {
    color: #4a5568;
}
.custom-tab.active {
    background: #4c85cc;
    color: white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.tab-content {
    padding: 20px;
}
.tab-pane {
    display: none;
}
.tab-pane.active {
    display: block;
}

/* Table Styles */
.data-table {
    width: 100%;
}
.data-table thead th {
    background: #f8fafc;
    color: #4a5568;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    padding: 10px 12px;
    border: none;
}
.data-table tbody tr {
    border-bottom: 1px solid #e8ecf1;
}
.data-table tbody td {
    padding: 12px;
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Transaction Items */
.transaction-item {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 12px;
    background: #f8fafc;
    border-left: 3px solid;
}
.transaction-earning { border-left-color: #48bb78; }
.transaction-withdrawal { border-left-color: #f56565; }
.transaction-conversion { border-left-color: #ed8936; }

.transaction-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}
.transaction-title {
    font-weight: 600;
    font-size: 0.85rem;
}
.transaction-amount {
    font-weight: 700;
    font-size: 0.9rem;
}
.transaction-desc {
    font-size: 0.75rem;
    color: #718096;
}
.transaction-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

/* Status Badges */
.status-badge {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
}
.status-pending { background: #fef5e7; color: #f39c12; }
.status-confirmed { background: #e8f8f5; color: #27ae60; }
.status-completed { background: #e8f4fd; color: #3498db; }

/* How It Works */
.how-it-works {
    background: #f0f0f0;
    border-radius: 12px;
    padding: 25px;
    margin-top: 20px;
}
.how-it-works h3 {
    font-size: 1.1rem;
    text-align: center;
    margin-bottom: 20px;
}
.steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}
.step-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
}
.step-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.step-number {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 auto 12px;
}
.step-card h5 {
    font-size: 0.9rem;
    margin-bottom: 8px;
}
.step-card p {
    font-size: 0.8rem;
    color: #718096;
    margin-bottom: 10px;
}
.step-card i {
    font-size: 1.5rem;
    opacity: 0.6;
}

.bonus-badges {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}
.bonus-badge {
    background: linear-gradient(135deg, #ae3dbc 0%, #f5576c 100%);
    color: white;
    padding: 10px 18px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Mobile Referral Cards */
.referral-cards {
    display: none;
}
.referral-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    border-left: 3px solid #667eea;
}
.referral-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.referral-user {
    display: flex;
    align-items: center;
    gap: 10px;
}
.referral-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
}
.referral-name {
    font-weight: 600;
    font-size: 0.85rem;
}
.referral-id {
    font-size: 0.7rem;
    color: #718096;
}
.referral-card-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.referral-stat {
    text-align: center;
    padding: 8px;
    background: white;
    border-radius: 6px;
}
.referral-stat-label {
    font-size: 0.65rem;
    color: #718096;
    text-transform: uppercase;
}
.referral-stat-value {
    font-weight: 600;
    font-size: 0.85rem;
}

/* Withdrawal Table */
.withdrawal-table {
    width: 100%;
}
.withdrawal-table th {
    font-size: 0.7rem;
    padding: 8px;
    background: #f8fafc;
}
.withdrawal-table td {
    font-size: 0.8rem;
    padding: 10px 8px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 30px 15px;
}
.empty-state i {
    font-size: 2.5rem;
    color: #cbd5e0;
    margin-bottom: 10px;
}
.empty-state h5 {
    font-size: 0.95rem;
    color: #4a5568;
    margin-bottom: 5px;
}
.empty-state p {
    font-size: 0.8rem;
    color: #718096;
}

/* Responsive */
@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .steps-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .referral-container {
        padding: 10px;
    }
    
    .ref-link-container {
        padding: 15px;
    }
    .ref-link-container h3 {
        font-size: 1rem;
    }
    .ref-link-container > p {
        font-size: 0.8rem;
    }
    .product-selector,
    .ref-link-box {
        padding: 10px;
    }
    .ref-link-wrapper {
        flex-direction: column;
    }
    .copy-btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
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
        font-size: 1.2rem;
    }
    .stat-label {
        font-size: 0.6rem;
    }
    
    .balance-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .balance-card {
        padding: 15px;
    }
    .balance-card h4 {
        font-size: 0.9rem;
    }
    .balance-amount {
        font-size: 1.5rem;
    }
    .action-btn {
        padding: 8px 12px;
        font-size: 0.75rem;
        flex: 1;
    }
    
    .custom-tabs {
        padding: 4px;
    }
    .custom-tab {
        padding: 10px;
        font-size: 0.8rem;
    }
    .tab-content {
        padding: 15px;
    }
    
    /* Hide table, show cards */
    .table-responsive {
        display: none;
    }
    .referral-cards {
        display: block;
    }
    
    .transaction-item {
        padding: 12px;
    }
    
    .steps-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .step-card {
        padding: 15px;
    }
    .step-number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .how-it-works {
        padding: 20px 15px;
    }
    .how-it-works h3 {
        font-size: 1rem;
    }
    
    .bonus-badges {
        flex-direction: column;
        align-items: center;
    }
    .bonus-badge {
        font-size: 0.75rem;
        padding: 8px 15px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
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
    
    .referral-card {
        padding: 12px;
    }
    .referral-avatar {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
    }
    .referral-card-body {
        gap: 8px;
    }
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-hand-holding-usd"></i> Affiliate Program</h1>
        </div>

        <div class="referral-container">
            <?php if(isset($successMsg)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= $successMsg; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>

            <?php if(isset($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= $errorMsg; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>


            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-content">
                        <div class="stat-value"><?= $totalReferrals; ?></div>
                        <div class="stat-label">Referrals</div>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-content">
                        <div class="stat-value">$<?= number_format($confirmedBalance, 2); ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-content">
                        <div class="stat-value">$<?= number_format($pendingBalance, 2); ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-content">
                        <div class="stat-value">$<?= number_format($totalEarned, 2); ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>



            <!-- Referral Link Section -->
            <div class="ref-link-container">
                <h3><i class="fas fa-link"></i> Share & Earn Commission</h3>
                <p>Invite friends and earn <strong>10%</strong> commission! Your friends get <strong>5% discount</strong> on their first purchase!</p>
                
                <div class="product-selector">
                    <label><i class="fas fa-box"></i> Select Product to Share:</label>
                    <select class="product-select" id="productSelector" onchange="updateRefLink()">
                        <option value="general">🌐 General Store Link</option>
                        <?php foreach($products as $product): ?>
                        <option value="<?= $product['slug']; ?>">📦 <?= $product['product_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ref-link-box">
                    <span class="link-type-badge" id="linkTypeBadge">
                        <i class="fas fa-globe"></i> General Link
                    </span>
                    <div class="ref-link-wrapper">
                        <input type="text" class="ref-link-input" id="refLink" value="<?= myRefLink(encrypt($userId)); ?>" readonly>
                        <button class="copy-btn" onclick="copyRefLink()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Balance & Withdrawals Row -->
            <div class="balance-row">
                <div class="balance-card">
                    <h4><i class="fas fa-wallet"></i> Balance Management</h4>
                    <p class="text-muted mb-1" style="font-size: 0.8rem;">Available for Withdrawal</p>
                    <p class="balance-amount confirmed-amount">$<?= number_format($confirmedBalance, 2); ?></p>
                    <div class="action-buttons">
                        <button class="action-btn btn-withdraw" data-toggle="modal" data-target="#withdrawModal" 
                                <?= $confirmedBalance < 50 ? 'disabled' : ''; ?>>
                            <i class="fas fa-money-bill-wave"></i> Withdraw
                        </button>
                        <button class="action-btn btn-convert" data-toggle="modal" data-target="#convertModal"
                                <?= $confirmedBalance <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-exchange-alt"></i> Convert
                        </button>
                    </div>
                    <?php if($confirmedBalance < 50): ?>
                    <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle"></i> Min withdrawal: $50
                    </small>
                    <?php endif; ?>
                </div>
                
                <div class="balance-card">
                    <h4><i class="fas fa-history"></i> Recent Withdrawals</h4>
                    <?php if(empty($withdrawalRequests)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="text-muted mb-0">No withdrawal requests yet</p>
                        </div>
                    <?php else: ?>
                        <table class="withdrawal-table">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($withdrawalRequests as $wr): ?>
                                <tr>
                                    <td><strong>$<?= number_format($wr['amount'], 2); ?></strong></td>
                                    <td><?= ucfirst($wr['withdrawal_type']); ?></td>
                                    <td><span class="status-badge status-<?= $wr['status']; ?>"><?= ucfirst($wr['status']); ?></span></td>
                                    <td><?= date("d M", strtotime($wr['requested_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabs Content -->
            <div class="content-card">
                <div class="custom-tabs">
                    <button class="custom-tab active" onclick="switchTab('referrals', this)">
                        <i class="fas fa-users"></i> My Referrals
                    </button>
                    <button class="custom-tab" onclick="switchTab('transactions', this)">
                        <i class="fas fa-list-alt"></i> Transactions
                    </button>
                </div>

                <div class="tab-content">
                    <!-- Referrals Tab -->
                    <div class="tab-pane active" id="referrals">
                        <?php if(empty($refs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-friends"></i>
                                <h5>No Referrals Yet</h5>
                                <p>Share your referral link to start earning!</p>
                            </div>
                        <?php else: ?>
                            <!-- Desktop Table -->
                            <div class="table-responsive">
                                <table class="data-table datatables">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Joined</th>
                                            <th>Orders</th>
                                            <th>Earnings</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($refs as $idx => $ref): 
                                            $orders = totalOrdersOfRef($ref['id'], $ref['date']);
                                            $earnings = getMyComission($orders['invoices']);
                                        ?>
                                        <tr>
                                            <td><?= $idx + 1; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="referral-avatar mr-2">
                                                        <?= strtoupper(substr(userNameByID($ref['id']), 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong style="font-size: 0.85rem;"><?= scrambleEmail(userNameByID($ref['id'])); ?></strong>
                                                        <br><small class="text-muted">ID: #<?= $ref['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= date("d M Y", $ref['date']); ?></td>
                                            <td><span class="badge badge-info"><?= $orders['total']; ?> orders</span></td>
                                            <td><strong class="text-success">$<?= number_format($earnings, 2); ?></strong></td>
                                            <td>
                                                <?php if($ref['is_banned'] == '1'): ?>
                                                    <span class="badge badge-danger">Banned</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Cards -->
                            <div class="referral-cards">
                                <?php foreach($refs as $ref): 
                                    $orders = totalOrdersOfRef($ref['id'], $ref['date']);
                                    $earnings = getMyComission($orders['invoices']);
                                ?>
                                <div class="referral-card">
                                    <div class="referral-card-header">
                                        <div class="referral-user">
                                            <div class="referral-avatar">
                                                <?= strtoupper(substr(userNameByID($ref['id']), 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="referral-name"><?= scrambleEmail(userNameByID($ref['id'])); ?></div>
                                                <div class="referral-id">ID: #<?= $ref['id']; ?></div>
                                            </div>
                                        </div>
                                        <?php if($ref['is_banned'] == '1'): ?>
                                            <span class="badge badge-danger">Banned</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="referral-card-body">
                                        <div class="referral-stat">
                                            <div class="referral-stat-label">Joined</div>
                                            <div class="referral-stat-value"><?= date("d M Y", $ref['date']); ?></div>
                                        </div>
                                        <div class="referral-stat">
                                            <div class="referral-stat-label">Orders</div>
                                            <div class="referral-stat-value"><?= $orders['total']; ?></div>
                                        </div>
                                        <div class="referral-stat" style="grid-column: span 2;">
                                            <div class="referral-stat-label">Your Earnings</div>
                                            <div class="referral-stat-value text-success">$<?= number_format($earnings, 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Transactions Tab -->
                    <div class="tab-pane" id="transactions">
                        <?php if(empty($recentTransactions)): ?>
                            <div class="empty-state">
                                <i class="fas fa-receipt"></i>
                                <h5>No Transactions Yet</h5>
                                <p>Your transaction history will appear here.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($recentTransactions as $trans): ?>
                            <div class="transaction-item transaction-<?= $trans['type']; ?>">
                                <div class="transaction-header">
                                    <div>
                                        <div class="transaction-title">
                                            <?php if($trans['type'] == 'earning'): ?>
                                                <i class="fas fa-plus-circle text-success"></i> Commission Earned
                                            <?php elseif($trans['type'] == 'withdrawal'): ?>
                                                <i class="fas fa-minus-circle text-danger"></i> Withdrawal
                                            <?php else: ?>
                                                <i class="fas fa-exchange-alt text-warning"></i> Converted
                                            <?php endif; ?>
                                        </div>
                                        <div class="transaction-desc"><?= $trans['description']; ?></div>
                                    </div>
                                    <div class="transaction-amount <?= $trans['type'] == 'earning' ? 'text-success' : 'text-danger'; ?>">
                                        <?= $trans['type'] == 'earning' ? '+' : '-'; ?>$<?= number_format($trans['amount'], 2); ?>
                                    </div>
                                </div>
                                <div class="transaction-footer">
                                    <span class="status-badge status-<?= $trans['status']; ?>">
                                        <?= ucfirst($trans['status']); ?>
                                    </span>
                                    <small class="text-muted"><?= date("d M Y", strtotime($trans['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- How It Works -->
            <div class="how-it-works">
                <h3><i class="fas fa-info-circle"></i> How It Works</h3>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5>Share Your Link</h5>
                        <p>Share your unique referral link with friends and on social media.</p>
                        <i class="fas fa-share-alt text-primary"></i>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5>They Sign Up & Save</h5>
                        <p>Friends register and get 5% off their first purchase.</p>
                        <i class="fas fa-user-plus text-success"></i>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5>You Earn Commission</h5>
                        <p>Earn 10% commission on every purchase they make.</p>
                        <i class="fas fa-money-bill-wave text-warning"></i>
                    </div>
                </div>
                
                <div class="bonus-badges">
                    <div class="bonus-badge">
                        <i class="fas fa-percentage"></i> You Earn 10% Commission
                    </div>
                    <div class="bonus-badge">
                        <i class="fas fa-tag"></i> Friends Get 5% Discount
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i> Request Withdrawal</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-info" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle"></i> Processing time: 3-5 business days
                    </div>
                    <div class="form-group">
                        <label>Available Balance</label>
                        <input type="text" class="form-control" value="$<?= number_format($confirmedBalance, 2); ?>" readonly style="font-weight: 600;">
                    </div>
                    <div class="form-group">
                        <label>Withdrawal Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" name="amount" class="form-control" min="50" max="<?= $confirmedBalance; ?>" step="0.01" required>
                        </div>
                        <small class="text-muted">Min: $50 | Max: $<?= number_format($confirmedBalance, 2); ?></small>
                    </div>
                    <div class="form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select name="withdrawal_type" class="form-control" required>
                            <option value="">Select Method</option>
                            <option value="paypal">PayPal</option>
                            <option value="crypto">Cryptocurrency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Details <span class="text-danger">*</span></label>
                        <textarea name="account_details" class="form-control" rows="3" 
                                  placeholder="PayPal email or Crypto wallet address" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="request_withdrawal" class="btn btn-success btn-sm">
                        <i class="fas fa-check"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Convert Modal -->
<div class="modal fade" id="convertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Convert to Credits</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-warning" style="font-size: 0.85rem;">
                        <i class="fas fa-exclamation-triangle"></i> This cannot be reversed. Credits cannot be withdrawn as cash.
                    </div>
                    <div class="form-group">
                        <label>Available Balance</label>
                        <input type="text" class="form-control" value="$<?= number_format($confirmedBalance, 2); ?>" readonly style="font-weight: 600;">
                    </div>
                    <div class="form-group">
                        <label>Amount to Convert <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" name="convert_amount" class="form-control" 
                                   min="0.01" max="<?= $confirmedBalance; ?>" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="convert_to_credit" class="btn btn-primary btn-sm">
                        <i class="fas fa-exchange-alt"></i> Convert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
var productLinks = {
    'general': '<?= myRefLink(encrypt($userId)); ?>',
    <?php foreach($products as $product): ?>
    '<?= $product['slug']; ?>': '<?= $sub_url; ?>product/<?= $product['slug']; ?>?ref=<?= encrypt($userId); ?>',
    <?php endforeach; ?>
};

function updateRefLink() {
    var selector = document.getElementById('productSelector');
    var selectedValue = selector.value;
    var linkInput = document.getElementById('refLink');
    var badge = document.getElementById('linkTypeBadge');
    
    linkInput.value = productLinks[selectedValue];
    badge.innerHTML = selectedValue === 'general' ? 
        '<i class="fas fa-globe"></i> General Link' : 
        '<i class="fas fa-box"></i> Product Link';
}

function copyRefLink() {
    var copyText = document.getElementById("refLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    Swal.fire({
        icon: 'success',
        title: 'Copied!',
        text: 'Referral link copied to clipboard',
        showConfirmButton: false,
        timer: 1500
    });
}

function switchTab(tabName, element) {
    document.querySelectorAll('.custom-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    element.classList.add('active');
    document.getElementById(tabName).classList.add('active');
}

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('.datatables')) {
        $('.datatables').DataTable().destroy();
    }
    
    if(window.innerWidth > 768) {
        $('.datatables').DataTable({
            "order": [[2, "desc"]],
            "pageLength": 10,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_",
                "info": "_START_-_END_ of _TOTAL_"
            }
        });
    }
});
</script>