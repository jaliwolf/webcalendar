<?php
/*
 * $Id$
 *
 * Description:
 * This script is intended to be used outside of normal WebCalendar
 * use, typically as an iframe in another page.
 *
 * You must have public access enabled in System Settings to use this
 * page (unless you modify the $public_must_be_enabled setting below
 * in this file).
 *
 * Typically, this is how you would reference this page from another:
 *
 * <iframe height="250" width="300"
 *  scrolling="yes" src="upcoming.php"></iframe>
 *
 * By default (if you do not edit this file), events for the public
 * calendar will be loaded for either:
 *   - the next 30 days
 *   - the next 10 events
 *
 * Input parameters:
 * You can override settings by changing the URL parameters:
 *   - days: number of days ahead to look for events
 *   - cat_id: specify a category id to filter on
 *   - user: login name of calendar to display (instead of public
 *     user), if allowed by System Settings.  You must have the
 *     following System Settings configured for this:
 *       Allow viewing other user's calendars: Yes
 *       Public access can view others: Yes
 *
 * Security:
 * TBD
 */

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/connect.php";

load_global_settings ();

/*
 *
 * Configurable settings for this file.  You may change the settings
 * below to change the default settings.
 * This settings will likely move into the System Settings in the
 * web admin interface in a future release.
 *
 */

// Change this to false if you still want to access this page even
// though you do not have public access enabled.
$public_must_be_enabled = true;

// Do we include a link to view the event?  If so, what target
// should we use.
$display_link = true;
$link_target = '_top';


// Default time window of events to load
// Can override with "upcoming.php?days=60"
$numDays = 30;

// Max number of events to display
$maxEvents = 10;

// Login of calendar user to use
// '__public__' is the login name for the public user
$username = '__public__';

// Allow the URL to override the user setting such as
// "upcoming.php?user=craig"
$allow_user_override = false;

// Load layers
$load_layers = true;

// Load just a specified category (by its id)
// Leave blank to not filter on category (unless specified in URL)
// Can override in URL with "upcoming.php?cat_id=4"
$cat_id = '';

// End configurable settings...

// Set for use elsewhere as a global
$login = $username;
// Load user preferences for DISPLAY_UNAPPROVED
load_user_preferences ();
$get_unapproved = ! empty ( $DISPLAY_UNAPPROVED ) && $DISPLAY_UNAPPROVED == 'Y';

include "includes/translate.php";


if ( $public_must_be_enabled && $public_access != 'Y' ) {
  $error = translate ( "You are not authorized" ) . ".";
}

if ( $allow_user_override ) {
  $u = getValue ( "user", "[A-Za-z0-9_\.=@,\-]+", true );
  if ( ! empty ( $u ) ) {
    $username = $u;
    $login = $u;
    // We also set $login since some functions assume that it is set.
  }
}

$cat_id = '';
if ( $categories_enabled == 'Y' ) {
  $x = getIntValue ( "cat_id", true );
  if ( ! empty ( $x ) ) {
    $cat_id = $x;
  }
}

if ( $load_layers ) {
  load_user_layers ( $username );
}

//load_user_categories ();

// Calculate date range
$date = getIntValue ( "date", true );
if ( empty ( $date ) || strlen ( $date ) != 8 ) {
  // If no date specified, start with today
  $date = date ( "Ymd" );
}
$thisyear = substr ( $date, 0, 4 );
$thismonth = substr ( $date, 4, 2 );
$thisday = substr ( $date, 6, 2 );

$startTime = mktime ( 3, 0, 0, $thismonth, $thisday, $thisyear );

$x = getIntValue ( "days", true );
if ( ! empty ( $x ) ) {
  $numDays = $x;
}
// Don't let a malicious user specify more than 365 days
if ( $numDays > 365 ) {
  $numDays = 365;
}
$endTime = mktime ( 3, 0, 0, $thismonth, $thisday + $numDays,
  $thisyear );
