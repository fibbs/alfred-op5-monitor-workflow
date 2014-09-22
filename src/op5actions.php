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


// now check the prefix string and act accordingly
if ( is_string($substr = check_args_prefix('ack_host:', $inQuery)) ) {

  // work for ack_host: prefix
  list($action_prefix, $host_name, $comment) = explode(':', $inQuery);

  if (empty($comment)) {
    $w->result(
      '',
      '',
      'Acknowledge host problem',
      'type a comment and hit <Enter> to continue',
      'icon.png',
      'no',
      ''
    );
  } else {
    $w->result(
      '',
      $inQuery,
      'Acknowledge host problem',
      'Using comment: ' . $comment,
      'icon.png',
      'yes',
      ''
    );
  }

  echo $w->toxml();
  exit;

} else if ( is_string($substr = check_args_prefix('ack_host_svcs:', $inQuery)) ) {

  // work for ack_host_svcs: prefix
  list($action_prefix, $host_name, $comment) = explode(':', $inQuery);

  if (empty($comment)) {
    $w->result(
      '',
      '',
      'Acknowledge service problems on host',
      'type a comment and hit <Enter> to continue',
      'icon.png',
      'no',
      ''
    );
  } else {
    $w->result(
      '',
      $inQuery,
      'Acknowledge service problems on host',
      'Using comment: ' . $comment,
      'icon.png',
      'yes',
      ''
    );
  }

  echo $w->toxml();
  exit;

} else if ( is_string($substr = check_args_prefix('ack_hg_hosts:', $inQuery)) ) {

  // work for ack_host_svcs: prefix
  list($action_prefix, $hostgroup_name, $comment) = explode(':', $inQuery);

  if (empty($comment)) {
    $w->result(
      '',
      '',
      'Acknowledge all host problems (and their service problems) in group',
      'type a comment and hit <Enter> to continue',
      'icon.png',
      'no',
      ''
    );
  } else {
    $w->result(
      '',
      $inQuery,
      'Acknowledge all host problems (and their service problems) in group',
      'Using comment: ' . $comment,
      'icon.png',
      'yes',
      ''
    );
  }

  echo $w->toxml();
  exit;

} else if ( is_string($substr = check_args_prefix('ack_hg_svcs:', $inQuery)) ) {

  // work for ack_host_svcs: prefix
  list($action_prefix, $hostgroup_name, $comment) = explode(':', $inQuery);

  if (empty($comment)) {
    $w->result(
      '',
      '',
      'Acknowledge all service problems in this host group',
      'type a comment and hit <Enter> to continue',
      'icon.png',
      'no',
      ''
    );
  } else {
    $w->result(
      '',
      $inQuery,
      'Acknowledge all service problems in this host group',
      'Using comment: ' . $comment,
      'icon.png',
      'yes',
      ''
    );
  }

  echo $w->toxml();
  exit;

} else if ( is_string($substr = check_args_prefix('ack_svc:', $inQuery)) ) {

  // work for ack_host_svcs: prefix
  list($action_prefix, $service_name, $comment) = explode(':', $inQuery);

  if (empty($comment)) {
    $w->result(
      '',
      '',
      'Acknowledge service problem',
      'type a comment and hit <Enter> to continue',
      'icon.png',
      'no',
      ''
    );
  } else {
    $w->result(
      '',
      $inQuery,
      'Acknowledge service problem',
      'Using comment: ' . $comment,
      'icon.png',
      'yes',
      ''
    );
  }

  echo $w->toxml();
  exit;

} else if ( is_string($substr = check_args_prefix('ack_svcgrp:', $inQuery)) ) {

  // work for ack_host_svcs: prefix
  list($action_prefix, $servicegroup_name, $comment) = explode(':', $inQuery);

  if (empty($comment)) {
    $w->result(
      '',
      '',
      'Acknowledge all service problems in group',
      'type a comment and hit <Enter> to continue',
      'icon.png',
      'no',
      ''
    );
  } else {
    $w->result(
      '',
      $inQuery,
      'Acknowledge all service problems in group',
      'Using comment: ' . $comment,
      'icon.png',
      'yes',
      ''
    );
  }

  echo $w->toxml();
  exit;


/*
  BEGINNING FROM HERE THE 'ACTIONS' PART OF THE ALFRED APP SELECTION BEGINS
*/

} else if ( is_string($substr = check_args_prefix('host:', $inQuery)) ) {

  // work for host: prefix
  $filter = '[hosts] name = "' . $substr . '"';
  $fetch_result = fetch_op5_api($filter, url_columns('hosts'));
  $host_object = $fetch_result[0];

  $w->result(
    '',
    'url: ' . build_object_url('host: ' . $substr),
    'Host: ' . $substr,
    'choose from one of the below listed options to issue object related actions',
    determine_hosticon($host_object),
    'yes',
    ''
  );

  if ($host_object->pnpgraph_present == 1) {
    $w->result(
      '',
      'url: ' . build_pnpgraph_url($substr, '_HOST_'),
      'Show Performance graphs for this host',
      '',
      'icon.png',
      'yes',
      ''
    );
  }

  $w->result(
    '',
    'hostnotifications: ' . $substr,
    'Notifications',
    'See notifications for the host',
    'icon.png',
    'yes',
    ''
  );

  // ACKNOWLEDGE host problem
  if ($host_object->state != 0 and $host_object->acknowledged == 0) {
    $w->result(
      '',
      '',
      'Acknowledge host problem',
      '',
      'icon.png',
      'no',
      'ack_host:' . $substr . ':'
    );
  }

  // ACKNOWLEDGE all service problems on this host
  if ($host_object->state == 0 and $host_object->worst_service_hard_state != 0) {
    $inner_filter = '[services] host.name = "' . $substr . '" and state != 0 and acknowledged = 0';
    $fetch_result = fetch_op5_api($inner_filter, url_columns('services'));
    if (count($fetch_result)) {
      $w->result(
        '',
        '',
        'Acknowledge all service problems on this host',
        '',
        'icon.png',
        'no',
        'ack_host_svcs:' . $substr . ':'
      );
    }
  }

  //RESCHEDULE / RECHECK
  $w->result(
    '',
    'recheck_host:' . $substr,
    'Re-check host and all services NOW',
    '',
    'icon.png',
    'yes',
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
    'url: ' . build_object_url('hostgroup: ' . $substr),
    'Hostgroup: ' . $substr,
    'choose from one of the below listed options to issue object related actions',
    determine_hostgroupicon($hostgroup_object),
    'yes',
    ''
  );

  // ACKNOWLEDGE host group's host problems AND their service problems
  if ($hostgroup_object->worst_host_state != 0) {
    $inner_filter = '[hosts] groups >= "' . $substr . '" and state != 0 and acknowledged = 0';
    $fetch_result = fetch_op5_api($inner_filter, url_columns('hosts'));
    if (count($fetch_result) > 0) {
      $w->result(
        '',
        '',
        'Acknowledge all host problems in this host group',
        '',
        'icon.png',
        'no',
        'ack_hg_hosts:' . $substr . ':'
      );
    }
  }

  // ACKNOWLEDGE host group's service problems on OK hosts
  if ($hostgroup_object->worst_service_state != 0) {
    $inner_filter = '[services] host.groups >= "' . $substr . '" and state != 0 and host.state = 0 and acknowledged = 0';
    $fetch_result = fetch_op5_api($inner_filter, url_columns('services'));
    if (count($fetch_result) > 0) {
      $w->result(
        '',
        '',
        'Acknowledge service problems on UP members of this hostgroup',
        '',
        'icon.png',
        'no',
        'ack_hg_svcs:' . $substr . ':'
      );
    }
  }

  //RESCHEDULE / RECHECK
  $w->result(
    '',
    'recheck_hg:' . $substr,
    'Re-check all hosts and services in this group NOW',
    '',
    'icon.png',
    'yes',
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
    'url: ' . build_object_url('service: ' . $substr),
    'Service: ' . $myservice . " on " . $myhost,
    'choose from one of the below listed options to issue object related actions',
    determine_serviceicon($service_object),
    'yes',
    ''
  );

  if ($service_object->pnpgraph_present == 1) {
    $w->result(
      '',
      'url: ' . build_pnpgraph_url($myhost, $myservice),
      'Show Performance graphs for this service',
      '',
      'icon.png',
      'yes',
      ''
    );
  }

  $w->result(
    '',
    'svcnotifications: ' . $myhost . ';' . $myservice,
    'Notifications',
    'See notifications for this service',
    'icon.png',
    'yes',
    ''
  );

  $w->result(
    '',
    'hostnotifications: ' . $myhost,
    'Notifications for host ' . $myhost,
    'See notifications for the host of this service',
    'icon.png',
    'yes',
    ''
  );

  // ACKNOWLEDGE service problem
  if ($service_object->state != 0 and $service_object->acknowledged == 0) {
    $w->result(
      '',
      '',
      'Acknowledge service problem',
      '',
      'icon.png',
      'no',
      'ack_svc:' . $substr . ':'
    );
  }

  //RESCHEDULE / RECHECK
  $w->result(
    '',
    'recheck_svc:' . $substr,
    'Re-check service NOW',
    '',
    'icon.png',
    'yes',
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
    'url: ' . build_object_url('servicegroup: ' . $substr),
    'Servicegroup: ' . $substr,
    'choose from one of the below listed options to issue object related actions',
    determine_servicegroupicon($servicegroup_object),
    'yes',
    ''
  );

  // ACKNOWLEDGE all service problems in service group 
  if ($servicegroup_object->worst_service_state != 0) {
    $inner_filter = '[services] groups >= "' . $substr . '" and state != 0 and acknowledged = 0';
    $fetch_result = fetch_op5_api($inner_filter, url_columns('services'));
    if (count($fetch_result)) {
      $w->result(
        '',
        '',
        'Acknowledge all service problems in this service group',
        '',
        'icon.png',
        'no',
        'ack_svcgrp:' . $substr . ':'
      );
    }
  }

  //RESCHEDULE / RECHECK
  $w->result(
    '',
    'recheck_svcgrp:' . $substr,
    'Re-check all services in this group NOW',
    '',
    'icon.png',
    'yes',
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
