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

/*
if (empty($username) or empty($password) or empty($api_hostname)) {
  error_http_connect();
}
 */

require_once('inc_functions.php');


// now check the prefix string and act accordingly
if ( is_string($substr = check_args_prefix('host:', $inQuery)) ) {

  // work for host: prefix

  $w->result(
    '',
    build_host_action_url($substr),
    'Host object: ' . $substr,
    'Hit <enter> to get directly to this object in op5 Monitor',
    'icon.png',
    'yes',
    ''
  );
  echo $w->toxml();

} else if ( is_string($substr = check_args_prefix('hostgroup:', $inQuery)) ) {
} else if ( is_string($substr = check_args_prefix('service:', $inQuery)) ) {
} else if ( is_string($substr = check_args_prefix('servicegroup:', $inQuery)) ) {
} else {

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

}


?>
