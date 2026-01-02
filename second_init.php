<?php
@ini_set( 'default_socket_timeout', 10 );

/*
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
*/

define( 'IN_SCRIPT', true );
define( "SOFTWARE", 'XtreamMasters Panel' );
define( 'SCRIPT_NAME', 'XtreamMasters Panel' );
define( 'SCRIPT_AUTHOR', 'Muhammad Ashan' );
require (INCLUDES_PATH . "functions.php");
require INCLUDES_PATH . "emailtemplate.php";
require (INCLUDES_PATH . "mysql.php");
require (ROOT_PATH . "config.php");
if ( empty( $_INFO ) )
{
  exit( 0 );
}

$db = new db( $_INFO[ 'username' ], $_INFO[ 'password' ], $_INFO[ 'dbname' ], $_INFO[ 'hostname' ] );
