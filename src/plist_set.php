<?php

require_once('workflows.php');
$w = new Workflows();

$config_plist = 'settings.plist';

$opmode = $argv[1];
$entry = $argv[2];


if ($opmode == "sethostname") {

  $w->set('hostname', $entry, $config_plist);

  if ($w->get('hostname', $config_plist) == $entry) {
    print 'successfully set host to ' . $entry;
  } else {
    print 'error setting hostname ' . $entry;
  }

} else if ($opmode == "setusername") {

  $w->set('username', $entry, $config_plist);

  if ($w->get('username', $config_plist) == $entry) {
    print 'successfully set username to ' . $entry;
  } else {
    print 'error setting username ' . $entry;
  }


} else if ($opmode == "setpassword") {

  $w->set('password', $entry, $config_plist);

  if ($w->get('password', $config_plist) == $entry) {
    print 'successfully set password';
  } else {
    print 'error setting password';
  }


} else if ($opmode == "setgetauth") {

  $w->set('get_authentication', $entry, $config_plist);

  if ($w->get('get_authentication', $config_plist) == $entry) {
    print 'successfully set HTTP GET authentication to ' . $entry;
  } else {
    print 'error setting HTTP GET authentication to ' . $entry;
  }

} else if ($opmode == "setnotificationfilter") {

  $w->set('notification_filter_contact', $entry, $config_plist);

  if ($w->get('notification_filter_contact', $config_plist) == $entry) {
    if ($entry == "") {
      print 'successfully set contact filter to be empty';
    } else {
      print 'successfully set contact filter to ' . $entry;
    }
  } else {
    print 'error setting contact filter ' . $entry;
  }


} else {

  print "something went terribly wrong";

}


?>
