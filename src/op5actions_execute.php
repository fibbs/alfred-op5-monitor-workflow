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
if (is_string( $get_authentication_val = $w->get('get_authentication', $config_plist)) and $get_authentication_val == "on") {
  $get_authentication = true;
} else {
  $get_authentication = false;
}

require_once('inc_functions.php');

/*
if (empty($username) or empty($password) or empty($api_hostname)) {
  error_http_connect();
}
 */

// MAIN workflow
if ( is_string($substr = check_args_prefix('ack_host:', $inQuery)) ) {

  list($hostname, $comment) = explode(':', $substr);
  $output = acknowledge_host_with_services($hostname, $comment); 
  echo "Acknowledged 1 host and " . $output . " services with comment " .$comment. "\n";

} else if ( is_string($substr = check_args_prefix('ack_host_svcs:', $inQuery)) ) {

  list($hostname, $comment) = explode(':', $substr);
  $output = acknowledge_hosts_services($hostname, $comment); 
  echo "Acknowledged " . $output . " services with comment " .$comment. "\n";

} else if ( is_string($substr = check_args_prefix('ack_hg_hosts:', $inQuery)) ) {

  list($hostgroupname, $comment) = explode(':', $substr);
  $output = acknowledge_hg_hosts($hostgroupname, $comment); 
  echo "Acknowledged " . $output[0] . " hosts and " . $output[1] . " services with comment " .$comment. "\n";

} else if ( is_string($substr = check_args_prefix('ack_hg_svcs:', $inQuery)) ) {

  list($hostgroupname, $comment) = explode(':', $substr);
  $output = acknowledge_hg_svcs($hostgroupname, $comment); 
  echo "Acknowledged " . $output . " services with comment " .$comment. "\n";

} else if ( is_string($substr = check_args_prefix('ack_svc:', $inQuery)) ) {

  list($service, $comment) = explode(':', $substr);
  list($service_host, $service_description) = explode(';', $service);
  $output = acknowledge_service($service_host, $service_description, $comment); 
  echo "Acknowledged 1 service with comment " .$comment. "\n";

} else if ( is_string($substr = check_args_prefix('ack_svcgrp:', $inQuery)) ) {

  list($svcgroupname, $comment) = explode(':', $substr);
  $output = acknowledge_svcgroup($svcgroupname, $comment); 
  echo "Acknowledged " . $output . " services with comment " .$comment. "\n";

}
?>