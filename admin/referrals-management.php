<?php
session_start();
include "../init.php";

// Check if admin
if(!isLoggedIn() || getUserData()['is_admin'] != 1) {
    redirect("../index.php");
    exit();
}
ini_set('display_errors', 1);
// Handle withdrawal actions
if(isset($_POST['action']) && isset($_POST['withdrawal_id'])) {
    $withdrawalId = intval($_POST['withdrawal_id']);
    $action = $_POST['action'];
    $admin_notes = isset($_POST['admin_notes']) ? $_POST['admin_notes'] : '';
    
    // Get withdrawal details
    $db->query("SELECT * FROM `withdrawal_requests` WHERE `id` = '%d'", $withdrawalId);
    $withdrawal = $db->getdata();
    
    if($withdrawal) {
        if($action == 'approve') {
            // Update withdrawal status
            $db->query("UPDATE `withdrawal_requests` SET `status` = 'completed', `admin_notes` = '%s', `processed_at` = NOW() WHERE `id` = '%d'", 
                       $admin_notes, $withdrawalId);
            
            // Reduce pending bonus
            $db->query("UPDATE `users` SET `ref_pending_bonus` = ref_pending_bonus - '%s' WHERE `id` = '%d'", 
                       $withdrawal['amount'], $withdrawal['user_id']);
            
            // Update transaction status
            $db->query("UPDATE `referral_transactions` SET `status` = 'confirmed', `confirmed_at` = NOW() 
                        WHERE `user_id` = '%d' AND `type` = 'withdrawal' AND `amount` = '%s' AND `status` = 'pending' 
                        ORDER BY created_at DESC LIMIT 1", 
                        $withdrawal['user_id'], $withdrawal['amount']);
            
            $successMsg = "Withdrawal approved successfully!";
            
        } elseif($action == 'reject') {
            // Update withdrawal status
            $db->query("UPDATE `withdrawal_requests` SET `status` = 'rejected', `admin_notes` = '%s', `processed_at` = NOW() WHERE `id` = '%d'", 
                       $admin_notes, $withdrawalId);
            
            // Return amount from pending back to available
            $db->query("UPDATE `users` SET `ref_bonus` = ref_bonus + '%s', `ref_pending_bonus` = ref_pending_bonus - '%s' WHERE `id` = '%d'", 
                       $withdrawal['amount'], $withdrawal['amount'], $withdrawal['user_id']);
            
            // Update transaction status
            $db->query("UPDATE `referral_transactions` SET `status` = 'cancelled' 
                        WHERE `user_id` = '%d' AND `type` = 'withdrawal' AND `amount` = '%s' AND `status` = 'pending' 
                        ORDER BY created_at DESC LIMIT 1", 
                        $withdrawal['user_id'], $withdrawal['amount']);
            
            $errorMsg = "Withdrawal rejected!";
        }
    }
}

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search_user = isset($_GET['search']) ? $_GET['search'] : '';

// Get withdrawals based on filter
$where_clause = "WHERE 1=1";
if($filter_status != 'all') {
    $where_clause .= " AND wr.status = '$filter_status'";
}
if(!empty($search_user)) {
    $where_clause .= " AND u.email LIKE '%$search_user%'";
}

