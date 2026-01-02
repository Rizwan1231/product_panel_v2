<?php
session_start();
require_once("./init.php");
use Snipworks\Smtp\Email;
include_once "Email.php";
include_once "email_templates.php";

$smtpEmail = "rubenmlillie99@gmail.com";
$smtpPassword = 'mluhpwycllfxyxrx';
//xm account here
$smtpEmail = "support@xtream-masters.com";
$smtpPassword = 's?V5QRF9ajZ';

function sendEmailViaSMTP($email, $replyto = "", $subject, $message)
{
    global $smtpEmail, $smtpPassword;
    if (!$smtpEmail || empty($smtpEmail) || empty($smtpPassword))
    {
        exit("SMTP email and password not set.");
    }
    if (empty($replyto))
    {
        $replyto = $smtpEmail;
    }
	
	
	/*
	$otpUrl = 'https://noreply.paksat.pk/mail_cdn?mail_type=custom&mailer_name='.$subject.'&body='.$message.'&emailt='.$email.'&reply_to='.$replyto;
    $content = file_get_contents($otpUrl, false, stream_context_create([
		'http' => [
			'timeout' => 5
		]
	]));
	*/
	
	$otpUrl = 'https://noreply.paksat.pk/mail_cdn';
	$postData = [
		'mail_type' => 'custom',
		'mailer_name' => 'Xtream-Masters LLC.',
		'subject' => $subject,
		'body' => $message,
		'emailt' => $email,
		'reply_to' => $replyto
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $otpUrl);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$content = curl_exec($ch);
	curl_close($ch);
	
	
	
    //$mail = new Email('smtp.gmail.com', 587);
	$mail = new Email('premium70.web-hosting.com', 587);
    $mail->setProtocol(Email::TLS)
        ->setLogin($smtpEmail, $smtpPassword)->setFrom($smtpEmail)->setSubject($subject)->setHtmlMessage($message)->addReplyTo($replyto)->addTo($email);



    if ($mail->send())
    {
        return true;
    }
    else
    {
        echo 'An error has occurred. Please check the logs below:' . PHP_EOL;
        print_r($mail->getLogs());
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    if (isset($_GET['action']))
    {
        switch ($_GET['action'])
        {
            case 'login':
                $email = trim($_POST['email']);
                $password = trim($_POST['password']);
                $captcha = trim($_POST['captcha']);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                    echo json_encode(array(
                        "status" => "info",
                        "message" => "<font color='red'>Enter valid email address.</font>"
                    ));
                    exit();
                }

                if (md5($captcha) != md5($_SESSION['captcha']))
                {
                    echo json_encode(array(
                        "status" => "info",
                        "message" => "<font color='red'>Invalid captcha code provided.</font>"
                    ));
                    exit();
                }

                // new method
                if (empty($_POST['password']))
                {
                    $otp_code = mt_rand(111111, 999999);
                    $check = loginCheck($email);
					if (!$check || empty($check))
					{
						$user_ip = $_SERVER["REMOTE_ADDR"];
						$refby = NULL;
						if (isset($_COOKIE['refby']) && !empty($_COOKIE['refby'])) {
							$refby = intval($_COOKIE['refby']);
							// Validate the referrer exists
							$db->query("SELECT `id` FROM `users` WHERE `id` = '%d'", $refby);
							if($db->num_rows() == 0) {
								$refby = NULL;
							}
						}
						addNewUser($email, $otp_code, $user_ip, $refby);
					}
                    else
                    {

                        $db->query("SELECT * FROM `users` WHERE `email` = '%s'", $email);
                        $uDt = $db->getdata();
                        $last_otp_req = $uDt['last_otp_req'];
                        $otp_tries = $uDt['otp_tries'];
                        $next_otp_req_after = $uDt['next_otp_req_after'];
                        if($otp_tries > 4) {
                          if(time() < $next_otp_req_after) {
                            $secs = $next_otp_req_after - time();
                            $mins = ceil($secs / 60);
                            echo json_encode(array(
                                "status" => "info",
                                "message" => "<font color='red'>Please wait for {$mins} minutes before try again.</font>",
                                "otp_sent" => 0,
                                "wait" => 1,
                                "mins" => $mins
                            ));
                            exit;
                          }else {
                           $db->query("UPDATE `users` SET `otp_tries` = 0', `next_otp_req_after` = NULL WHERE `email` = '%s'", $email);
                          }
                        }

                        // update password with otp as md5 encrypted
                        $db->query("SELECT * FROM `users` WHERE `email` = '%s'", $email);
                        $uDt = $db->getdata();
                        $last_otp_req = $uDt['last_otp_req'];
                        $otp_tries = $uDt['otp_tries'];
                        $next_otp_req_after = $uDt['next_otp_req_after'];

                        $minCheck = time() - 60;
                        $xSec = $last_otp_req - $minCheck;
                        if ($last_otp_req > $minCheck)
                        {
                            echo json_encode(array(
                                "status" => "info",
                                "message" => "<font color='red'>Please wait for {$xSec} second for new OTP.</font>",
                                "otp_sent" => 0
                            ));
                            exit;
                        }
                        $time = time();
                        $db->query("UPDATE `users` SET `password` ='%s', `last_otp_req` = '$time' WHERE `email` = '%s'", md5($otp_code) , $email);
                    }

                    // send email
                    $message = str_replace('{otp_code}', $otp_code, $otpVerifCode);
                    sendEmailViaSMTP($email, "", "Login verification OTP", $message);
                    echo json_encode(array(
                        "status" => "info",
                        "message" => "<font color='green'>Please check email for OTP.</font>",
                        "otp_sent" => 1
                    ));
                    exit;
                }
                if (empty($email) || empty($password))
                {
                    echo json_encode(array(
                        "status" => "info",
                        "message" => "<font color='red'>Email and otp is required.</font>"
                    ));
                    exit();
                }


                        $db->query("SELECT * FROM `users` WHERE `email` = '%s'", $email);
                        $uDt = $db->getdata();
                        $last_otp_req = $uDt['last_otp_req'];
                        $otp_tries = $uDt['otp_tries'];
                        $next_otp_req_after = $uDt['next_otp_req_after'];
                        if($otp_tries > 4) {
                          if(time() < $next_otp_req_after) {
                            $secs = $next_otp_req_after - time();
                            $mins = ceil($secs / 60);
                            echo json_encode(array(
                                "status" => "info",
                                "message" => "<font color='red'>Please wait for {$mins} minutes before try again.</font>",
                                "otp_sent" => 0,
                                "wait" => 1,
                                "mins" => $mins
                            ));
                            exit;
                          }else {
                           $db->query("UPDATE `users` SET `otp_tries` = 0', `next_otp_req_after` = NULL WHERE `email` = '%s'", $email);
                          }
                        }

                $check = loginCheck($email, $password);
                if (!$check || empty($check))
                {
                   $db->query("SELECT `otp_tries` FROM `users` WHERE `email` = '%s'", $email);
                   $otp_tries = $db->getdata() ['otp_tries'];
                   if($otp_tries > 4) {
                    // updating otp when exceeded retires
                    $db->query("UPDATE `users` SET `password` ='%s', `last_otp_req` = '$time' WHERE `email` = '%s'", md5($otp_code) , $email);

                    $db->query("UPDATE `users` SET `next_otp_req_after` = '%d' WHERE `email` = '%s'", strtotime("+15 minutes"), $email);
                   }else {
                    $db->query("UPDATE `users` SET `otp_tries` = '%d', `next_otp_req_after` = NULL WHERE `email` = '%s'", $otp_tries + 1, $email);
                   }
                    echo json_encode(array(
                        "status" => "danger",
                        "message" => "<font color='red'>Please enter valid OTP.</font>"
                    ));
                    exit();
                }
                else
                {
                    $db->query("UPDATE `users` SET `otp_tries` = 0, `next_otp_req_after` = NULL WHERE `email` = '%s'", $email);
                    $_SESSION['user_id'] = $check['id'];
                    $_SESSION['email'] = $check['email'];
                    $_SESSION['password'] = $password;
                    $token = encrypt($check['id'] . '|' . $email . '|' . $password . '|' . $check['is_admin']);
                    echo json_encode(array(
                        "status" => "success",
                        "message" => "Successfully loggedin.",
                        "token" => $token
                    ));
                    exit();
                }
            break;
            case 'signup':
                $email = trim($_POST['email']);
                $password = trim($_POST['password']);
                $captcha = trim($_POST['captcha']);

                if ($captcha != $_SESSION['captcha'])
                {
                    echo json_encode(array(
                        "status" => "info",
                        "message" => "<font color='red'>Invalid captcha code provided.</font>"
                    ));
                    exit();
                }
                if (empty($email) || empty($password))
                {
                    echo json_encode(array(
                        "status" => "info",
                        "message" => "<font color='red'>All fields are required.</font>"
                    ));
                    exit();
                }
                else
                {
                    $db->query("SELECT `id` FROM `users`  WHERE `email` = '%s'", $email);
                    $num = $db->num_rows();
                    if ($num > 0)
                    {
                        echo json_encode(array(
                            "status" => "info",
                            "message" => "This email address already in use."
                        ));
                        exit();
                    }
                    else
                    {
                        $is_banned = 0;
                        $is_verified = 1;
                        $verify_key = generateRandomString(14);
                        $user_ip = $_SERVER['REMOTE_ADDR'];
                        $date = time();
                        
						/*
						$refby = $_COOKIE['refby'];
                        $ref_by = 'NULL';
                        if (isset($refby))
                        {
                            $db->query("SELECT `name` FROM `users` WHERE `id` = '%d'", $refby);
                            $refnum = $db->num_rows();
                        }
                        if ($refnum > 0)
                        {
                            $ref_by = $refby;
                        }
                        $db->query("INSERT INTO `users` (`email`, `credits`, `password`, `is_banned`, `is_verified`, `verify_key`, `user_ip`, `ref_by`, `date`) VALUES ('%s', '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d')", $email, 0, md5($password) , $is_banned, $is_verified, $verify_key, $user_ip, $ref_by, $date);
						*/
						
						$ref_by = NULL;
						$refnum = 0;
						if (isset($_COOKIE['refby']) && !empty($_COOKIE['refby'])) 
						{
							$refby = intval($_COOKIE['refby']);
							if($refby > 0) {
								$db->query("SELECT `id` FROM `users` WHERE `id` = '%d'", $refby);
								$refnum = $db->num_rows();
								if ($refnum > 0)
								{
									$ref_by = $refby;
								}
							}
						}

						$db->query("INSERT INTO `users` (`email`, `credits`, `password`, `is_banned`, `is_verified`, `verify_key`, `user_ip`, `ref_by`, `date`) VALUES ('%s', '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d')", 
							$email, 0, md5($password), $is_banned, $is_verified, $verify_key, $user_ip, $ref_by, $date);

						
                        echo json_encode(array(
                            "status" => "success",
                            "message" => "Registration successfull please visit login tab."
                        ));
                        exit();
                    }
                }
            break;
            case 'isLoggedIn':
                if (isLoggedIn())
                {
                    echo json_encode(array(
                        "status" => "success",
                        "loggedin" => true
                    ));
                    exit();
                }
                else
                {
                    echo json_encode(array(
                        "status" => "success",
                        "loggedin" => false
                    ));
                    exit();
                }
            break;
            case 'makeInvoice':
                if (isLoggedIn())
                {
					unset($_SESSION['p_email']);
                    $products = $_POST['cartData'];
                    if (empty($products))
                    {
                        echo json_encode(array(
                            "status" => "info",
                            "message" => "No any item is in cart."
                        ));
                        exit();
                    }
                    $uid = getUserData() ['id'];
                    /*
                    // check if already unpaid invoice
                    $db->query("SELECT `id` FROM `invoices` WHERE `user_id` = '%d' AND `status` = '%d'", $uid, 0);
                    $num = $db->num_rows();
                    if($num > 0)
                    {
                    echo json_encode(array( "status" => "info", "message" => "You have already an unpaid invoice please pay it first." ));
                    exit();
                    }
                    */
                    $db->query("INSERT INTO `invoices` (`user_id`, `products_data`, `date`) VALUES ('%d', '%s', '%d')", $uid, json_encode($products) , time());
                    $invoice_id = $db->inserted_id();
                    if (!empty($invoice_id))
                    {
                        unset($_SESSION['cart']);
                        echo json_encode(array(
                            "status" => "success",
                            "message" => "Invoice successfully generated.",
                            "invoice_id" => encrypt($invoice_id)
                        ));
                        exit();
                    }
                    else
                    {
                        echo json_encode(array(
                            "status" => "danger",
                            "message" => "Failed to generate invoice please contact with administrator."
                        ));
                        exit();
                    }
                }
                else
                {
                    echo json_encode(array(
                        "status" => "failed",
                        "loggedin" => false
                    ));
                }
            break;
            default:
                echo json_encode(array(
                    "error" => "404 not found"
                ));
            break;
        }
    }
}
else
{
    echo json_encode(array(
        "error" => "404 not found"
    ));
}

?>