<style>
	*,
*:after,
*:before {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}

.clearfix:before,
.clearfix:after {
	content: " ";
	display: table;
}

.clearfix:after {
	clear: both;
}

body {
	font-family: sans-serif;
	background: #f6f9fa;
}

h1 {
	color: #ccc;
	text-align: center;
}
h4.payment-title {
    text-align: left;
    padding: 10px 0px 30px 30px;
    font-weight: 500;
    color: #5e6977;
}
a {
  color: #ccc;
  text-decoration: none;
  outline: none;
}

.tab_container {
    width: 100%;
    position: relative;
    border: 1px solid #eee;
    border-radius: 7px;
  background:#fff;
}

input, section {
  clear: both;
  padding-top: 10px;
  display: none;
}

label {
  font-weight: 700;
  font-size: 14px;
  display: block;
  float: left;
  padding: 20px 14px;
  color: #8d8d8d;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  background: #fff;
  margin-bottom: 2px;
      border-top-left-radius: 7px;
    border-top-right-radius: 7px;
  border-bottom:2px solid #eee;

}

#tab1:checked ~ #content1,
#tab2:checked ~ #content2,
#tab3:checked ~ #content3,
#tab4:checked ~ #content4 {
  display: block;
  padding: 20px 0 0 0;
  color: #999;
}

.tab_container .tab-content h3  {
  text-align: center;
}

.tab_container [id^="tab"]:checked + label {
  background: #fff;
  border-bottom:2px solid #358ed7;
  color: #358ed7;
}

.tab_container [id^="tab"]:checked + label>span.numberCircle {
  border: 2px solid #358ed7;
  border-radius: 50%;
    width: 30px;
    height: 30px;
    padding: 3px 5px;
    text-align: center;
    font-size: 10px;
    margin: 3px 8px
}

label:hover {
background-color:#eee;
  border-radius:0px;
}

.numberCircle {
    border-radius: 50%;
    width: 30px;
    height: 30px;
    padding: 3px 5px;
    border: 2px solid #8d8d8d;
    text-align: center;
    font-size: 10px;
    margin: 3px 8px
}


.row-payment-method {
  margin: 0px 0px 0px 0px;
  padding: 22px 0px 11px 0px;
  text-align: left;
  display: table;
}
.payment-row {
  background-color: #f5f6fa;
  padding-left: 30px;
  padding-right: 30px;
      width: 100%;
}
.payment-row-last {
  margin-left: 30px;
  margin-right: 30px;
  width: 100%;
}

.payment-padding-right {
  
}
.select-icon {
  display: table-cell;
  vertical-align: top;
  text-align: left;
  padding-left: 0px;
  padding-top: 0px;
  padding-right: 0px;
  padding-bottom: 0px;
  width: 24px;
  height: 24px;
}

.select-txt {
  display: table-cell;
  vertical-align: middle;
  word-wrap: break-word;
  height: 60px;
  text-align: left;
  padding-left: 15px;
  font-size: 12pt;
}
.select-logo {
  padding-right: 0px;
  vertical-align: top;
      right: 35px;
    position: absolute;
}
.select-logo-sub {
  display: table-cell;
  vertical-align: middle;
}
.logo-spacer {
  padding-right: 13px;
}
.pymt-type-name {
  font-weight: 500;
  font-size: 12pt;
  padding-bottom: 8px;
  color: #5a6977;
}
.pymt-type-desc {
  padding-bottom: 22px;
  width:70%;
  color: #747474;
  font-size:14px;
}
.hr {
  border-bottom: 1px solid #ebf0f5;
  padding-bottom: 5px;
}
.form-cc {
  display: table;
  width: 100%;
  text-align: left;
  padding: 0px 0px 30px 30px;
}
.row-cc {
  display: table;
  width: 100%;
  padding-bottom: 7px;
}
.cc-txt {
  border-color: #e1e8ee;
  width: 100%;
}
.input {
  border-radius: 5px;
  border-style: solid;
  border-width: 2px;
  height: 38px;
  padding-left: 15px;
  font-weight: 600;
  font-size: 11pt;
  color: #5e6977;
 
}
input[type="text"] {
   display: initial;
  padding:15px
}
.text-validated {
  border-color: #7DC855;
  background-image: url("https://www.dropbox.com/s/1mve74fafiwsae1/icon-tick.png?raw=1");
  background-repeat: no-repeat;
  background-position: right 18px center;
}
.cc-ddl {
  border-color: #f0f4f7;
  background-color: #f0f4f7;
      width: 100px;
    margin-right: 10px;

  
}
.cc-title {
  font-size: 10.5pt;
  padding-bottom: 8px;
}
.cc-field {
  padding-top: 15px;
  padding-right: 30px;
  display: table-cell;
}
.button-master-container, .button-master-container:hover {
  display: table;
  width: 100%;
  border-top: 1px solid #e1e8ee;
  height: 60px;
  vertical-align: bottom;
}
.button-container {
  width: 50%;
  display: table-cell;
  text-align: center;
  vertical-align: middle;
}
a, a:hover {
  color: inherit;
  text-decoration: inherit;
}
.button-container:hover {
background-color:#eee;
  cursor:pointer;
}
.button-finish {
  border-left: 1px solid #e1e8ee;
  color: #4b9325;
  font-weight: 500;
  font-size: 12pt;
  /* background-image: url("https://www.dropbox.com/s/10d95otbo48r0hh/icon-next.png?raw=1"); */
  background-repeat: no-repeat;
  background-position: right 50px center;
}
.cvv-tooltip-img {
  display: inline-block;
  vertical-align: middle;
  padding-left: 17px;
}
input[id^="radio"]{
   display:none;
}

