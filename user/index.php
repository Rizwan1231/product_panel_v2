<?php
session_start();
include "../init.php";

if(isset($_GET['token']) && !empty($_GET['token']))
{
  $token = trim($_GET['token']);
  if(!decrypt($token))
  {
   session_destroy();
   redirect("../index.php");
   exit();
  }
  $token = decrypt($token);
  $udata = explode('|', $token);
  $userId = $udata[0];
  $userEmail = $udata[1];
  $userPassword = $udata[2];
  $isAdmin = $udata[3];

  $check = loginCheck($userEmail, $userPassword);
  if(!$check || empty($check))
  {
   session_destroy();
   redirect("../index.php");
   exit();
   }
   else
   {
     $_SESSION['user_id'] = $userId;
     $_SESSION['email'] = $userEmail;
     $_SESSION['password'] = $userPassword;
     if($isAdmin == 0) {
      redirect("index.php");
     exit();
     }elseif($isAdmin == 1) {
      redirect("../admin/index.php");
     exit();
     }else {
      redirect("../index.php");
     exit();
     }
   }
  exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'logout')
{
   session_destroy();
   echo "<script>localStorage.removeItem('loginKey'); loggedin = 0;</script>";
   redirect($client_url . 'index.php?action=loggedout');
 exit();
}
if(!isLoggedIn())
{
  redirect("../index.php");
  exit();
}

if(isset($_POST['topup']) && !empty($_POST['ammount']))
{
 $ammount = trim(intval($_POST['ammount']));
 if($ammount < 10)
 {
    http_response_code(400);
  echo json_encode(array("status" => "error", "message" => "Please Enter Amount More Than $10." ));
  exit();
 }
 $uid = getUserData()['id'];
 $rdata = array( "user_id" => $uid, "ammount" => $ammount );
 $db->query("INSERT INTO `invoices` (`user_id`, `products_data`, `recharge_data`, `date`) VALUES ('%d', '', '%s', '%d')", $uid, json_encode($rdata), time());
 $invoice_id = $db->inserted_id();
 echo json_encode(array( "status" => "success", "message" => "Invoice successfully generated.", "redirect" => 'invoice.php?invoice_id=' . encrypt($invoice_id) ));
 exit();
}

// Get user data
$userData = getUserData();
$userId = $userData['id'];
$userEmail = $userData['email'];
$userBalance = $userData['credits'];
$referralBonus = $userData['ref_bonus'] ?? 0;

// Get recent orders
$db->query("SELECT * FROM `orders` WHERE `user_id` = '%d' ORDER BY `date` DESC LIMIT 5", $userId);
$recentOrders = $db->getall();

// Get recent invoices
$db->query("SELECT * FROM `invoices` WHERE `user_id` = '%d' ORDER BY `date` DESC LIMIT 5", $userId);
$recentInvoices = $db->getall();

// Calculate statistics
$totalReferrals = getTotalRefs($userId);
$totalProducts = getTotalOrders($userId);

include "header.php";
?>

<style>
/* Base Reset for Dashboard */
.dashboard-container {
    padding: 15px;
}

