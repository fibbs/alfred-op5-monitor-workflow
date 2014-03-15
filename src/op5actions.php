<?php

require_once('workflows.php');
$w = new Workflows();

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

$config_plist = 'settings.plist';

// Arguments handling: put args together to one string, omitting the script name itself
array_shift($argv);
$inQuery = implode(' ', $argv);

// Main configuration
$username = $w->get('username', $config_plist);
$password = $w->get('password', $config_plist);
$api_hostname = $w->get('hostname', $config_plist);

if (empty($username) or empty($password) or empty($api_hostname)) {
  error_http_connect();
}


// functions
function error_http_connect() {
  global $w;

  $w->result(
    '',
    '',
    'Error: could not connect to op5 Monitor via http(s)',
    'Please set this workflow up correctly',
    'icon.png',
    'no',
    ''
  );

  echo $w->toxml();
  exit;
}

// now check the prefix string and act accordingly


/*
$w->result(
  '',
  '',
  'Error! No arguments given',
  'this workflow is intented to be used by the op5 Monitor query module only',
  'icon.png',
  'no',
  ''
);
echo $w->toxml();
exit;
 */



?>