input[id^="radio"] + label
{
    background-image:url("https://www.dropbox.com/s/mnwbybfl4pnzoi4/radio-inactive.png?raw=1");
    height: 26px;
    width: 24px;
    display:inline-block;
    padding: 0 0 0 0px;
    cursor:pointer;
    border-radius: 50%;
}

input[id^="radio"]:checked + label
{
    background-image:url("https://www.dropbox.com/s/8634yi8i1s7fx7w/radio-active.png?raw=1");
  height: 26px;
    width: 24px;
    display:inline-block;
    padding: 0 0 0 0px;
    cursor:pointer;
}
p.credit {
  text-align:center;
  color: #ccc;
}

.top10 {
	  padding-top: 10px;
  }
  
@media only screen and (max-width: 720px) {
  .select-logo {
    display: none;
  }
  .top10m {
	  padding-top: 10px;
  }
}

</style>

		<div class="tab_container">			
			<input id="tab1" type="radio" name="tabs">
      <label for="tab1"><span class="numberCircle">1</span><span>Card Payment</span></label>

			<input id="tab2" type="radio" name="tabs">
			<label for="tab2"><span class="numberCircle">2</span><span>Binance</span></label>

			<input id="tab3" type="radio" name="tabs">
			<label for="tab3"><span class="numberCircle">3</span><span>Crypto</span></label>
			
			<input id="tab4" type="radio" name="tabs" checked>
			<label for="tab4"><span class="numberCircle">4</span><span>Balance</span></label>


        <section id="content4" class="tab-content">		
	<h4 class="payment-title hr row"><div class="col-sm-6">Invoice: idhere </div>
	<div class="col-sm-6"> Payment $35</div></h4>

	
	<div class="pymt-radio">
      
    <div class="row-payment-method payment-row-last">
      <div class="select-icon">
        <input type="radio" id="radio2" name="payment_balance" value="acc_bal" checked>
        <label for="radio2"></label>
      </div>
      <div class="select-txt">
        <p class="pymt-type-name">Pay now using your account ballance.</p>
        <p class="pymt-type-desc">Pay Now using your Account Balance" is a convenient and secure payment option that allows you to make payments directly from your account balance. With this feature, you no longer have to worry about using your credit card or entering your billing information.</p>
      </div>
      <div class="select-logo">
        <div class="select-logo-sub">
        <img style="width: 43px;" src="./img/accbal.png" alt="Account Balance"/></div>
      </div>
     </div>
	</div>
 
			
            
    <div class="button-master-container">
      <div class="button-container"><a href="javascript:void(0);" data-dismiss="modal">Return to Portal</a>
      </div>
      <div class="button-container button-finish"><a href="coinbase/stripe.php?invoice=idhere" target="_blank" title="Pay Now">Complete Order</a>
      </div>
    </div>
    </section>

			<section id="content1" class="tab-content">
				
	<h4 class="payment-title hr row"><div class="col-sm-6">Invoice: idhere </div>
	<div class="col-sm-6"> Payment $35</div></h4>

	
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
          <img src="https://www.dropbox.com/s/by52qpmkmcro92l/logo-visa.png?raw=1" alt="Visa"/>
        </div>
        <div class="select-logo-sub">
        <img src="https://www.dropbox.com/s/6f5dorw54xomw7p/logo-mastercard.png?raw=1" alt="MasterCard"/></div>
      </div>
     </div>
	</div>
 
			
            
    <div class="button-master-container">
      <div class="button-container"><a href="javascript:void(0);" data-dismiss="modal">Return to Portal</a>
      </div>
      <div class="button-container button-finish"><a href="coinbase/stripe.php?invoice=idhere" target="_blank" title="Disabled At The Moment.">Complete Order</a>
      </div>
    </div>
	
				
			</section>

			<section id="content2" class="tab-content">
	
	<h4 class="payment-title hr row"><div class="col-sm-6">Invoice: idhere </div>
	<div class="col-sm-6"> Payment $35</div></h4>
	
	<div class="pymt-radio">
      
    <div class="row-payment-method payment-row-last">
      <div class="select-icon">
        <input type="radio" id="radio2" name="binance" value="usdt" checked>
        <label for="radio2"></label>
      </div>
      <div class="select-txt">
        <p class="pymt-type-name">Binance Pay - <a style="color:#358ed7;" href="https://www.youtube.com/watch?v=1HzP94hEYrE" target="_blank">How to do?</a> - ( <a style="color:#358ed7;" href="https://www.binance.com/en/activity/referral-entry/CPA?fromActivityPage=true&ref=CPA_00FIKDTB7T" target="_blank" title="Join using our reffer link We\'ll both get a 100 USDT cashback voucher!">Ref</a> )</p>
        <p class="pymt-type-desc">Safe money transfer using your binance p2p account.
		Pay by binance app via QR code or by web checkout option <b>USDT</b> coin accept only. (instantly activation)</p>
      </div>
      <div class="select-logo">
        <div class="select-logo-sub logo-spacer">
          <img src="https://curl.pk/img/u/bac1c4f7f8b12b93330b22fef7b8f6355328.png" width="120px" alt="Binance"/>
        </div>
      </div>
     </div>
	</div>
 
			
            
    <div class="button-master-container">
      <div class="button-container"><a href="javascript:void(0);" data-dismiss="modal">Return to Portal</a>
      </div>
      <div class="button-container button-finish"><a href="coinbase/binance.php?invoice=idhere" target="_blank" title="The feature currency">Complete Order</a>
      </div>
    </div>
	
			</section>

			<section id="content3" class="tab-content">
			
	<h4 class="payment-title hr row"><div class="col-sm-6">Invoice: idhere </div>
	<div class="col-sm-6"> Payment $35</div></h4>
	
	<div class="pymt-radio">
      
    <div class="row-payment-method payment-row-last">
      <div class="select-icon">
        <input type="radio" id="radio2" name="crypto" value="usdt" checked>
        <label for="radio2"></label>
      </div>
      <div class="select-txt">
        <p class="pymt-type-name">Crypto Payments - <a style="color:#358ed7;" href="https://www.youtube.com/watch?v=HPoJhVNSHdk" target="_blank">How to do?</a></p>
        <p class="pymt-type-desc">You'll redirect to coinbase checkout page where you need put your email for order complete confirmation,
		you can pay via coinbase or any other wallet to giving external address <b>BTC, ETH, USDT</b> coin accepted.  (auto activation after network confirmation)</p>
      </div>
      <div class="select-logo" style="margin-top: -10px;">
        <div class="select-logo-sub logo-spacer">
          <img src="https://curl.pk/img/u/4f8b9a8812eb3bc48230dede340215a4486.png" width="110px" hieght="45px" alt="BTC & ETH"/>
        </div>
      </div>
     </div>
	</div>
 
			
            
    <div class="button-master-container">
      <div class="button-container"><a href="javascript:void(0);" data-dismiss="modal">Return to Portal</a>
      </div>
      <div class="button-container button-finish"><a href="coinbase/index.php?id=idhere" target="_blank" title="The feature currency">Complete Order</a>
      </div>
    </div>
	
	
			</section>

		</div>