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

} else if ($opmode == "setdefaultmode") {

  $w->result(
    '', 
    $opmode . ' hosts', 
    'hosts', 
    'hit enter to set default mode to hosts', 
    'icon.png', 
    'yes', 
    '' 
  );

  $w->result(
    '', 
    $opmode . ' services', 
    'services', 
    'hit enter to set default mode to services', 
    'icon.png', 
    'yes', 
    '' 
  );

  $w->result(
    '', 
    $opmode . ' hostgroups', 
    'hostgroups', 
    'hit enter to set default mode to hostgroups', 
    'icon.png', 
    'yes', 
    '' 
  );

  $w->result(
    '', 
    $opmode . ' servicegroups', 
    'servicegroups', 
    'hit enter to set default mode to servicegroups', 
    'icon.png', 
    'yes', 
    '' 
  );

  $w->result(
    '', 
    $opmode . ' saved_filters', 
    'saved_filters', 
    'hit enter to set default mode to saved_filters', 
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

  if ($defaultmode = $w->get('defaultmode', $config_plist)) {
    $defaultmode_text = 'Default mode is set to ' . $defaultmode;
  } else {
    $defaultmode_text = 'Default mode is not yet set (hosts will be used in this case)';
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
    'Set Default Mode',
    $defaultmode_text,
    'icon.png',
    'no',
    'setdefaultmode '
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
