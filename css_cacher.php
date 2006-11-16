<?php
/* $Id$ */
 require_once 'includes/classes/WebCalendar.class';
     
 $WebCalendar =& new WebCalendar ( __FILE__ );    
     
 include 'includes/config.php';    
 include 'includes/dbi4php.php';    
 include 'includes/functions.php';    
     
 $WebCalendar->initializeFirstPhase();    
     
 include "includes/$user_inc";
 include_once 'includes/access.php'; 
 include_once 'includes/validate.php';    
 include 'includes/translate.php';  
 include_once 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings ();

//if calling script uses 'guest', we must also
$guest = ( ! empty ( $_GET['login'] ) ? 'guest' : '' );
load_user_preferences ( $guest );

//we will cache css as default, but override from admin and pref
//by incrementing the webcalendar_csscache cookie value

$cookie = ( isset ( $_COOKIE['webcalendar_csscache'] ) ?
    $_COOKIE['webcalendar_csscache'] : 0 );

header( 'Content-type: text/css' ); 
header('Last-Modified: '. date('r', time() + $cookie ) );
header('Expires: ' . date( 'D, j M Y H:i:s', time() +  86400 ) . ' UTC');
header('Cache-Control: Public');
header('Pragma: Public');

if ( ini_get ( 'zlib.output_compression' ) != 1 ) 
  ob_start( 'ob_gzhandler' );


include_once ( 'includes/styles.php' );

?>