/* Welcome Section */
.welcome-section {
    background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
    border-radius: 12px;
    padding: 20px;
    color: #262626;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.welcome-section h2 {
    font-size: 1.4rem;
    margin-bottom: 5px;
    font-weight: 600;
}
.welcome-section p {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 15px;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.quick-action-btn {
    padding: 8px 16px;
    background: rgb(255 255 255 / 20%);
    border: 1px solid rgb(218 122 122 / 30%);
    color: #fc544b;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.8rem;
    transition: all 0.3s;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}


/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

/* Stat Card - Compact Design */
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border-left: 3px solid;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s ease;
    min-height: 80px;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-card.primary { border-left-color: #667eea; }
.stat-card.success { border-left-color: #48bb78; }
.stat-card.warning { border-left-color: #f6ad55; }
.stat-card.info { border-left-color: #4299e1; }

.stat-content {
    flex: 1;
    min-width: 0;
}
.stat-label {
    color: #718096;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
    margin-bottom: 4px;
}
.stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1.2;
}
.stat-badge {
    font-size: 0.65rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-top: 4px;
}
.stat-badge.positive {
    color: #48bb78;
    background: rgba(72, 187, 120, 0.1);
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-left: 10px;
}
.stat-icon.primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-icon.success { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
.stat-icon.warning { background: rgba(246, 173, 85, 0.1); color: #f6ad55; }
.stat-icon.info { background: rgba(66, 153, 225, 0.1); color: #4299e1; }

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
}

/* Activity Card */
.activity-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f4f8;
}
.activity-header h4 {
    font-size: 1rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.activity-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: all 0.3s;
}
.activity-item:last-child {
    margin-bottom: 0;
}
.activity-item:hover {
    background: #edf2f7;
}
.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
    background: rgba(255, 164, 38, 0.1);
    color: #ffa426;
}
.activity-info {
    flex: 1;
    min-width: 0;
}
.activity-info strong {
    font-size: 0.85rem;
    display: block;
}
.activity-info small {
    font-size: 0.75rem;
    color: #718096;
}

/* Support Card */
.support-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.support-card h4 {
    font-size: 1rem;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.support-card > p {
    font-size: 0.8rem;
    color: #718096;
    margin-bottom: 15px;
}
.support-item {
    background: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    transition: all 0.3s;
}
.support-item:last-child {
    margin-bottom: 0;
}
.support-item:hover {
    background: #edf2f7;
}
.support-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.support-whatsapp { background: rgba(37, 211, 102, 0.1); color: #25d366; }
.support-telegram { background: rgba(0, 136, 204, 0.1); color: #0088cc; }
.support-email { background: rgba(234, 67, 53, 0.1); color: #ea4335; }

.support-info strong {
    font-size: 0.85rem;
    display: block;
}
.support-info a {
    font-size: 0.8rem;
    color: #718096;
}

/* Modal Styles */
.modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    z-index: 9998;
}
.modal-backdrop.show {
    display: block;
}
.topup-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    max-width: 450px;
    width: calc(100% - 30px);
}
.topup-modal.show {
    display: block;
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { opacity: 0; transform: translate(-50%, -40%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f4f8;
}
.modal-header h3 {
    font-size: 1.1rem;
    margin: 0;
}
.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f0f4f8;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}
.modal-close:hover {
    background: #e2e8f0;
}

/* Amount Presets */
.amount-presets {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 15px;
}
.preset-btn {
    padding: 10px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s;
}
.preset-btn:hover,
.preset-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
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
.empty-state p {
    color: #718096;
    margin-bottom: 15px;
}

/* Responsive Styles */
@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr 280px;
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 10px;
    }
    
    .welcome-section {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    .welcome-section h2 {
        font-size: 1.1rem;
    }
    .welcome-section p {
        font-size: 0.8rem;
        margin-bottom: 12px;
    }
    
    .quick-actions {
        gap: 8px;
    }
    .quick-action-btn {
        padding: 6px 12px;
        font-size: 0.75rem;
    }
    .quick-action-btn i {
        font-size: 0.7rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .stat-card {
        padding: 12px;
        min-height: auto;
        flex-direction: column-reverse;
        align-items: flex-start;
        gap: 8px;
    }
    .stat-icon {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
        margin-left: 0;
    }
    .stat-label {
        font-size: 0.65rem;
    }
    .stat-value {
        font-size: 1.2rem;
    }
    .stat-badge {
        font-size: 0.6rem;
        padding: 2px 5px;
    }
    
    .content-grid {
        gap: 15px;
    }
    
    .activity-card,
    .support-card {
        padding: 15px;
        border-radius: 10px;
    }
    .activity-header {
        margin-bottom: 12px;
        padding-bottom: 10px;
    }
    .activity-header h4 {
        font-size: 0.9rem;
    }
    .activity-item {
        padding: 10px;
        margin-bottom: 8px;
    }
    .activity-icon {
        width: 32px;
        height: 32px;
        margin-right: 10px;
        font-size: 0.85rem;
    }
    .activity-info strong {
        font-size: 0.8rem;
    }
    .activity-info small {
        font-size: 0.7rem;
    }
    
    .support-card h4 {
        font-size: 0.9rem;
    }
    .support-card > p {
        font-size: 0.75rem;
        margin-bottom: 12px;
    }
    .support-item {
        padding: 10px;
    }
    .support-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
        margin-right: 10px;
    }
    .support-info strong {
        font-size: 0.8rem;
    }
    .support-info a {
        font-size: 0.75rem;
    }
    
    /* Modal Mobile */
    .topup-modal {
        padding: 20px;
        width: calc(100% - 20px);
    }
    .modal-header h3 {
        font-size: 1rem;
    }
    .amount-presets {
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }
    .preset-btn {
        padding: 8px 5px;
        font-size: 0.8rem;
    }
}

@media (max-width: 360px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .stat-value {
        font-size: 1.1rem;
    }
    .quick-action-btn span {
        display: none;
    }
    .quick-action-btn {
        padding: 8px 12px;
    }
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        </div>

        <div class="dashboard-container">

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-content">
                        <div class="stat-label">Account Balance</div>
                        <div class="stat-value">$<?= number_format($userBalance, 2); ?></div>
                        <span class="stat-badge positive">
                            <i class="fas fa-arrow-up"></i> Available
                        </span>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-content">
                        <div class="stat-label">Referral Bonus</div>
                        <div class="stat-value">$<?= number_format($referralBonus, 2); ?></div>
                        <span class="stat-badge positive">
                            <i class="fas fa-coins"></i> Earned
                        </span>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-gift"></i>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-content">
                        <div class="stat-label">Total Referrals</div>
                        <div class="stat-value"><?= $totalReferrals; ?></div>
                        <span class="stat-badge positive">
                            <i class="fas fa-user-plus"></i> Users
                        </span>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-content">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?= $totalProducts; ?></div>
                        <span class="stat-badge positive">
                            <i class="fas fa-check"></i> Completed
                        </span>
                    </div>
                    <div class="stat-icon info">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome back, <?= explode('@', $userEmail)[0]; ?>! 👋</h2>
                <p>Here's an overview of your account activity and quick actions.</p>
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="openTopupModal()">
                        <i class="fas fa-wallet"></i> <span>Top Up</span>
                    </button>
                    <a href="products.php" class="quick-action-btn">
                        <i class="fas fa-shopping-cart"></i> <span>Orders</span>
                    </a>
                    <a href="referral.php" class="quick-action-btn">
                        <i class="fas fa-users"></i> <span>Referrals</span>
                    </a>
                </div>
            </div>
			
            <!-- Activity and Support Row -->
            <div class="content-grid">
                <!-- Recent Activity -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h4><i class="fas fa-history"></i> Recent Activity</h4>
                        <a href="invoices.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <?php if(empty($recentOrders) && empty($recentInvoices)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No recent activity</p>
                            <a href="../index.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-shopping-cart"></i> Browse Products
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach($recentInvoices as $invoice): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="activity-info">
                                <strong>Invoice #<?= $invoice['id']; ?></strong>
                                <small><?= date("d M Y, h:i A", $invoice['date']); ?></small>
                            </div>
                            <div>
                                <?php if($invoice['status'] == 1): ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Support Contacts -->
                <div class="support-card">
                    <h4><i class="fas fa-headset"></i> Need Help?</h4>
					<hr>
                    <p>Working Hours<br>01 PM - 10 PM Europe (Friday off)</p>
                    
                    <div class="support-item">
                        <div class="support-icon support-whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="support-info">
                            <strong>WhatsApp</strong>
                            <a href="https://wa.me/<?= $rWhatsapp; ?>" target="_blank"><?= $rWhatsapp; ?></a>
                        </div>
                    </div>
                    
                    <div class="support-item">
                        <div class="support-icon support-telegram">
                            <i class="fab fa-telegram"></i>
                        </div>
                        <div class="support-info">
                            <strong>Telegram</strong>
                            <a href="https://t.me/<?= $rTelegram; ?>" target="_blank">@<?= $rTelegram; ?></a>
                        </div>
                    </div>
                    
                    <div class="support-item">
                        <div class="support-icon support-email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="support-info">
                            <strong>Email</strong>
                            <a href="mailto:<?= $rEmail; ?>"><?= $rEmail; ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Topup Modal -->
<div class="modal-backdrop" id="modalBackdrop"></div>
<div class="topup-modal" id="topupModal">
    <div class="modal-header">
        <h3><i class="fas fa-wallet"></i> Top Up Balance</h3>
        <button class="modal-close" onclick="closeTopupModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form action="index.php" method="post" class="ajaxform_with_redirect">
        <div class="form-group">
            <label>Select Amount (USD)</label>
            <div class="amount-presets">
                <button type="button" class="preset-btn" onclick="setAmount(10)">$10</button>
                <button type="button" class="preset-btn" onclick="setAmount(25)">$25</button>
                <button type="button" class="preset-btn" onclick="setAmount(50)">$50</button>
                <button type="button" class="preset-btn" onclick="setAmount(100)">$100</button>
            </div>
        </div>
        
        <div class="form-group">
            <label>Custom Amount <small class="text-muted">(Min $10)</small></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">$</span>
                </div>
                <input type="number" class="form-control" name="ammount" id="ammount" 
                       placeholder="Enter amount" min="10" required>
                <div class="input-group-append">
                    <span class="input-group-text">USD</span>
                </div>
            </div>
        </div>
        
        <input type="hidden" name="topup" value="1">
        
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-credit-card"></i> Proceed to Payment
        </button>
    </form>
</div>

<?php include "footer.php"; ?>

<script>
function openTopupModal() {
    document.getElementById('modalBackdrop').classList.add('show');
    document.getElementById('topupModal').classList.add('show');
}

function closeTopupModal() {
    document.getElementById('modalBackdrop').classList.remove('show');
    document.getElementById('topupModal').classList.remove('show');
}

function setAmount(amount) {
    document.getElementById('ammount').value = amount;
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.classList.remove('active');
        if(btn.textContent === '$' + amount) {
            btn.classList.add('active');
        }
    });
}

document.getElementById('modalBackdrop').addEventListener('click', closeTopupModal);
</script>