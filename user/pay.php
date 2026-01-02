<?php
if (basename($_SERVER['SCRIPT_FILENAME']) == 'pay.php') {
    exit('Direct access not allowed');
}
?>
<link rel="stylesheet" href="../css/paynow.css" />
<div class="tab_container">
  <input id="tab1" type="radio" name="tabs">
  <label for="tab1">
    <span class="numberCircle">1</span>
    <span>Card</span>
  </label>
  <input id="tab2" type="radio" name="tabs">
  <label for="tab2">
    <span class="numberCircle">2</span>
    <span>Binance</span>
  </label>
  <input id="tab3" type="radio" name="tabs">
  <label for="tab3">
    <span class="numberCircle">3</span>
    <span>Crypto</span>
  </label>
  <input id="tab5" type="radio" name="tabs">
  <label for="tab5">
    <span class="numberCircle">4</span>
    <span>PayPal</span>
  </label>
  <input id="tab4" type="radio" name="tabs" checked>
  <label for="tab4">
    <span class="numberCircle">5</span>
    <span>Funds</span>
  </label>
<!--
  <input id="tab1" type="radio" name="tabs">
  <label for="tab1">
    <span class="numberCircle">4</span>
    <span>Card Payment</span>
  </label>
-->
  <section id="content5" class="tab-content">
    <h4 class="payment-title hr row">
      <div class="col-sm-6 invoiceid">--</div>
      <div class="col-sm-6 totalpayment">--</div>
    </h4>
    <div class="pymt-radio">
      <div class="row-payment-method payment-row-last">
        <div class="select-icon">
          <input type="radio" id="radio2" name="payment_balance" value="acc_bal" checked>
          <label for="radio2"></label>
        </div>
        <div class="select-txt">
          <p class="pymt-type-name">Pay using paypal balance.</p>
          <p class="pymt-type-desc">Safe payment online. Only pay using PayPal account balance for instantly Order Complete.</p>
        </div>
        <div class="select-logo">
          <div class="select-logo-sub">
            <img style="width: 66px;" src="https://www.dropbox.com/s/pycofx0gngss4ef/logo-paypal.png?raw=1" alt="Account Balance" />
          </div>
        </div>
      </div>
    </div>
    <div class="button-master-container">
      <div class="button-container" onclick="closeModel();">
        <a href="javascript:void(0);">Return to Portal</a>
      </div>
      <div class="button-container button-finish"">
        <a class="checkoutlink" href="coinbase/paypal.php" target="_blank" title="The feature currency">PayPal</a>
      </div>
    </div>
  </section>
  
  <section id="content4" class="tab-content">
    <h4 class="payment-title hr row">
      <div class="col-sm-6 invoiceid">--</div>
      <div class="col-sm-6 totalpayment">--</div>
    </h4>
    <div class="pymt-radio">
      <div class="row-payment-method payment-row-last">
        <div class="select-icon">
          <input type="radio" id="radio2" name="payment_balance" value="acc_bal" checked>
          <label for="radio2"></label>
        </div>
        <div class="select-txt">
          <p class="pymt-type-name">Pay using your account balance.</p>
          <p class="pymt-type-desc">Pay Now using your Account Balance" is a convenient and secure payment option that allows you to make payments directly from your account balance. With this feature, you no longer have to worry about using your credit card or any other billing method.</p>
        </div>
        <div class="select-logo">
          <div class="select-logo-sub">
            <img style="width: 43px;" src="../img/accbal.png" alt="Account Balance" />
          </div>
        </div>
      </div>
    </div>
    <div class="button-master-container">
      <div class="button-container" onclick="closeModel();">
        <a href="javascript:void(0);">Return to Portal</a>
      </div>
      <div class="button-container button-finish" onclick="processPurchase('acc_bal', this);">
        <a href="javascript:void(0);" class="paywithbal" title="Pay Now">Complete Order</a>
      </div>
    </div>
  </section>
  <section id="content1" class="tab-content">
    <h4 class="payment-title hr row">
      <div class="col-sm-6 invoiceid">--</div>
      <div class="col-sm-6 totalpayment">--</div>
    </h4>
    <div class="pymt-radio">
      <div class="row-payment-method payment-row-last">
        <div class="select-icon">
          <input type="radio" id="radio2" name="payment_card" value="cr" checked>
          <label for="radio2"></label>
        </div>
        <div class="select-txt">
          <p class="pymt-type-name">Credit Card</p>
          <p class="pymt-type-desc">Safe money transfer using your bank account. Safe payment online. Credit card needed. Visa, Maestro, Discover, American Express</p>
        </div>
        <div class="select-logo">
          <div class="select-logo-sub logo-spacer">
            <img src="../img/logo-visa.png" alt="Visa" />
          </div>
          <div class="select-logo-sub">
            <img src="../img/logo-mastercard.png" alt="MasterCard" />
          </div>
        </div>
      </div>
    </div>
    <div class="button-master-container">
      <div class="button-container" onclick="closeModel();">
        <a href="javascript:void(0);" data-dismiss="modal">Return to Portal</a>
      </div>
      <div class="button-container button-finish">
        <a class="checkoutlink" href="coinbase/stripe.php" target="_blank" title="Disabled At The Moment.">Complete Order</a>
      </div>
    </div>
  </section>
  <section id="content2" class="tab-content">
    <h4 class="payment-title hr row">
      <div class="col-sm-6 invoiceid">--</div>
      <div class="col-sm-6 totalpayment">--</div>
    </h4>
    <div class="pymt-radio">
      <div class="row-payment-method payment-row-last">
        <div class="select-icon">
          <input type="radio" id="radio2" name="binance" value="usdt" checked>
          <label for="radio2"></label>
        </div>
        <div class="select-txt">
          <p class="pymt-type-name">Binance Pay - <a style="color:#358ed7;" href="https://www.youtube.com/watch?v=1HzP94hEYrE" target="_blank">How to do?</a> - ( <a style="color:#358ed7;" href="https://www.binance.com/en/activity/referral-entry/CPA?fromActivityPage=true&ref=CPA_00FIKDTB7T" target="_blank" title="Join using our reffer link We\'ll both get a 100 USDT cashback voucher!">Ref</a> ) </p>
          <p class="pymt-type-desc">Safe money transfer using your binance p2p account. Pay by binance app via QR code or by web checkout option <b>USDT</b> coin accept only. (instantly activation) </p>
        </div>
        <div class="select-logo">
          <div class="select-logo-sub logo-spacer">
            <img src="../img/binance.png" width="120px" alt="Binance" />
          </div>
        </div>
      </div>
    </div>
    <div class="button-master-container">
      <div class="button-container" onclick="closeModel();">
        <a href="javascript:void(0);" data-dismiss="modal">Return to Portal</a>
      </div>
      <div class="button-container button-finish">
        <a class="checkoutlink" href="coinbase/binance.php" target="_blank" title="The feature currency">Complete Order</a>
      </div>
    </div>
  </section>
  <section id="content3" class="tab-content">
    <h4 class="payment-title hr row">
      <div class="col-sm-6 invoiceid">--</div>
      <div class="col-sm-6 totalpayment">--</div>
    </h4>
    <div class="pymt-radio">
      <div class="row-payment-method payment-row-last">
        <div class="select-icon">
          <input type="radio" id="radio2" name="crypto" value="usdt" checked>
          <label for="radio2"></label>
        </div>
        <div class="select-txt">
          <p class="pymt-type-name">Crypto Payments - <a style="color:#358ed7;" href="https://www.youtube.com/watch?v=HPoJhVNSHdk" target="_blank">How to do?</a>
          </p>
          <p class="pymt-type-desc">No cryptomus account required
chose cryptomus option, pay via any private wallet to external address <b>BTC, ETH, USDT, BNB, Others</b> coin accepted. (auto activation after network confirmation)</p>
        </div>
        <div class="select-logo" style="margin-top: -10px;">
          <div class="select-logo-sub logo-spacer">
            <img src="../img/bitcoin.png" width="110px" hieght="45px" alt="BTC & ETH" />
          </div>
        </div>
      </div>
    </div>
    <div class="button-master-container">
  
      <div class="button-container button-finish">
        <a class="checkoutlink" href="coinbase/coinbase.php" target="_blank" title="The feature currency">1 - Coinbase Pay</a>
      </div>
      <div class="button-container button-finish">
        <a class="checkoutlink" href="coinbase/cryptomus.php" target="_blank" title="The feature currency">2 - Cryptomus Pay</a>
      </div>
    </div>
  </section>
</div>