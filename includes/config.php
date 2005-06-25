<?php
/**
 * This file loads configuration settings from the data file settings.php and
 * sets up some needed variables.
 *
 * The settings.php file is created during installation using the web-based db
 * setup page (install/index.php).
 *
 * <b>Note:</b>
 * DO NOT EDIT THIS FILE!
 *
 * (Versions 0.9.44 and older required users to edit this file to configure
 * WebCalendar.  Version 0.9.45 and later requires editing the settings.php
 * file instead.)
 *
 * @version $Id$
 * @package WebCalendar
 */

/**
 * Prints a fatal error message to the user along with a link to the
 * Troubleshooting section of the WebCalendar System Administrator's Guide.
 *
 * Execution is aborted.
 *
 * @param string $error The error message to display
 *
 * @internal We don't normally put functions in this file.  But, since this
 *           file is included before some of the others, this function either
 *           goes here or we repeat this code in multiple files.
 */
function die_miserable_death ( $error )
{
global $TROUBLE_URL;
  echo "<html><head><title>WebCalendar: Fatal Error</title></head>\n" .
    "<body><h2>WebCalendar Error</h2>\n" .
    "<p>$error</p>\n<hr />" .
    "<p><a href=\"$TROUBLE_URL\" target=\"_blank\">" .
    "Troubleshooting Help</a></p></body></html>\n";
  exit;
}




?>