$db->query("SELECT wr.*, u.email, u.ref_bonus, u.ref_pending_bonus 
            FROM `withdrawal_requests` wr 
            LEFT JOIN `users` u ON wr.user_id = u.id 
            $where_clause 
            ORDER BY wr.requested_at DESC");
$withdrawals = $db->getall();

// Get statistics
$db->query("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM `withdrawal_requests` WHERE `status` = 'pending'");
$pending_stats = $db->getdata();

$db->query("SELECT COUNT(*) as total, SUM(amount) as total_amount FROM `withdrawal_requests` WHERE `status` = 'completed'");
$completed_stats = $db->getdata();

$db->query("SELECT SUM(ref_bonus) as total_available, SUM(ref_pending_bonus) as total_pending FROM `users`");
$balance_stats = $db->getdata();

// Get top 10 referrers
$db->query("SELECT 
            u.id, 
            u.email, 
            COUNT(r.id) as referral_count,
            SUM(CASE WHEN r.is_banned = 0 THEN 1 ELSE 0 END) as active_referrals,
            u.ref_bonus,
            u.ref_pending_bonus
            FROM users u 
            LEFT JOIN users r ON r.ref_by = u.id 
            WHERE u.id IN (SELECT DISTINCT ref_by FROM users WHERE ref_by IS NOT NULL)
            GROUP BY u.id 
            ORDER BY referral_count DESC 
            LIMIT 10");
$top_referrers = $db->getall();

// Get referral search results
$referral_search = isset($_GET['ref_search']) ? $_GET['ref_search'] : '';
$searched_referrals = [];
if(!empty($referral_search)) {
    $db->query("SELECT 
                u.*,
                ref.email as referrer_email
                FROM users u
                LEFT JOIN users ref ON u.ref_by = ref.id
                WHERE u.email LIKE '%s' OR ref.email LIKE '%s'
                ORDER BY u.date DESC",
                '%'.$referral_search.'%', '%'.$referral_search.'%');
    $searched_referrals = $db->getall();
}



include "header.php";
?>

<style>
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-left: 4px solid;
    transition: all 0.3s;
}
.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}
.stats-card.primary { border-left-color: #667eea; }
.stats-card.warning { border-left-color: #f6ad55; }
.stats-card.success { border-left-color: #48bb78; }
.stats-card.info { border-left-color: #4299e1; }

.stats-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}
.stats-label {
    color: #718096;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.withdrawal-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
}
.withdrawal-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}
.status-pending { background: #fef5e7; color: #f39c12; }
.status-completed { background: #e8f8f5; color: #27ae60; }
.status-rejected { background: #ffeaea; color: #e74c3c; }

.filter-tabs {
    display: flex;
    gap: 10px;
    background: #f7fafc;
    padding: 6px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.filter-tab {
    padding: 10px 20px;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #718096;
    cursor: pointer;
    transition: all 0.3s;
}
.filter-tab:hover {
    background: rgba(255,255,255,0.5);
}
.filter-tab.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.top-referrer-item {
    padding: 15px;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}
.top-referrer-item:hover {
    background: #edf2f7;
    transform: translateX(5px);
}
.rank-badge {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: white;
}
.rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4e); color: #333; }
.rank-2 { background: linear-gradient(135deg, #c0c0c0, #d8d8d8); color: #333; }
.rank-3 { background: linear-gradient(135deg, #cd7f32, #e4a853); }
.rank-default { background: linear-gradient(135deg, #667eea, #764ba2); }

.action-buttons {
    display: flex;
    gap: 8px;
}
.btn-approve {
    background: linear-gradient(135deg, #48bb78, #38a169);
    color: white;
}
.btn-reject {
    background: linear-gradient(135deg, #f56565, #e74c3c);
    color: white;
}
.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}
</style>

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-users-cog"></i> Referral & Withdrawal Management</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item">Admin</div>
                <div class="breadcrumb-item active">Referral Management</div>
            </div>
        </div>

        <?php if(isset($successMsg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= $successMsg; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <?php if(isset($errorMsg)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-times-circle"></i> <?= $errorMsg; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card primary">
                    <div class="stats-value">
                        <?= $pending_stats['total'] ?? 0; ?>
                    </div>
                    <div class="stats-label">Pending Withdrawals</div>
                    <small class="text-muted">
                        $<?= number_format($pending_stats['total_amount'] ?? 0, 2); ?> Total
                    </small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card success">
                    <div class="stats-value">
                        <?= $completed_stats['total'] ?? 0; ?>
                    </div>
                    <div class="stats-label">Completed Withdrawals</div>
                    <small class="text-muted">
                        $<?= number_format($completed_stats['total_amount'] ?? 0, 2); ?> Paid Out
                    </small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card warning">
                    <div class="stats-value">
                        $<?= number_format($balance_stats['total_available'] ?? 0, 2); ?>
                    </div>
                    <div class="stats-label">Total Available Balance</div>
                    <small class="text-muted">All Users Combined</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card info">
                    <div class="stats-value">
                        $<?= number_format($balance_stats['total_pending'] ?? 0, 2); ?>
                    </div>
                    <div class="stats-label">Total Pending Balance</div>
                    <small class="text-muted">In Withdrawal Queue</small>
                </div>
            </div>
        </div>

        <!-- Withdrawal Management Section -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-money-check-alt"></i> Withdrawal Requests</h4>
                <div class="card-header-action">
                    <form method="GET" class="form-inline">
                        <input type="text" name="search" class="form-control mr-2" 
                               placeholder="Search by email..." value="<?= $search_user; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?status=pending" class="filter-tab <?= $filter_status == 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="?status=completed" class="filter-tab <?= $filter_status == 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-check"></i> Completed
                    </a>
                    <a href="?status=rejected" class="filter-tab <?= $filter_status == 'rejected' ? 'active' : ''; ?>">
                        <i class="fas fa-times"></i> Rejected
                    </a>
                    <a href="?status=all" class="filter-tab <?= $filter_status == 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All
                    </a>
                </div>

                <!-- Withdrawals List -->
                <?php if(empty($withdrawals)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                        <h5 class="text-muted">No withdrawal requests found</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="withdrawalsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Account Details</th>
                                    <th>Balance Info</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($withdrawals as $wr): ?>
                                <tr>
                                    <td>#<?= $wr['id']; ?></td>
                                    <td>
                                        <strong><?= $wr['email']; ?></strong>
                                        <br>
                                        <small>User ID: #<?= $wr['user_id']; ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-primary">$<?= number_format($wr['amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php if($wr['withdrawal_type'] == 'paypal'): ?>
                                            <i class="fab fa-paypal text-primary"></i> PayPal
                                        <?php else: ?>
                                            <i class="fab fa-bitcoin text-warning"></i> Crypto
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-monospace small">
                                            <?php 
                                            if(filter_var($wr['account_details'], FILTER_VALIDATE_EMAIL)) {
                                                echo $wr['account_details'];
                                            } else {
                                                // For crypto addresses
                                                echo substr($wr['account_details'], 0, 8) . '...' . substr($wr['account_details'], -6);
                                            }
                                            ?>
                                        </span>
                                        <button class="btn btn-xs btn-link" onclick="showFullDetails('<?= htmlspecialchars($wr['account_details']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <small>
                                            Available: $<?= number_format($wr['ref_bonus'], 2); ?><br>
                                            Pending: $<?= number_format($wr['ref_pending_bonus'], 2); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $wr['status']; ?>">
                                            <?= ucfirst($wr['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date("d M Y", strtotime($wr['requested_at'])); ?>
                                        <br>
                                        <small><?= date("h:i A", strtotime($wr['requested_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if($wr['status'] == 'pending'): ?>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-approve" 
                                                    onclick="processWithdrawal(<?= $wr['id']; ?>, 'approve')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-reject" 
                                                    onclick="processWithdrawal(<?= $wr['id']; ?>, 'reject')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <small class="text-muted">
                                            <?= $wr['admin_notes'] ?: 'Processed'; ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Referrers Section -->
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-trophy"></i> Top 10 Referrers</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach($top_referrers as $index => $referrer): ?>
                        <div class="top-referrer-item">
                            <div class="d-flex align-items-center">
                                <div class="rank-badge <?= $index < 3 ? 'rank-'.($index+1) : 'rank-default'; ?>">
                                    <?= $index + 1; ?>
                                </div>
                                <div class="ml-3">
                                    <strong><?= $referrer['email']; ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-users"></i> <?= $referrer['referral_count']; ?> referrals
                                        (<?= $referrer['active_referrals']; ?> active)
                                    </small>
                                </div>
                            </div>
                            <div class="text-right">
                                <strong class="text-success">$<?= number_format($referrer['ref_bonus'], 2); ?></strong>
                                <br>
                                <small class="text-muted">Available</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($top_referrers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                            <p class="text-muted">No referrers found yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Referral Search Section -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-search"></i> Search Referrals</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="ref_search" class="form-control" 
                                       placeholder="Search by user email or referrer email..." 
                                       value="<?= $referral_search; ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if(!empty($searched_referrals)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Referred By</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($searched_referrals as $ref): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $ref['email']; ?></strong>
                                            <br>
                                            <small>ID: #<?= $ref['id']; ?></small>
                                        </td>
                                        <td>
                                            <?php if($ref['referrer_email']): ?>
                                                <?= $ref['referrer_email']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Direct</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date("d M Y", $ref['date']); ?></td>
                                        <td>
                                            <?php if($ref['is_banned'] == 1): ?>
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
                        <?php elseif(!empty($referral_search)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                            <p class="text-muted">No results found for "<?= htmlspecialchars($referral_search); ?>"</p>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                            <p class="text-muted">Enter email to search for referrals</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Process Withdrawal Modal -->
<div class="modal fade" id="processModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Withdrawal</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" id="processForm">
                <div class="modal-body">
                    <input type="hidden" name="withdrawal_id" id="withdrawalId">
                    <input type="hidden" name="action" id="withdrawalAction">
                    
                    <div class="alert alert-info" id="actionInfo"></div>
                    
                    <div class="form-group">
                        <label>Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" 
                                  placeholder="Add any notes about this action..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Account Details:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="fullDetails" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-primary" onclick="copyDetails()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable with proper check
    if (!$.fn.DataTable.isDataTable('#withdrawalsTable')) {
        $('#withdrawalsTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search withdrawals:",
                "lengthMenu": "Show _MENU_ withdrawals",
                "info": "Showing _START_ to _END_ of _TOTAL_ withdrawals"
            }
        });
    }
});

function processWithdrawal(id, action) {
    $('#withdrawalId').val(id);
    $('#withdrawalAction').val(action);
    
    if(action === 'approve') {
        $('#actionInfo').html('<i class="fas fa-check-circle"></i> You are about to APPROVE this withdrawal request.');
        $('#confirmBtn').removeClass('btn-danger').addClass('btn-success').text('Approve Withdrawal');
    } else {
        $('#actionInfo').html('<i class="fas fa-exclamation-triangle"></i> You are about to REJECT this withdrawal request. The amount will be returned to user\'s available balance.');
        $('#confirmBtn').removeClass('btn-success').addClass('btn-danger').text('Reject Withdrawal');
    }
    
    $('#processModal').modal('show');
}

function showFullDetails(details) {
    $('#fullDetails').val(details);
    $('#detailsModal').modal('show');
}

function copyDetails() {
    var copyText = document.getElementById("fullDetails");
    copyText.select();
    document.execCommand("copy");
    
    Swal.fire({
        icon: 'success',
        title: 'Copied!',
        text: 'Account details copied to clipboard',
        showConfirmButton: false,
        timer: 1500
    });
}

// Auto-refresh for pending withdrawals
<?php if($filter_status == 'pending'): ?>
setTimeout(function() {
    location.reload();
}, 60000); // Refresh every 60 seconds
<?php endif; ?>
</script>