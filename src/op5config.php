<?php

require_once('workflows.php');
$w = new Workflows();

$config_plist = 'settings.plist';

$opmode = $argv[1];
$entry = $argv[2];

/*
$w->result(
  'uid', 
  'arg', 
  'title', 
  'subtitle', 
  'icon.png', 
  'yes', 
  'autocomplete' 
);
 */

if ($opmode == "sethostname") {

  $w->result(
    '', 
    $opmode . ' ' . $entry, 
    'Set Hostname', 
    'type hostname and hit enter', 
    'icon.png', 
    'yes', 
    '' 
  );

  echo $w->toxml();

} else if ($opmode == "setusername") {

  $w->result(
    '', 
    $opmode . ' ' . $entry, 
    'Set Username', 
    'type username and hit enter', 
    'icon.png', 
    'yes', 
    '' 
  );

  echo $w->toxml();

} else if ($opmode == "setpassword") {

  $w->result(
    '', 
    $opmode . ' ' . $entry, 
    'Set Password', 
    'type password and hit enter', 
    'icon.png', 
    'yes', 
    '' 
  );

  echo $w->toxml();

} else if ($opmode == "setgetauth") {

  $w->result(
    '', 
    $opmode . ' on', 
    'Set HTTP GET authentication ON', 
    'This requires additional setup in your op5 Monitor settings', 
    'icon.png', 
    'yes', 
    '' 
  );

  $w->result(
    '', 
    $opmode . ' off', 
    'Set HTTP GET authentication OFF', 
    'This is the default setting', 
    'icon.png', 
    'yes', 
    '' 
  );

  echo $w->toxml();

} else if ($opmode == "setnotificationfilter") {

  $w->result(
    '', 
    $opmode . ' ' . $entry, 
    'Set contact name to filter notifications', 
    'type contact name and hit enter', 
    'icon.png', 
    'yes', 
    '' 
  );

  echo $w->toxml();


} else if (empty($opmode)) {

  // try to read the settings.plist
  if ($hostname = $w->get('hostname', $config_plist)) {
    $hostname_text = 'current hostname: ' . $hostname;
  } else {
    $hostname_text = 'hostname is not yet set';
  }

  if ($username = $w->get('username', $config_plist)) {
    $username_text = 'current user name: ' . $username;
  } else {
    $username_text = 'user name is not yet set';
  }

  if ($password = $w->get('password', $config_plist)) {
    $password_text = 'password is set';
  } else {
    $password_text = 'password is not yet set';
  }

  if (is_string( $get_authentication = $w->get('get_authentication', $config_plist)) and $get_authentication == "on") {
    $get_authentication_text = 'HTTP GET authentication is enabled';
  } else {
    $get_authentication_text = 'HTTP GET authentication is disabled';
  }

  if ($notification_filter_contact = $w->get('notification_filter_contact', $config_plist)) {
    $notification_filter_contact_text = 'filter is set to "' . $notification_filter_contact . '"';
  } else {
    $notification_filter_contact_text = 'filter is not set';
  }

  $w->result(
    '',
    '',
    'Set op5 Monitor Hostname',
    $hostname_text,
    'icon.png',
    'no',
    'sethostname '
  );

  $w->result(
    '',
    '',
    'Set User Name',
    $username_text,
    'icon.png',
    'no',
    'setusername '
  );

  $w->result(
    '',
    '',
    'Set Password',
    $password_text,
    'icon.png',
    'no',
    'setpassword '
  );

  $w->result(
    '',
    '',
    'Set HTTP GET authentication',
    $get_authentication_text,
    'icon.png',
    'no',
    'setgetauth '
  );

  $w->result(
    '',
    '',
    'Filter notifications by contact name',
    $notification_filter_contact_text,
    'icon.png',
    'no',
    'setnotificationfilter '
  );

  echo $w->toxml();

} else {

  $w->result(
    '',
    '',
    'Wrong argument',
    'choose between the three operating modes',
    'icon.png',
    'no',
    ''
  );

  echo $w->toxml();

}


?>
