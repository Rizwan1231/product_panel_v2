<?php
session_start();
use GeoIp2\Database\Reader;
$rScriptPath = "/var/www/html/product_panel/";
require_once $rScriptPath.'vendor/autoload.php';


function getClientCountry($ip) {
	
	global $rScriptPath;
	$city = new Reader($rScriptPath.'vendor/GeoLite2.mmdb');
	
	try {
		$city = $city->city($ip);
		//print_r($city); exit;
		return $city->country->isoCode;
	} catch (Exception $e) {
		return 'US';
	}
	
}


function getClientRealIP() {
    $ipAddress = '';

    // Check for the `X-Forwarded-For` header
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        // The header may contain multiple IPs, we want the first one
        $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ipAddress = trim($ipArray[0]);
    }
    
    // Fallback to 'HTTP_X_REAL_IP'
    if (empty($ipAddress) && array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
        $ipAddress = trim($_SERVER['HTTP_X_REAL_IP']);
		$ipArray = explode(',', $ipAddress);
		$ipAddress = trim($ipArray[0]);
    }
    
    // Fallback to 'REMOTE_ADDR'
    if (empty($ipAddress) && array_key_exists('REMOTE_ADDR', $_SERVER)) {
        $ipAddress = trim($_SERVER['REMOTE_ADDR']);
    }

    return $ipAddress;
}

$rVistorCountry = getClientCountry(getClientRealIP()); //PK

$rVistorCountryBlock = ['BD', 'OM']; 

if (in_array($rVistorCountry, $rVistorCountryBlock)) {
	echo "503 Service is not available in your country..."; exit;
}


function makeBootstrapAlert($msg, $type)
{
 $html = '<div class="alert alert-'.$type.'">
  '.$msg.'
</div>';
 return $html;
}

