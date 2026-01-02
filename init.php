<?php
define( 'ROOT_PATH', str_replace( "\\", "/", dirname( __file__ ) ) . '/' ); 
define( 'INCLUDES_PATH', ROOT_PATH . 'includes' . '/' ); 

require ( ROOT_PATH . "second_init.php" ); 
setReferral();

$currentUrl = getCurrentUrlPage(0, 1);
$currentPage = getCurrentUrlPage(1, 0);

/*
if($currentPage && !empty($currentPage) && $currentPage == "products.php" && $currentUrl == $sub_url) {
 redirect($client_url);
 exit();
}
*/
?>