$endDate = date ( "Ymd", $endTime );


/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( $username, $cat_id, $date );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $username, $date, $endDate, $cat_id );

// Print header without custom header and no style sheet
if ( ! empty ( $LANGUAGE ) ) {
  $charset = translate ( "charset" );
  $lang = languageToAbbrev ( ( $LANGUAGE == "Browser-defined" || 
    $LANGUAGE == "none" )? $lang : $LANGUAGE );
  if ( $charset != "charset" ) {
    echo "<?xml version=\"1.0\" encoding=\"$charset\"?>\n" .
      "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
      "\"DTD/xhtml1-transitional.dtd\">\n" .
      "<html xmlns=\"http://www.w3.org/1999/xhtml\" " .
      "xml:lang=\"$lang\" lang=\"$lang\">\n" .
      "<head>\n" .
      "<meta http-equiv=\"Content-Type\" content=\"text/html; " .
      "charset=$charset\" />\n";
  } else {
    echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n" .
      "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
      "\"DTD/xhtml1-transitional.dtd\">\n" .
      "<html xmlns=\"http://www.w3.org/1999/xhtml\" " .
      "xml:lang=\"en\" lang=\"en\">\n" .
      "<head>\n";
  }
} else {
  echo "<html>\n";
  $charset = "iso-8859-1";
}
echo "<title>".translate($application_name)."</title>\n";
 
?>
</head>
<body>
<?php
if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  echo "\n<br /><br />\n</body></html>";
  exit;
}
print "<dl>\n";

print "<!-- \nstartTime: $startTime\nendTime: $endTime\nstartDate: " .
  "$date\nnumDays: $numDays\nuser: $username\nevents: " . 
  count ( $events ) . "\nrepeated_events: " . 
  count ( $repeated_events ) . " -->\n";

$numEvents = 0;
for ( $i = $startTime; date ( "Ymd", $i ) <= date ( "Ymd", $endTime ) &&
  $numEvents < $maxEvents; $i += ( 24 * 3600 ) ) {
  $d = date ( "Ymd", $i );
  $entries = get_entries ( $username, $d, $get_unapproved );
  $rentries = get_repeating_entries ( $username, $d, $get_unapproved );
  print "<!-- $d " . count ( $entries ) . "/" . count ( $rentries ) . " -->\n";

  if ( count ( $entries ) > 0 || count ( $rentries ) > 0 ) {
    print "<!-- XXX -->\n";
    print "<dt>" . date_to_str ( $d ) . "</dt>\n<dd>";
    for ( $j = 0; $j < count ( $entries ) && $numEvents < $maxEvents; $j++ ) {
      print_upcoming_event ( $entries[$j] );
      $numEvents++;
    }
    for ( $j = 0; $j < count ( $rentries ) && $numEvents < $maxEvents; $j++ ) {
      print_upcoming_event ( $rentries[$j] );
      $numEvents++;
    }
    print "</dd>\n";
  }
}

print "</dl>\n";

print "</body>\n</html>";


// Print the details of an upcoming event
function print_upcoming_event ( $e ) {
  global $display_link, $link_target, $server_url, $charset;

  if ( $display_link && ! empty ( $server_url ) ) {
    print "<a title=\"" . 
      $e['cal_name'] . "\" href=\"" . 
      $server_url . "view_entry.php?id=" . 
      $e['cal_id'] . "&amp;date=" . 
      $e['cal_date'] . "\"";
    if ( ! empty ( $link_target ) ) {
      print " target=\"$link_target\"";
    }
    print ">";
  }
 print $e['cal_name'];
  if ( $display_link && ! empty ( $server_url ) ) {
    print "</a>";
  }
  if ( $e['cal_duration'] == 24 * 60 ) {
    print " (" . translate("All day event") . ")\n";
  } else if ( $e['cal_time'] != -1 ) {
    print " (" . display_time ( $e['cal_time'] ) . ")\n";
  }
  print "<br />\n";
}
?>
