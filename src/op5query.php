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


if (empty($username) or empty($password) or empty($api_hostname)) {
  error_http_connect();
}

require_once('inc_functions.php');

$hoststatusmap = array(
  0 => 'UP',
  1 => 'DOWN',
  2 => 'UNREACHABLE',
  3 => 'PENDING'
);

$servicestatusmap = array(
  0 => 'OK',
  1 => 'WARN',
  2 => 'CRIT',
  3 => 'UNKNOWN',
  4 => 'PENDING'
);


// MAIN workflow

if (empty($inQuery)) {

  $w->result(
    '',
    '',
    'Hosts Query',
    'Query op5 Monitor for host objects',
    'icon.png',
    'no',
    'h:'
  );
  $w->result(
    '',
    '',
    'Hostgroups Query',
    'Query op5 Monitor for hostgroup objects',
    'icon.png',
    'no',
    'g:'
  );
  $w->result(
    '',
    '',
    'Services Query',
    'Query op5 Monitor for service objects',
    'icon.png',
    'no',
    's:'
  );
  $w->result(
    '',
    '',
    'Servicegroups Query',
    'Query op5 Monitor for Servicegroup objects',
    'icon.png',
    'no',
    'G:'
  );
  $w->result(
    '',
    '',
    'Saved Filters Query',
    'Query op5 Monitor for saved filters',
    'icon.png',
    'no',
    '+'
  );
  echo $w->toxml();
  exit;

} else {

  $url_filter = set_url_filter();

}

// find out which opmode to use (defined in the filter between the square brackets)
$opmode = preg_replace('/\[(\w+)\].*$/', '${1}', $url_filter);


