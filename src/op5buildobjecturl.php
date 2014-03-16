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

  $url = build_object_url("host", $substr);

} else if ( is_string($substr = check_args_prefix('hostgroup:', $inQuery)) ) {

  $url = build_object_url("hostgroup", $substr);

} else if ( is_string($substr = check_args_prefix('service:', $inQuery)) ) {

  $url = build_object_url("service", $substr);

} else if ( is_string($substr = check_args_prefix('servicegroup:', $inQuery)) ) {

  $url = build_object_url("servicegroup", $substr);

} else {
  $url = "https://" . $api_hostname . "/monitor/index.php/tac";
}

echo $url . "\n";

?>
