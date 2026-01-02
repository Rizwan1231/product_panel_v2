<?php
session_start();
include "../init.php";
if(!isLoggedIn())
{
  redirect("../index.php");
  exit();
}

$userData = getUserData();
$userId = $userData['id'];
$currentBalance = $userData['credits'];

// Handle topup submission
if(isset($_POST['topup']) && !empty($_POST['ammount']))
{
    $amount = trim(intval($_POST['ammount']));
    if($amount < 10)
    {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Minimum amount is $10" ));
        exit();
    }
    $rdata = array( "user_id" => $userId, "ammount" => $amount );
    $db->query("INSERT INTO `invoices` (`user_id`, `products_data`, `recharge_data`, `date`) VALUES ('%d', '', '%s', '%d')", $userId, json_encode($rdata), time());
    $invoice_id = $db->inserted_id();
    echo json_encode(array( "status" => "success", "message" => "Invoice generated. Redirecting...", "redirect" => 'invoice.php?invoice_id=' . encrypt($invoice_id) ));
    exit();
}

include "header.php";
?>

<style>
/* Page Container */
.funds-container {
    padding: 15px;
    max-width: 600px;
    margin: 0 auto;
}

/* Balance Card */
.balance-card {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    border-radius: 12px;
    padding: 25px 20px;
    color: white;
    text-align: center;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.balance-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 100%;
    height: 200%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.balance-label {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.current-balance {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
    position: relative;
}

/* Add Funds Card */
.add-funds-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.add-funds-card h4 {
    font-size: 1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Info Box */
.info-box {
    background: #f0f7ff;
    border-left: 3px solid #667eea;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 0.85rem;
    color: #4a5568;
}
.info-box i {
    color: #667eea;
    margin-right: 5px;
}

/* Amount Buttons Grid */
.amount-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
.amount-btn {
    padding: 12px 8px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}
.amount-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.amount-btn.selected {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: scale(1.02);
}

/* Custom Amount Input */
.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 8px;
}
.custom-amount-input {
    font-size: 1.1rem;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.3s;
}
.custom-amount-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.input-group-text {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    font-weight: 600;
    color: #718096;
}

/* Submit Button */
.submit-btn {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    padding: 14px 30px;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(72, 187, 120, 0.3);
}
.submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Payment Methods Info */
.payment-methods {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e8ecf1;
}
.payment-methods-title {
    font-size: 0.8rem;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    text-align: center;
}
.payment-icons {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}
.payment-icon {
    background: #f8fafc;
    padding: 8px 15px;
    border-radius: 6px;
    font-size: 0.8rem;
    color: #4a5568;
    display: flex;
    align-items: center;
    gap: 6px;
}
.payment-icon i {
    font-size: 1.1rem;
}
.payment-icon .fa-cc-visa { color: #1a1f71; }
.payment-icon .fa-cc-mastercard { color: #eb001b; }
.payment-icon .fa-paypal { color: #003087; }
.payment-icon .fa-bitcoin { color: #f7931a; }

/* Responsive */
@media (max-width: 768px) {
    .funds-container {
        padding: 10px;
    }
    
    .balance-card {
        padding: 20px 15px;
        margin-bottom: 15px;
    }
    .balance-label {
        font-size: 0.8rem;
    }
    .current-balance {
        font-size: 2rem;
    }
    
    .add-funds-card {
        padding: 15px;
    }
    .add-funds-card h4 {
        font-size: 0.95rem;
    }
    
    .info-box {
        padding: 10px 12px;
        font-size: 0.8rem;
        margin-bottom: 15px;
    }
    
    .amount-buttons {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 15px;
    }
    .amount-btn {
        padding: 10px 5px;
        font-size: 0.9rem;
        border-radius: 6px;
    }
    
    .form-group label {
        font-size: 0.8rem;
    }
    .custom-amount-input {
        font-size: 1rem;
        padding: 10px;
    }
    
    .submit-btn {
        padding: 12px 25px;
        font-size: 0.95rem;
    }
    
    .payment-methods {
        margin-top: 15px;
        padding-top: 15px;
    }
    .payment-icons {
        gap: 10px;
    }
    .payment-icon {
        padding: 6px 12px;
        font-size: 0.75rem;
    }
}

@media (max-width: 400px) {
    .amount-buttons {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .payment-icons {
        flex-direction: column;
        align-items: center;
    }
    .payment-icon {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-wallet"></i> Add Funds</h1>
        </div>

        <div class="funds-container">
            <!-- Current Balance -->
            <div class="balance-card">
                <div class="balance-label">Current Balance</div>
                <div class="current-balance">$<?= number_format($currentBalance, 2); ?></div>
            </div>

            <!-- Add Funds Form -->
            <div class="add-funds-card">
                <h4><i class="fas fa-plus-circle"></i> Add Funds to Account</h4>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i> 
                    Select an amount or enter custom. Minimum: <strong>$10</strong>
                </div>

                <form method="POST" id="topupForm" class="ajaxform_with_redirect">
                    <!-- Quick Amount Selection -->
                    <div class="amount-buttons">
                        <button type="button" class="amount-btn" onclick="setAmount(10)">$10</button>
                        <button type="button" class="amount-btn" onclick="setAmount(25)">$25</button>
                        <button type="button" class="amount-btn" onclick="setAmount(50)">$50</button>
                        <button type="button" class="amount-btn" onclick="setAmount(100)">$100</button>
                        <button type="button" class="amount-btn" onclick="setAmount(250)">$250</button>
                        <button type="button" class="amount-btn" onclick="setAmount(500)">$500</button>
                    </div>

                    <!-- Custom Amount Input -->
                    <div class="form-group">
                        <label for="ammount">Custom Amount (USD)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" 
                                   class="form-control custom-amount-input" 
                                   name="ammount" 
                                   id="ammount" 
                                   placeholder="Enter amount" 
                                   min="10" 
                                   step="1" 
                                   required>
                        </div>
                    </div>

                    <input type="hidden" name="topup" value="1">
                    
                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-arrow-right"></i> Continue to Payment
                    </button>
                </form>

                <!-- Payment Methods -->
                <div class="payment-methods">
                    <div class="payment-methods-title">Accepted Payment Methods</div>
                    <div class="payment-icons">
                        <div class="payment-icon">
                            <i class="fab fa-cc-visa"></i> Visa
                        </div>
                        <div class="payment-icon">
                            <i class="fab fa-cc-mastercard"></i> Mastercard
                        </div>
                        <div class="payment-icon">
                            <i class="fab fa-paypal"></i> PayPal
                        </div>
                        <div class="payment-icon">
                            <i class="fab fa-bitcoin"></i> Crypto
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include "footer.php"; ?>

<script>
function setAmount(amount) {
    document.getElementById('ammount').value = amount;
    
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('selected');
        if(btn.textContent === '$' + amount) {
            btn.classList.add('selected');
        }
    });
}

// Update selected button when typing custom amount
$('#ammount').on('input', function() {
    var value = $(this).val();
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('selected');
        if(btn.textContent === '$' + value) {
            btn.classList.add('selected');
        }
    });
});
</script>