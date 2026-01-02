<?php
session_start();
include "../init.php";
if(!isLoggedIn())
{
  redirect("../index.php");
  exit();
}

$userId = getUserData()['id'];
$currentBalance = getUserData()['credits'];

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$date_range = isset($_GET['range']) ? $_GET['range'] : 'all';

// Build query based on filters
$where_clause = "WHERE `user_id` = '$userId'";

// Type filter
if($filter_type == 'credit') {
    $where_clause .= " AND `charge` = '+'";
} elseif($filter_type == 'debit') {
    $where_clause .= " AND `charge` = '-'";
}

// Date range filter
if($date_range == 'today') {
    $where_clause .= " AND `date` >= " . strtotime('today');
} elseif($date_range == 'week') {
    $where_clause .= " AND `date` >= " . strtotime('-7 days');
} elseif($date_range == 'month') {
    $where_clause .= " AND `date` >= " . strtotime('-30 days');
}

// IMPORTANT: Order by ID DESC to see newest first in proper sequence
$db->query("SELECT * FROM `payment_logs` $where_clause ORDER BY `id` DESC");
$histories = $db->getall();


include "header.php";
?>

<style>
/* Stats Cards */
.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-left: 4px solid;
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}
.stat-card.primary { border-left-color: #667eea; }
.stat-card.success { border-left-color: #48bb78; }
.stat-card.danger { border-left-color: #f56565; }
.stat-card.info { border-left-color: #4299e1; }

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 15px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.danger { background: rgba(245, 101, 101, 0.1); color: #f56565; }
.stat-icon.info { background: rgba(66, 153, 225, 0.1); color: #4299e1; }

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 5px;
}
.stat-label {
    color: #718096;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* Filter Tabs */
.filter-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
.filter-tab {
    padding: 10px 20px;
    background: #f7fafc;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #718096;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}
.filter-tab:hover {
    background: #edf2f7;
    color: #4a5568;
}
.filter-tab.active {
    background: #667eea;
    color: white;
}

/* Date Range Buttons */
.date-range-buttons {
    display: flex;
    gap: 10px;
}
.date-btn {
    padding: 8px 16px;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #718096;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}
.date-btn:hover {
    border-color: #667eea;
    color: #667eea;
}
.date-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

/* Enhanced Table */
.table-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}
.table-header {
    padding: 20px 25px;
    background: white;
    border-bottom: 2px solid #f0f4f8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.table-body {
    padding: 20px;
}
.logs-table {
    width: 100%;
}
.logs-table thead th {
    background: #f8fafc;
    color: #4a5568;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    padding: 12px 15px;
    border: none;
}
.logs-table tbody tr {
    border-bottom: 1px solid #e8ecf1;
    transition: all 0.3s;
}
.logs-table tbody tr:hover {
    background: #f8fafc;
}
.logs-table tbody td {
    padding: 15px;
    vertical-align: middle;
    color: #2d3748;
}

/* Transaction Types */
.amount-credit {
    color: #48bb78;
    font-weight: 600;
    font-size: 1rem;
}
.amount-debit {
    color: #f56565;
    font-weight: 600;
    font-size: 1rem;
}
.balance-amount {
    font-weight: 700;
    color: #2d3748;
    font-size: 1rem;
}

/* Transaction Details */
.transaction-detail {
    display: flex;
    align-items: center;
    gap: 10px;
}
.transaction-icon {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.transaction-icon.credit {
    background: rgba(72, 187, 120, 0.1);
    color: #48bb78;
}
.transaction-icon.debit {
    background: rgba(245, 101, 101, 0.1);
    color: #f56565;
}

/* Export Button */
.btn-export {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state i {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 20px;
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-history"></i> Credits Logs</h1>
        </div>

        <!-- Filters -->
        <div class="filter-container" style="display:none;">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3">Transaction Type</h6>
                    <div class="filter-tabs">
                        <a href="?type=all&range=<?= $date_range ?>" class="filter-tab <?= $filter_type == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All
                        </a>
                        <a href="?type=credit&range=<?= $date_range ?>" class="filter-tab <?= $filter_type == 'credit' ? 'active' : ''; ?>">
                            <i class="fas fa-plus"></i> Credits
                        </a>
                        <a href="?type=debit&range=<?= $date_range ?>" class="filter-tab <?= $filter_type == 'debit' ? 'active' : ''; ?>">
                            <i class="fas fa-minus"></i> Debits
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3">Date Range</h6>
                    <div class="date-range-buttons">
                        <a href="?type=<?= $filter_type ?>&range=all" class="date-btn <?= $date_range == 'all' ? 'active' : ''; ?>">All Time</a>
                        <a href="?type=<?= $filter_type ?>&range=today" class="date-btn <?= $date_range == 'today' ? 'active' : ''; ?>">Today</a>
                        <a href="?type=<?= $filter_type ?>&range=week" class="date-btn <?= $date_range == 'week' ? 'active' : ''; ?>">This Week</a>
                        <a href="?type=<?= $filter_type ?>&range=month" class="date-btn <?= $date_range == 'month' ? 'active' : ''; ?>">This Month</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="table-card">
            <div class="table-header">
                <h4><i class="fas fa-list"></i> Transaction History</h4>
                <button class="btn-export" onclick="exportToCSV()">
                    <i class="fas fa-download"></i> Export CSV
                </button>
            </div>
            <div class="table-body">
                <?php if(empty($histories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h4>No Transaction History</h4>
                        <p>Your credit transactions will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="logs-table" id="logsTable">
                            <thead>
                                <tr>
                                    <th width="8%">Log ID</th>
                                    <th width="37%">Transaction Details</th>
                                    <th width="15%">Amount</th>
                                    <th width="15%">Balance After</th>
                                    <th width="25%">Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Display logs in the order they come from database (already ordered by ID DESC)
                                foreach($histories as $history):
                                    $isCredit = strpos($history['charge'], '+') !== false;
                                    $amount = str_replace(['+', '-', '$'], '', $history['charge']);
                                    $logId = isset($history['id']) ? $history['id'] : 'N/A';
                                ?>
                                <tr data-log-id="<?= $logId ?>">
                                    <td>
                                        <strong>#<?= $logId ?></strong>
                                    </td>
                                    <td>
                                        <div class="transaction-detail">
                                            <div class="transaction-icon <?= $isCredit ? 'credit' : 'debit' ?>">
                                                <i class="fas fa-<?= $isCredit ? 'arrow-down' : 'arrow-up' ?>"></i>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($history['detail']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="<?= $isCredit ? 'amount-credit' : 'amount-debit' ?>">
                                            <?= $isCredit ? '+' : '-' ?> $<?= number_format($amount, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="balance-amount">$<?= number_format(str_replace('$', '', $history['balance']), 2); ?></span>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar"></i> <?= date("d M Y", $history['date']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> <?= date("h:i A", $history['date']); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<br><Br>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable with DISABLED sorting to maintain database order
    if (!$.fn.DataTable.isDataTable('#logsTable')) {
        $('#logsTable').DataTable({
            "ordering": false,  // DISABLE sorting to keep database order
            "pageLength": 25,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
                "emptyTable": "No transactions found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "<i class='fas fa-chevron-right'></i>",
                    "previous": "<i class='fas fa-chevron-left'></i>"
                }
            }
        });
    }
});

// Export to CSV
function exportToCSV() {
    let csv = 'Log ID,Details,Amount,Balance After,Date\n';
    
    $('#logsTable tbody tr').each(function() {
        let logId = $(this).data('log-id');
        let details = $(this).find('.transaction-detail strong').text().trim();
        let amount = $(this).find('td').eq(2).text().trim();
        let balance = $(this).find('td').eq(3).text().trim();
        let date = $(this).find('td').eq(4).text().trim().replace(/\s+/g, ' ');
        
        csv += `${logId},"${details}",${amount},${balance},"${date}"\n`;
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', 'credit_logs_' + new Date().toISOString().slice(0,10) + '.csv');
    a.click();
    
    Swal.fire({
        icon: 'success',
        title: 'Export Successful',
        text: 'Your transaction history has been exported.',
        showConfirmButton: false,
        timer: 2000
    });
}
</script>