function redirect($location)
{
 return header("Location: $location");
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function loginCheck($email, $password = "")
{
 global $db;
 $loginDone = false;
 if(empty($password)) {
	$db->query("SELECT * FROM `users` WHERE `email` = '%s' LIMIT 1", $email);
 }else {
	$db->query("SELECT * FROM `users` WHERE `email` = '%s' AND `password` = '%s' LIMIT 1", $email, md5($password));
	$loginDone = true;
 }
 $num = $db->num_rows();
 $userdata = $db->getdata();
 if($num < 1)
 {
  return false;
 }
 else
 {
	 if ($loginDone == true) {
		 $time = time();
		 $db->query("UPDATE `users` SET `last_login` = '$time' WHERE `email` = '%s'", $email);
	 }
	return $userdata;
 }
}

function getUserData()
{
 global $db;
 $email = $_SESSION['email'];
 $password = $_SESSION['password'];
 $uid = $_SESSION['user_id'];
 $db->query("SELECT * FROM `users` WHERE `email` = '%s' AND `password` = '%s' AND `id` = '%d' LIMIT 1", $email, md5($password), $uid);
 $num = $db->num_rows();
 $userdata = $db->getdata();
 if($num < 1)
 {
  return false;
 }
 elseif($userdata['is_banned'] == 1)
 {
  return false;
 }
 elseif($userdata['is_verified'] == 0)
 {
  return false;
 }
 else
 {
  return $userdata;
 }
}

function isLoggedIn()
{
 if(getUserData() == false)
 {
  return false;
 }
 else
 {
  return true;
 }
}

function base_url()
{
 $root = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
 $root .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
 $root = str_replace(array('admin/', 'user/'), '', $root);
 return $root;
}

function settingsInfo($key = "")
{
 global $db;
 $db->query("SELECT * FROM `settings`");
 $settings = $db->getall();
 $st = array();
 foreach($settings as $setting)
 {
  if(!empty($key))
  {
   if($key == $setting['setting_key'])
   {
   return $setting['setting_value'];
   }
  }
  else
  {
   $st[$setting['setting_key']] = $setting['setting_value'];
  }
 }
 return $st;
}

function activePage($page)
{
  $current_page = basename($_SERVER['PHP_SELF']);
    if (strstr($current_page, $page)) {
    return 'active';
  } else {
    return '';
  }
}
/*
function encrypt($string, $salt = null)
{
        define('ENCRYPTION_KEY', '4736d52f85bdb63e46bf7d6d41bbd551af36e1bfb7c68164bf81e2400d291319');
	if($salt === null) { $salt = hash('sha256', uniqid(mt_rand(), true)); }
	return base64_encode(openssl_encrypt($string, 'AES-256-CBC', ENCRYPTION_KEY, 0, str_pad(substr($salt, 0, 16), 16, '0', STR_PAD_LEFT))).':'.$salt;
}
function decrypt($string)
{
        define('ENCRYPTION_KEY', '4736d52f85bdb63e46bf7d6d41bbd551af36e1bfb7c68164bf81e2400d291319');
  	if( count(explode(':', $string)) !== 2 ) { return $string; }
	$salt = explode(":",$string)[1]; $string = explode(":",$string)[0];
	return openssl_decrypt(base64_decode($string), 'AES-256-CBC', ENCRYPTION_KEY, 0, str_pad(substr($salt, 0, 16), 16, '0', STR_PAD_LEFT));
}
*/
function encrypt($string) {
    $key = 'HKJyuNMcgEWWjhK';
    $ciphertext_raw = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    $ciphertext = base64_encode($ciphertext_raw);
    return bin2hex($ciphertext);
}

function decrypt($string) {
    $key = 'HKJyuNMcgEWWjhK';
    $string = hex2bin($string);
    $ciphertext_raw = base64_decode($string);
    $original_plaintext = openssl_decrypt($ciphertext_raw, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    return $original_plaintext;
}

function stripText($text, $words = 30) {
    /*
	$text = strip_tags($text);
    $word_count = str_word_count($text, 0);
    $words = min($words, $word_count);
    $words_array = str_word_count($text, 1);
    $limited_text = implode(' ', array_slice($words_array, 0, $words));
    if ($words < $word_count) {
        $limited_text .= '...';
    }
	*/
	$limited_text = mb_strimwidth($text, 0, $words, "...");
    return $limited_text;
}

function totalPrice($products)
{
 global $db;
 $price = 0;
 foreach($products as $product)
 {
  $db->query("SELECT `price` FROM `products` WHERE `id` = '%d'", decrypt($product));
  $price += $db->getdata()['price'];
 }
 return $price;
}

function productType($product)
{
 global $db;
  $db->query("SELECT `product_type` FROM `products` WHERE `id` = '%d'", decrypt($product));
  $type = $db->getdata()['product_type'];
  if($type == 1)
  {
   $ptype = 'Downloadable File';
  }
  else
  {
   $ptype = 'Text Data';
  }
 return $ptype;
}


function getProducts($json) {
  $products = array();
  $data = json_decode($json, true);
  foreach ($data as $item) {
    array_push($products, $item['product']);
  }
  return $products;
}

function getPInfos($products) {
    global $db;
    $pdatas = array();
    foreach ($products as $product) {
        $productid = decrypt($product['product']);
        $db->query("SELECT * FROM `products` WHERE `id` = '%d'", $productid);
        $pdata = $db->getdata();
        $pdatas[] = $pdata;
    }
    return $pdatas;
}

function userNameByID($id)
{
 global $db;
  $db->query("SELECT `email` FROM `users` WHERE `id` = '%d'", $id);
  $name = $db->getdata()['email'];
 if(!empty($name))
 {
 return $name;
 }
 else
 {
  return 'Unknown';
 }
}

function checkRdata($datas)
{
 $sections = explode("&&", $datas);
 $output = array();
 foreach ($sections as $section) {
    $fields = explode("<|>", $section);
    $value = $fields[1];
    if(!empty($value))
    {
    $output[] = $value;
    }
}
 return implode(", ", $output);
}

function productsNames($products)
{
    $names = array();
    $products = json_decode($products, true);
    foreach($products as $product)
    {
        if(isset($product['require_data']['license_domain']) && !empty($product['require_data']['license_domain'])) {
        $names[] = $product['product_name'] . '<br>Domain: ' . $product['require_data']['license_domain'];
        }else {
        $names[] = $product['product_name'];
        }
    }
    $list = '';
    if (count($names) > 1) {
        foreach($names as $key => $name)
        {
            $list .= ($key+1) . '. ' . $name . "<br>";
        }
    } else if (count($names) == 1) {
        $list .= $names[0];
    }
    else
    {
     $list = "Account Recharge";
    }
    return $list;
}

function totalOrdersOfRef($id, $joinDate)
{
 global $db;
 $invoices = array();
 $db->query("SELECT `invoice_id` FROM `orders` WHERE `user_id` = '%d' AND `date` > '%d' AND `status` = '%d'", $id, $joinDate, 1);
 $orders = $db->num_rows();
 foreach($db->getall() as $ordata)
 {
  $invoices[] = $ordata['invoice_id'];
 }
 $data = array( "total" => $orders, "invoices" => $invoices );
 return $data;
}

function totalOrdersOfRefAdmin($id)
{
    global $db;
    $invoices = array();
    $db->query("SELECT `id` FROM `users` WHERE `ref_by` = '%d'", $id);
    $user_ids = $db->getall();
    foreach ($user_ids as $user_id) {
        $db->query("SELECT `invoice_id` FROM `orders` WHERE `user_id` = '%d'", $user_id['id']);
        $orders = $db->num_rows();
        foreach($db->getall() as $ordata)
        {
            $invoices[] = $ordata['invoice_id'];
        }
    }
    return $invoices;
}

function myRefLink($id)
{
  global $client_url;
  $referralLink = $client_url.'?ref='.$id;
  return $referralLink;
}

function getMyComission($invoices)
{
  global $db;
  $totalPaid = 0;
  $percent = settingsInfo("REFERRAL_COMMISSION");
  if($percent && !empty($percent) && $percent != 0)
  {
  foreach ($invoices as $invoice)
  {
    $db->query("SELECT `products_data` FROM `invoices` WHERE `id` = '%d'", $invoice);
    $products = $db->getdata()['products_data'];
    $products = getProducts($products);
    $totalPaid += totalPrice($products);
  }
    $commission = $totalPaid * ($percent / 100);
    return $commission;
  }
  else
  {
   return 0;
  }
}

function saveCreditsLogs($id, $charge, $type, $detail)
{
 global $db;
  $db->query("SELECT `id`, `credits` FROM `users` WHERE `id` = '%d'", $id);
  $arr = $db->getdata(); 	
  $id = $arr['id'];
  $credits = $arr['credits']; 
 if ($type == '-') {
	 $balance = $credits-$charge;
 } elseif ($type == '+') {
	 $balance = $credits+$charge;
 } else {
	 die('invalid charge value');
 }
 $rowCharge = sprintf("%s%d", $type, $charge);
 $db->query("INSERT INTO `payment_logs` (`user_id`, `detail`, `charge`, `balance`, `date`) VALUES ('%d', '%s', '%s', '%d', '%d')", $id, $detail, $rowCharge, $balance, time());  
return true;
}


function setReferral()
{
    global $db;
    
    if(isset($_GET['ref']) && !empty($_GET['ref'])) {
        $refby = intval(decrypt($_GET['ref']));
        
        if($refby > 0) {
            $db->query("SELECT `id` FROM `users` WHERE `id` = '%d'", $refby);
            $refnum = $db->num_rows();
            
            if($refnum > 0) {
                $cookie_name = "refby";
                $cookie_value = $refby;
                $expiration_time = time() + (86400 * 30); // 30 days
                $path = "/"; // Cookie available throughout the entire website
                
                // Set cookie with proper parameters
                setcookie($cookie_name, $cookie_value, $expiration_time, $path, "", false, true);
                
                return true;
            }
        }
    }
    
    return false;
}


function checkUser($id)
{
  global $db;
    $db->query("SELECT `id` FROM `users` WHERE `id` = '%d'", $id);
    $refnum = $db->num_rows();
    if($refnum > 0)
   {
    return true;
   }
   else
   {
    return false;
   }
 return false;
}

function commissionCalculate($ammount)
{
  $percent = settingsInfo("REFERRAL_COMMISSION");
  if($percent && !empty($percent) && $percent != 0)
  {
    $commission = $ammount * ($percent / 100);
    return $commission;
  }
  else
  {
   return false;
  }
 return false;
}


function roundup($number)
{
    $rounded_number = floor($number);
    $decimal_value = $number - $rounded_number;
    if ($decimal_value >= 0.5) {
        $rounded_number += 1;
    }
    return $rounded_number;
}

function getTotalRefs($id)
{
    global $db;
    $db->query("SELECT COUNT(*) AS total_refs FROM `users` WHERE `ref_by` = '%d'", $id);
    $result = $db->getdata();
    return $result['total_refs'];
}

function getTotalOrders($id)
{
    global $db;
    $db->query("SELECT COUNT(*) AS total_orders FROM `orders` WHERE `user_id` = '%d'", $id);
    $result = $db->getdata();
    return $result['total_orders'];
}

function getCurrentUrlPage($returnOnlyPage = 0, $returnOnlyUrl = 1) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    $page = pathinfo($uri)['basename'];
    $url = $protocol . '://' . $host . $uri;
    if($returnOnlyPage == 1) {
     return $page;
    }elseif( $returnOnlyUrl == 1) {
    $url = dirname($url) . '/';
    return $url;
    }else {
     return false;
    }
}


function genSlug($productName) {
    $slug = strtolower($productName);
    $slug = preg_replace('/[\'"!@#$%^&*()+=]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}


function addNewUser($email, $password, $user_ip, $refby = NULL)
{
    global $db;
    $verify_key = generateRandomString(14);
    $ref_by = NULL;
    $refnum = 0;
    
    // Check if refby is set and valid
    if($refby !== NULL && $refby !== 'NULL' && !empty($refby))
    {
        $refby = intval($refby); // Ensure it's an integer
        if($refby > 0) {
            $db->query("SELECT `id` FROM `users` WHERE `id` = '%d'", $refby);
            $refnum = $db->num_rows();
            if($refnum > 0)
            {
                $ref_by = $refby;
            }
        }
    }
    
    // Insert with proper NULL handling
    if($ref_by !== NULL) {
        $db->query("INSERT INTO `users` (`email`, `password`, `is_verified`, `user_ip`, `date`, `verify_key`, `ref_by`) 
                    VALUES ('%s', '%s', '%d', '%s', '%d', '%s', '%d')", 
                    $email, md5($password), 1, $user_ip, time(), $verify_key, $ref_by);
    } else {
        $db->query("INSERT INTO `users` (`email`, `password`, `is_verified`, `user_ip`, `date`, `verify_key`, `ref_by`) 
                    VALUES ('%s', '%s', '%d', '%s', '%d', '%s', NULL)", 
                    $email, md5($password), 1, $user_ip, time(), $verify_key);
    }
    
    return true;
}


function scrambleEmail($email) {
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email; // Return as-is if not valid email
    }
    
    // Split email into parts
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain_full = $parts[1];
    
    // Split domain and extension
    $domain_parts = explode('.', $domain_full);
    $domain = $domain_parts[0];
    $extension = implode('.', array_slice($domain_parts, 1)); // Handle multi-part extensions like .co.uk
    
    // Process username
    $username_length = strlen($username);
    if ($username_length <= 2) {
        // Very short username, show first character only
        $scrambled_username = substr($username, 0, 1) . str_repeat('*', $username_length - 1);
    } elseif ($username_length <= 4) {
        // Short username, show first 2 characters
        $scrambled_username = substr($username, 0, 2) . str_repeat('*', $username_length - 2);
    } else {
        // Normal username, show first 4 characters
        $visible_chars = min(4, ceil($username_length * 0.3)); // Show max 4 chars or 30% of username
        $scrambled_username = substr($username, 0, $visible_chars) . str_repeat('*', min(5, $username_length - $visible_chars));
    }
    
    // Process domain
    $domain_length = strlen($domain);
    if ($domain_length <= 2) {
        $scrambled_domain = substr($domain, 0, 1) . str_repeat('*', $domain_length - 1);
    } else {
        $scrambled_domain = substr($domain, 0, 1) . str_repeat('*', min(4, $domain_length - 1));
    }
    
    // Process extension (show first and last character)
    $ext_length = strlen($extension);
    if ($ext_length <= 2) {
        $scrambled_extension = $extension; // Too short to hide
    } else {
        // Show first character and last character(s)
        $scrambled_extension = substr($extension, 0, 1) . str_repeat('*', min(2, $ext_length - 2)) . substr($extension, -1);
    }
    
    return $scrambled_username . '@' . $scrambled_domain . '.' . $scrambled_extension;
}

// Alternative simpler version
function scrambleEmailSimple($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    
    list($username, $domain_full) = explode('@', $email);
    
    // Username: show first 3-4 chars + asterisks
    $username_visible = min(4, max(2, floor(strlen($username) * 0.3)));
    $username_hidden = substr($username, 0, $username_visible) . str_repeat('*', 5);
    
    // Domain: show first char + asterisks + extension pattern
    $domain_parts = explode('.', $domain_full);
    $domain = $domain_parts[0];
    $extension = implode('.', array_slice($domain_parts, 1));
    
    $domain_hidden = substr($domain, 0, 1) . str_repeat('*', 4);
    $ext_hidden = '.' . substr($extension, 0, 1) . str_repeat('*', strlen($extension) - 2) . substr($extension, -1);
    
    return $username_hidden . '@' . $domain_hidden . $ext_hidden;
}

// Version with more control
function scrambleEmailAdvanced($email, $options = []) {
    $defaults = [
        'username_visible' => 4,      // How many chars to show from username
        'username_stars' => 5,         // How many asterisks for username
        'domain_visible' => 1,         // How many chars to show from domain
        'domain_stars' => 4,           // How many asterisks for domain
        'show_full_ext' => false       // Whether to show full extension
    ];
    
    $opts = array_merge($defaults, $options);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    
    list($username, $domain_full) = explode('@', $email);
    $domain_parts = explode('.', $domain_full);
    $domain = $domain_parts[0];
    $extension = implode('.', array_slice($domain_parts, 1));
    
    // Process each part
    $username_scrambled = substr($username, 0, min(strlen($username), $opts['username_visible'])) 
                         . str_repeat('*', $opts['username_stars']);
    
    $domain_scrambled = substr($domain, 0, min(strlen($domain), $opts['domain_visible'])) 
                       . str_repeat('*', $opts['domain_stars']);
    
    if ($opts['show_full_ext']) {
        $ext_scrambled = '.' . $extension;
    } else {
        $ext_scrambled = '.' . substr($extension, 0, 1) 
                        . str_repeat('*', max(0, strlen($extension) - 2)) 
                        . (strlen($extension) > 1 ? substr($extension, -1) : '');
    }
    
    return $username_scrambled . '@' . $domain_scrambled . $ext_scrambled;
}

/*
// Usage examples:
echo scrambleEmail('myemailaddress75675@gmail.com'); 
// Output: myem*****@g****.*m

echo scrambleEmail('john@example.com');
// Output: jo*****@e****.*m

echo scrambleEmail('ab@x.io');
// Output: a*@x*.*o

echo scrambleEmail('support@company.co.uk');
// Output: supp*****@c****.*k

// With custom options
echo scrambleEmailAdvanced('admin@website.org', [
    'username_visible' => 2,
    'username_stars' => 3,
    'domain_visible' => 2,
    'domain_stars' => 3
]);
// Output: ad***@we***.*g
*/

function getNextRenewalDate($startDate, $products) {
    $renewalDates = [];
    foreach ($products as $product) {
        $expiryDuration = $product['expiry_duration'] ?? null;
        $expiryDurationIn = $product['expiry_duration_in'] ?? null;
        if (empty($expiryDuration) || empty($expiryDurationIn)) {
            $renewalDates[] = '--';
            continue;
        }
        $nextRenewalDate = strtotime("+$expiryDuration $expiryDurationIn", $startDate);
        $renewalDates[] = date('d-m-Y', $nextRenewalDate);
    }
    return $renewalDates;
}

?>