if ($opmode == "hosts") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  foreach ( $result as $host ) {

    $serviceelements = array();
    if ($host->num_services_crit > 0) {
      array_push($serviceelements, $host->num_services_crit . " CRIT");
    }
    if ($host->num_services_warn > 0) {
      array_push($serviceelements, $host->num_services_warn . " WARN");
    }
    if ($host->num_services_unknown > 0) {
      array_push($serviceelements, $host->num_services_unknown . " UNKNOWN");
    }
    if ($host->num_services_pending > 0) {
      array_push($serviceelements, $host->num_services_pending . " PENDING");
    }
    if ($host->num_services_ok > 0) {
      array_push($serviceelements, $host->num_services_ok . " OK");
    }
    $servicestext = implode(', ', $serviceelements);

    if ($host->last_check == 0) {
      $hosticon_hoststate = 3;
    } else {
      $hosticon_hoststate = $host->state;
    }

    if ($host->num_services_crit >0) {
      $hosticon_servicestate = 2;
    } else if ($host->num_services_warn >0) {
      $hosticon_servicestate = 1;
    } else if ($host->num_services_unknown >0) {
      $hosticon_servicestate = 3;
    } else if ($host->num_services_pending >0) {
      $hosticon_servicestate = 4;
    } else {
      $hosticon_servicestate = 0;
    }

    // ########################## here's the magic
    $w->result(
      '', 
      'host:' . $host->name, 
      $host->name . " (" . $host->alias . ")", 
      $servicestext . " services (host is " . $hoststatusmap[$host->state] . " since ".time_since($host->duration).")", 
      'icons/hoststatus-'.$hosticon_hoststate.'-'.$hosticon_servicestate.'.png', 
      'yes', 
      's:' . $host->name . ';'
    );
  }

  if (!count($result)) {
    $w->result(
      '', 
      '', 
      'No Hosts Found', 
      'No hosts matching your query were found', 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;
  }


} else if ($opmode == "services") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  foreach ( $result as $service ) {

    if (preg_match('/\[services\] host\.name = /', $url_filter)) {
      $service_description = $service->description;
    } else {
      $service_description = $service->host->name . " / " . $service->description;
    }

    if ($service->state_text == "pending") {
      $service_icon = 'icons/servicestatus-4.png';
    } else {
      $service_icon = 'icons/servicestatus-'.$service->state.'.png';
    }

    // ########################## here's the magic
    $w->result(
      '', 
      'service:' . $service->host->name . ';' . $service->description, 
      $service_description, 
      'since ' . time_since($service->duration) . ' - ' . $service->plugin_output, 
      $service_icon, 
      'yes', 
      '' 
    );

  }

  if (!count($result)) {
    $w->result(
      '', 
      'none', 
      'No Services Found', 
      'No services matching your query were found', 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;
  }

} else if ($opmode == "hostgroups") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  foreach ( $result as $hostgroup ) {

    // skip empty hostgroups (can be safely commented out)
    if ($hostgroup->num_hosts === 0) {
      continue;
    }

    if ($hostgroup->num_hosts > 0) {
      $icon_hostpart = $hostgroup->worst_host_state;
    } else {
      $icon_hostpart = 3;
    }

    if ($hostgroup->num_services >0) {
      $icon_servicepart = $hostgroup->worst_service_state;
    } else {
      $icon_servicepart = 4;
    }

    $host_details = array();
    if ($hostgroup->num_hosts_down > 0) {
      $host_details[] = $hostgroup->num_hosts_down . ' DOWN';
    }
    if ($hostgroup->num_hosts_unreach > 0) {
      $host_details[] = $hostgroup->num_hosts_unreach . ' UNREACHABLE';
    }
    if ($hostgroup->num_hosts_pending > 0) {
      $host_details[] = $hostgroup->num_hosts_pending . ' PENDING';
    }
    if ($hostgroup->num_hosts_up > 0) {
      $host_details[] = $hostgroup->num_hosts_up . ' UP';
    }
    if ($hostgroup->num_hosts === 0) {
      $host_details[] = 'none';
    }

    $service_details = array();
    if ($hostgroup->num_services_crit > 0) {
      $service_details[] = $hostgroup->num_services_crit . ' CRIT';
    }
    if ($hostgroup->num_services_warn > 0) {
      $service_details[] = $hostgroup->num_services_warn . ' WARN';
    }
    if ($hostgroup->num_services_unknown > 0) {
      $service_details[] = $hostgroup->num_services_unknown . ' UNKNOWN';
    }
    if ($hostgroup->num_services_pending > 0) {
      $service_details[] = $hostgroup->num_services_pending . ' PENDING';
    }
    if ($hostgroup->num_services_ok > 0) {
      $service_details[] = $hostgroup->num_services_ok . ' OK';
    }
    if ($hostgroup->num_services === 0) {
      $service_details[] = 'none';
    }

    $w->result(
      '', 
      'hostgroup:' . $hostgroup->name, 
      $hostgroup->name, 
      'hosts: ' . implode(', ', $host_details) . ', services: ' . implode(', ', $service_details), 
      'icons/hoststatus-' . $icon_hostpart . '-' . $icon_servicepart . '.png', 
      'yes', 
      'm:' . $hostgroup->name 
    );

  }

  if (!count($result)) {
    $w->result(
      '', 
      'none', 
      'No Hostgroups Found', 
      'No host groups matching your query were found', 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;
  }


} else if ($opmode == "servicegroups") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  foreach ( $result as $servicegroup ) {

    // skip empty hostgroups (can be safely commented out)
    if ($servicegroup->num_services === 0) {
      continue;
    }

    if ($servicegroup->num_services >0) {
      $sg_icon = 'icons/servicestatus-' . $servicegroup->worst_service_state . '.png';
    } else {
      $sg_icon = 'icons/servicestatus-4.png';
    }

    $service_details = array();
    if ($servicegroup->num_services_crit > 0) {
      $service_details[] = $servicegroup->num_services_crit . ' CRIT';
    }
    if ($servicegroup->num_services_warn > 0) {
      $service_details[] = $servicegroup->num_services_warn . ' WARN';
    }
    if ($servicegroup->num_services_unknown > 0) {
      $service_details[] = $servicegroup->num_services_unknown . ' UNKNOWN';
    }
    if ($servicegroup->num_services_pending > 0) {
      $service_details[] = $servicegroup->num_services_pending . ' PENDING';
    }
    if ($servicegroup->num_services_ok > 0) {
      $service_details[] = $servicegroup->num_services_ok . ' OK';
    }
    if ($servicegroup->num_services === 0) {
      $service_details[] = 'none';
    }

    $w->result(
      '', 
      'servicegroup:' . $servicegroup->name, 
      $servicegroup->name, 
      'services: ' . implode(', ', $service_details), 
      $sg_icon, 
      'yes', 
      'M:' . $servicegroup->name 
    );

  }

  if (!count($result)) {
    $w->result(
      '', 
      'none', 
      'No Servicegroups Found', 
      'No service groups matching your query were found', 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;
  }

} else if ($opmode == "saved_filters") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  foreach ( $result as $filter ) {

    // ########################## here's the magic
    $w->result(
      '', 
      $filter->filter, 
      $filter->filter_name, 
      $filter->filter_description, 
      'icon.png', 
      'no', 
      '\'' . $filter->filter 
    );

  }

  if (!count($result)) {
    $w->result(
      '', 
      'none', 
      'No Filters Found', 
      'No saved filters matching your query were found', 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;
  }

} else {

    $w->result(
      '', 
      'none', 
      'No Results', 
      'Your request had no result. This doesn\'t automatically have to be bad...', 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;

}

// Print the XML output
echo $w->toxml();

?>
