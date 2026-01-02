<?php
session_start();
include "../init.php";

if (!isLoggedIn()) {
    redirect("../login.php");
    exit();
}

if (isLoggedIn()) {
    $admin = getUserData()['is_admin'];
    if ($admin != 1) {
        redirect("../user");
        exit();
    }
}

// Filters
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterProduct = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$filterUser = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$whereConditions = [];
$whereParams = [];

if (!empty($filterType)) {
    $whereConditions[] = "al.log_type = '%s'";
    $whereParams[] = $filterType;
}

if (!empty($filterStatus)) {
    $whereConditions[] = "al.status = '%s'";
    $whereParams[] = $filterStatus;
}

if ($filterProduct > 0) {
    $whereConditions[] = "al.product_id = '%d'";
    $whereParams[] = $filterProduct;
}

if ($filterUser > 0) {
    $whereConditions[] = "al.user_id = '%d'";
    $whereParams[] = $filterUser;
}

if (!empty($filterDateFrom)) {
    $whereConditions[] = "DATE(al.created_at) >= '%s'";
    $whereParams[] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $whereConditions[] = "DATE(al.created_at) <= '%s'";
    $whereParams[] = $filterDateTo;
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Build the final query
$sql = "SELECT al.*, u.email as user_email
        FROM automation_logs al
        LEFT JOIN users u ON al.user_id = u.id
        $whereClause
        ORDER BY al.id DESC
        LIMIT 500";

if (!empty($whereParams)) {
    array_unshift($whereParams, $sql);
    call_user_func_array([$db, 'query'], $whereParams);
} else {
    $db->query($sql);
}

$logs = $db->getall();

// Get products for filter dropdown
$db->query("SELECT id, product_name FROM products ORDER BY product_name");
$products = $db->getall();

// Get statistics
$db->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
    SUM(CASE WHEN log_type = 'activation' THEN 1 ELSE 0 END) as activation_count,
    SUM(CASE WHEN log_type = 'email_validation' THEN 1 ELSE 0 END) as validation_count,
    SUM(CASE WHEN log_type = 'renewal' THEN 1 ELSE 0 END) as renewal_count
    FROM automation_logs");
$stats = $db->getdata();

include "header.php";
?>

<style>
.stats-card {
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.stats-card .number {
    font-size: 2rem;
    font-weight: bold;
}
.stats-card .label {
    font-size: 0.9rem;
    opacity: 0.9;
}
.stats-success { background: linear-gradient(135deg, #28a745, #20c997); }
.stats-danger { background: linear-gradient(135deg, #dc3545, #e83e8c); }
.stats-warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
.stats-primary { background: linear-gradient(135deg, #007bff, #6610f2); }
.stats-info { background: linear-gradient(135deg, #17a2b8, #20c997); }

.log-type-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.log-type-activation { background: #e3f2fd; color: #1976d2; }
.log-type-email_validation { background: #fff3e0; color: #f57c00; }
.log-type-renewal { background: #e8f5e9; color: #388e3c; }

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-success { background: #d4edda; color: #155724; }
.status-failed { background: #f8d7da; color: #721c24; }
.status-error { background: #fff3cd; color: #856404; }

.filter-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
}

.log-detail-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.json-display {
    background: #f4f4f4;
    border-radius: 5px;
    padding: 10px;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 200px;
    overflow-y: auto;
}

.detail-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.detail-value {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 15px;
}

.instructions-preview {
    background: #e8f4f8;
    border-left: 4px solid #17a2b8;
    padding: 10px;
    margin-bottom: 15px;
    white-space: pre-wrap;
    max-height: 150px;
    overflow-y: auto;
}

.execution-time {
    font-size: 0.8rem;
    color: #6c757d;
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-robot mr-2"></i>Automation Logs</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item">Dashboard</div>
                <div class="breadcrumb-item">Automation Logs</div>
            </div>
        </div>
    </section>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-primary">
                <div class="number"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="label"><i class="fas fa-list"></i> Total Logs</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-success">
                <div class="number"><?= number_format($stats['success_count'] ?? 0) ?></div>
                <div class="label"><i class="fas fa-check-circle"></i> Success</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-danger">
                <div class="number"><?= number_format($stats['failed_count'] ?? 0) ?></div>
                <div class="label"><i class="fas fa-times-circle"></i> Failed</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-warning">
                <div class="number"><?= number_format($stats['error_count'] ?? 0) ?></div>
                <div class="label"><i class="fas fa-exclamation-triangle"></i> Errors</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card stats-info">
                <div class="number"><?= number_format($stats['activation_count'] ?? 0) ?></div>
                <div class="label"><i class="fas fa-bolt"></i> Activations</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
                <div class="number"><?= number_format($stats['renewal_count'] ?? 0) ?></div>
                <div class="label"><i class="fas fa-sync"></i> Renewals</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-2">
                    <label>Log Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="activation" <?= $filterType == 'activation' ? 'selected' : '' ?>>Activation</option>
                        <option value="email_validation" <?= $filterType == 'email_validation' ? 'selected' : '' ?>>Email Validation</option>
                        <option value="renewal" <?= $filterType == 'renewal' ? 'selected' : '' ?>>Renewal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="success" <?= $filterStatus == 'success' ? 'selected' : '' ?>>Success</option>
                        <option value="failed" <?= $filterStatus == 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="error" <?= $filterStatus == 'error' ? 'selected' : '' ?>>Error</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Product</label>
                    <select name="product_id" class="form-control">
                        <option value="">All Products</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= $filterProduct == $product['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($product['product_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filterDateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label>Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filterDateTo) ?>">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="automation_logs.php" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-history"></i> API Call Logs</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatables" id="logsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date/Time</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>User</th>
                            <th>Invoice</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No automation logs found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td data-order="<?= $log['id'] ?>"><strong>#<?= $log['id'] ?></strong></td>
                                    <td>
                                        <small><?= date('M d, Y', strtotime($log['created_at'])) ?></small><br>
                                        <small class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="log-type-badge log-type-<?= $log['log_type'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $log['log_type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['product_name'] ?: 'N/A') ?>
                                        <?php if ($log['product_id']): ?>
                                            <small class="text-muted d-block">(ID: <?= $log['product_id'] ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['user_email'] ?: 'N/A') ?>
                                        <?php if ($log['user_id']): ?>
                                            <small class="text-muted d-block">(ID: <?= $log['user_id'] ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['invoice_id']): ?>
                                            <a href="userInvoices.php?search=<?= $log['invoice_id'] ?>" class="badge badge-info">
                                                #<?= $log['invoice_id'] ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $log['status'] ?>">
                                            <?php if ($log['status'] == 'success'): ?>
                                                <i class="fas fa-check"></i>
                                            <?php elseif ($log['status'] == 'failed'): ?>
                                                <i class="fas fa-times"></i>
                                            <?php else: ?>
                                                <i class="fas fa-exclamation"></i>
                                            <?php endif; ?>
                                            <?= ucfirst($log['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="execution-time">
                                            <?= number_format($log['execution_time'] * 1000, 0) ?>ms
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary view-details"
                                                data-log='<?= htmlspecialchars(json_encode($log), ENT_QUOTES) ?>'>
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade log-detail-modal" id="logDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Log Details #<span id="modalLogId"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Log Type</div>
                        <div class="detail-value" id="modalLogType"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status</div>
                        <div class="detail-value" id="modalStatus"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Product</div>
                        <div class="detail-value" id="modalProduct"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">User</div>
                        <div class="detail-value" id="modalUser"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="detail-label">Invoice ID</div>
                        <div class="detail-value" id="modalInvoice"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Order ID</div>
                        <div class="detail-value" id="modalOrder"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-label">Execution Time</div>
                        <div class="detail-value" id="modalExecTime"></div>
                    </div>
                </div>

                <div class="detail-label">API URL</div>
                <div class="detail-value" style="word-break: break-all;" id="modalApiUrl"></div>

                <div class="detail-label">Request Parameters</div>
                <div class="json-display" id="modalRequestParams"></div>

                <div class="detail-label">Raw Response</div>
                <div class="json-display" id="modalResponseRaw"></div>

                <div class="detail-label">Parsed Response</div>
                <div class="json-display" id="modalResponseParsed"></div>

                <div class="detail-label">Extracted Data</div>
                <div class="json-display" id="modalExtractedData"></div>

                <div id="errorSection" style="display: none;">
                    <div class="detail-label text-danger">Error Message</div>
                    <div class="alert alert-danger" id="modalError"></div>
                </div>

                <div id="instructionsSection" style="display: none;">
                    <div class="detail-label">Generated Instructions</div>
                    <div class="instructions-preview" id="modalInstructions"></div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="detail-label">IP Address</div>
                        <div class="detail-value" id="modalIp"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Timestamp</div>
                        <div class="detail-value" id="modalTimestamp"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable with proper ordering (newest first)
    if ($.fn.DataTable.isDataTable('#logsTable')) {
        $('#logsTable').DataTable().destroy();
    }
    $('#logsTable').DataTable({
        responsive: true,
        order: [[0, 'desc']], // Order by ID descending (newest first)
        pageLength: 25
    });

    // Use event delegation for dynamically created elements (DataTables pagination)
    $(document).on('click', '.view-details', function() {
        var log = $(this).data('log');

        $('#modalLogId').text(log.id);
        $('#modalLogType').html('<span class="log-type-badge log-type-' + log.log_type + '">' +
            log.log_type.replace('_', ' ').toUpperCase() + '</span>');

        var statusClass = 'status-' + log.status;
        var statusIcon = log.status == 'success' ? 'check' : (log.status == 'failed' ? 'times' : 'exclamation');
        $('#modalStatus').html('<span class="status-badge ' + statusClass + '"><i class="fas fa-' + statusIcon + '"></i> ' +
            log.status.charAt(0).toUpperCase() + log.status.slice(1) + '</span>');

        $('#modalProduct').text(log.product_name + ' (ID: ' + log.product_id + ')');
        $('#modalUser').text((log.user_email || 'N/A') + ' (ID: ' + log.user_id + ')');
        $('#modalInvoice').text(log.invoice_id || 'N/A');
        $('#modalOrder').text(log.order_id || 'N/A');
        $('#modalExecTime').text((parseFloat(log.execution_time) * 1000).toFixed(2) + ' ms');
        $('#modalApiUrl').text(log.api_url || 'N/A');

        // Format JSON displays
        try {
            var params = typeof log.request_params === 'string' ? JSON.parse(log.request_params) : log.request_params;
            $('#modalRequestParams').text(JSON.stringify(params, null, 2));
        } catch(e) {
            $('#modalRequestParams').text(log.request_params || 'N/A');
        }

        $('#modalResponseRaw').text(log.response_raw || 'N/A');

        try {
            var parsed = typeof log.response_parsed === 'string' ? JSON.parse(log.response_parsed) : log.response_parsed;
            $('#modalResponseParsed').text(JSON.stringify(parsed, null, 2));
        } catch(e) {
            $('#modalResponseParsed').text(log.response_parsed || 'N/A');
        }

        try {
            var extracted = typeof log.extracted_data === 'string' ? JSON.parse(log.extracted_data) : log.extracted_data;
            $('#modalExtractedData').text(JSON.stringify(extracted, null, 2));
        } catch(e) {
            $('#modalExtractedData').text(log.extracted_data || 'N/A');
        }

        // Show/hide error section
        if (log.error_message) {
            $('#errorSection').show();
            $('#modalError').text(log.error_message);
        } else {
            $('#errorSection').hide();
        }

        // Show/hide instructions section
        if (log.instructions_generated) {
            $('#instructionsSection').show();
            $('#modalInstructions').text(log.instructions_generated);
        } else {
            $('#instructionsSection').hide();
        }

        $('#modalIp').text(log.ip_address || 'N/A');
        $('#modalTimestamp').text(log.created_at);

        $('#logDetailModal').modal('show');
    });
});
</script>