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

require_once('inc_functions.php');

/*
if (empty($username) or empty($password) or empty($api_hostname)) {
  error_http_connect();
}
 */


// now check the prefix string and act accordingly
if ( is_string($substr = check_args_prefix('host:', $inQuery)) ) {

  // work for host: prefix
  $filter = '[hosts] name = "' . $substr . '"';
  $fetch_result = fetch_op5_api($filter, url_columns('hosts'));
  $host_object = $fetch_result[0];

  $w->result(
    '',
    '',
    'Host: ' . $substr . ' / ' . $host_object->plugin_output,
    'choose from one of the below listed options to issue object related actions',
    determine_hosticon($host_object),
    'no',
    ''
  );
  echo $w->toxml();

} else if ( is_string($substr = check_args_prefix('hostgroup:', $inQuery)) ) {

  // work for hostgroup: prefix
  $filter = '[hostgroups] name = "' . $substr . '"';
  $fetch_result = fetch_op5_api($filter, url_columns('hostgroups'));
  $hostgroup_object = $fetch_result[0];

  $w->result(
    '',
    '',
    'Hostgroup: ' . $substr,
    'choose from one of the below listed options to issue object related actions',
    determine_hostgroupicon($hostgroup_object),
    'no',
    ''
  );
  echo $w->toxml();

} else if ( is_string($substr = check_args_prefix('service:', $inQuery)) ) {

  // work for service: prefix
  list($myhost, $myservice) = explode(';', $substr);
  $filter = '[services] host.name = "' . $myhost . '" and description = "' . $myservice . '"';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));
  $service_object = $fetch_result[0];

  $w->result(
    '',
    '',
    'Service: ' . $myservice . " on " . $myhost,
    'choose from one of the below listed options to issue object related actions',
    determine_serviceicon($service_object),
    'no',
    ''
  );
  echo $w->toxml();

} else if ( is_string($substr = check_args_prefix('servicegroup:', $inQuery)) ) {

  // work for host: servicegroup
  $filter = '[servicegroups] name = "' . $substr . '"';
  $fetch_result = fetch_op5_api($filter, url_columns('servicegroups'));
  $servicegroup_object = $fetch_result[0];

  $w->result(
    '',
    '',
    'Servicegroup: ' . $substr,
    'choose from one of the below listed options to issue object related actions',
    determine_servicegroupicon($servicegroup_object),
    'no',
    ''
  );
  echo $w->toxml();

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
