<?php
$support_email = 'support@xtream-masters.com';

if (isset($rWhatsapp)) {
	$support_number = $rWhatsapp;
} else {
	$support_number = '+447307530066';
}

$brand_name = "Xtream-Masters";

$otpVerifCode = '
<!DOCTYPE html>
<html lang="en" style="margin:0;padding:0;font-family:\'Open Sans\', Arial, sans-serif;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login Verification</title>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet" />
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:\'Open Sans\', Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f0f4f8;padding:20px;">
    <tr>
      <td align="center">
        <!-- Main Container -->
        <table width="100%" max-width="600px" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
          
          <!-- Header -->
          <tr>
            <td style="padding:30px;text-align:center; background: linear-gradient(135deg, #FF5722, #f66031); color:#ffffff;">
              <h1 style="margin:0;font-family:\'Open Sans\', Arial, sans-serif;font-size:24px;font-weight:700;">'.$brand_name.'</h1>
            </td>
          </tr>
          
          <!-- Content -->
          <tr>
            <td style="padding:40px 30px 20px 30px;">
              <h2 style="margin-top:0;margin-bottom:20px;font-family:\'Open Sans\', Arial, sans-serif;font-size:20px;font-weight:600;color:#333;">Hello,</h2>
              <p style="font-size:16px; line-height:1.5; color:#555;">We\'ve received a request to log in to your '.$brand_name.' account. Please use the verification code below to complete your login:</p>
              
              <!-- OTP Code -->
              <div style="margin:30px 0;text-align:center;">
                <span style="
                  display:inline-block;
                  font-family:\'Open Sans\', Arial, sans-serif;
                  font-size:36px;
                  font-weight:700;
                  letter-spacing:4px;
                  padding:20px 40px;
                  background-color:#2d3748;
                  color:#ffffff;
                  border-radius:8px;
                  box-shadow:0 4px 8px rgba(0,0,0,0.1);
                ">{otp_code}</span>
              </div>
              
              <p style="font-size:16px; line-height:1.5; color:#555;">If you did not initiate this login attempt, please disregard this email. Your account remains secure.</p>
              
              <p style="margin-top:30px; font-size:16px; color:#555;">If you need assistance, contact our support team at <a href="mailto:'.$support_email.'" style="color:#667eea;text-decoration:none;">'.$support_email.'</a> or call <a href="tel:'.$support_number.'" style="color:#667eea;text-decoration:none;">'.$support_number.'</a>.</p>
            </td>
          </tr>
          
          <!-- Footer -->
          <tr>
            <td style="padding:20px 30px; background-color:#f0f4f8; text-align:center; font-size:14px; color:#999;">
              © '.date("Y").' '.$brand_name.'. All rights reserved.
            </td>
          </tr>
        </table>
        <!-- End Main Container -->
      </td>
    </tr>
  </table>
</body>
</html>
';


?>