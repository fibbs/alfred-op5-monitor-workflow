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

function url_columns ($opmode) {
  if ($opmode == "hosts") {
    $columns = array(
      'name',
      'alias',
      'state',
      'last_check',
      'plugin_output',
      'num_services',
      'num_services_ok',
      'num_services_warn',
      'num_services_crit',
      'num_services_unknown',
      'num_services_pending',
      'duration'
    );
  } else if ($opmode == "saved_filters") {
    $columns = array(
      'id',
      'filter_name',
      'filter',
      'filter_description'
    );
  } else if ($opmode == "services") {
    $columns = array(
      'description',
      'display_name',
      'state',
      'plugin_output',
      'host.name',
      'duration'
    );
  } else if ($opmode == "hostgroups") {
    $columns = array(
      'name',
      'alias',
      'num_hosts',
      'num_hosts_down',
      'num_hosts_pending',
      'num_hosts_unreach',
      'num_hosts_up',
      'num_services',
      'num_services_ok',
      'num_services_crit',
      'num_services_pending',
      'num_services_unknown',
      'num_services_warn',
      'worst_host_state',
      'worst_service_state'
    );
  } else if ($opmode == "servicegroups") {
    $columns = array(
      'name',
      'alias',
      'num_services',
      'num_services_ok',
      'num_services_warn',
      'num_services_crit',
      'num_services_pending',
      'num_services_unknown',
      'worst_service_state'
    );
  }

  return implode(',', $columns);
}

function fetch_op5_api ($filter, $columns) {
  global $api_hostname;
  global $username;
  global $password;

  $url = 'https://'.$api_hostname.'/api/filter/query?query=' . urlencode($filter) . "&columns=" . $columns;

  $ch = curl_init( $url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $password );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

  $output_json = curl_exec( $ch );
  $info = curl_getinfo($ch);
  curl_close($ch);

  $output = json_decode($output_json);
  return $output;
}

function time_since ($duration) {

  if ($duration > 86400) {
    $return = "over 1day";
  } else if ($duration > 3600) {
    $my_hours = floor($duration / 3600);
    $my_minutes = floor(($duration % 3600) / 60);
    $my_seconds = $duration - ($my_hours * 3600) - ($my_minutes * 60);
    $return = "${my_hours}h, ${my_minutes}m, ${my_seconds}s";
  } else if ($duration > 60) {
    $my_minutes = floor($duration / 60);
    $my_seconds = $duration % 60;
    $return = "${my_minutes}m, ${my_seconds}s";
  } else { $return = "${duration}s";
  }

  return $return;
}

function check_args_prefix($prefix, $inQuery) {

  if (strpos($inQuery, $prefix) === 0) {
    $return = substr($inQuery, strlen($prefix), strlen($inQuery) - strlen($prefix));
    $return = preg_replace('/^ +/', '', $return);
    return $return;
  } else {
    return false;
  }
}

function check_filter_problems_only($str) {
  if (preg_match('/#$/', $str)) {
    $str = substr($str, 0, strlen($str)-1);
    return array(true, $str);
  } else {
    return array(false, $str);
  }
}

function set_url_filter() {
  global $inQuery;
  global $w;
  global $config_plist;

  if (! $defaultmode = $w->get('defaultmode', $config_plist)) {
    $defaultmode = 'hosts';
  }

  if ( !preg_match('/^[a-zA-Z]:/', $inQuery) and !preg_match('/^[\'\+]/', $inQuery) ) {
    if ($defaultmode == 'hosts') {
      $inQuery = 'h:' . $inQuery;
    } else if ($defaultmode == 'services') {
      $inQuery = 's:' . $inQuery;
    } else if ($defaultmode == 'hostgroups') {
      $inQuery = 'g:' . $inQuery;
    } else if ($defaultmode == 'servicegroups') {
      $inQuery = 'G:' . $inQuery;
    } else if ($defaultmode == 'saved_filters') {
      $inQuery = 'f:' . $inQuery;
    }
  }

  if ( is_string($substr = check_args_prefix('s:', $inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if (empty($substr)) {

      if ($is_filtered) {
        return '[services] state != 0';
      } else {
        return '[services] all';
      }

    } else {

      if ($is_filtered) {
        $statusfilter = ' and state != 0';
      } else {
        $statusfilter = '';
      }

      if (preg_match('/;/', $substr)) {

        list($filter_hostpart, $filter_servicepart) = explode(';', $substr);

        if (empty($filter_servicepart)) {

          if (strpos($filter_hostpart, '!') === 0) {
            $filter_hostpart = substr($filter_hostpart, 1,  strlen($filter_hostpart)-1);
            return '[services] host.name != "'.$filter_hostpart.'"' . $statusfilter;
          } else {
            return '[services] host.name = "'.$filter_hostpart.'"' . $statusfilter;
          }

        } else {

          if (strpos($filter_hostpart, '!') === 0) {
            $filter_hostpart = substr($filter_hostpart, 1,  strlen($filter_hostpart)-1);
            $hostfilter = 'host.name != "'.$filter_hostpart.'"';
          } else {
            $hostfilter = 'host.name = "'.$filter_hostpart.'"';
          }

          if (strpos($filter_servicepart, '!') === 0) {
            $filter_servicepart = substr($filter_servicepart, 1, strlen($filter_servicepart)-1);
            $servicefilter = 'description !~~ "'.$filter_servicepart.'"';
          } else {
            $servicefilter = 'description ~~ "'.$filter_servicepart.'"';
          }

          return '[services] ' . $hostfilter . ' and ' . $servicefilter . $statusfilter;

        }

      } else {

        if (strpos($substr, '!') === 0) {
          $substr = substr($substr, 1,  strlen($substr)-1);
          return '[services] description !~~ "'.$substr.'"'. $statusfilter;
        } else {
          return '[services] description ~~ "'.$substr.'"' . $statusfilter;
        }

      }

    }

  } else if ( is_string($substr = check_args_prefix('g:', $inQuery)) ) {
    
    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if (empty($substr)) {

      if ($is_filtered) {
        return '[hostgroups] (worst_host_state != 0 or worst_service_state != 0)';
      } else {
        return '[hostgroups] all';
      }

    } else {

      if ($is_filtered) {
        $statusfilter = ' and (worst_host_state != 0 or worst_service_state != 0)';
      } else {
        $statusfilter = '';
      }

      if (strpos($substr, '!') === 0) {
        $substr = substr($substr, 1,  strlen($substr)-1);
        return '[hostgroups] (name !~~ "'.$substr.'" and alias !~~ "'.$substr.'")' . $statusfilter;
      } else {
        return '[hostgroups] (name ~~ "'.$substr.'" or alias ~~ "'.$substr.'")' . $statusfilter;
      }

    }

  } else if ( is_string($substr = check_args_prefix('G:', $inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if (empty($substr)) {

      if ($is_filtered) {
        return '[servicegroups] worst_service_state != 0';
      } else {
        return '[servicegroups] all';
      }

    } else {

      if ($is_filtered) {
        $statusfilter = ' and worst_service_state != 0';
      } else {
        $statusfilter = '';
      }

      if (strpos($substr, '!') === 0) {
        $substr = substr($substr, 1,  strlen($substr)-1);
        return '[servicegroups] (name !~~ "'.$substr.'" and alias !~~ "'.$substr.'")' . $statusfilter;
      } else {
        return '[servicegroups] (name ~~ "'.$substr.'" or alias ~~ "'.$substr.'")' . $statusfilter;
      }

    }

  } else if ( is_string($substr = check_args_prefix('f:', $inQuery)) or is_string($substr = check_args_prefix('+', $inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if (empty($substr)) {

      return '[saved_filters] all';

    } else {

      if (strpos($substr, '!') === 0) {
        $substr = substr($substr, 1,  strlen($substr)-1);
        return '[saved_filters] filter_name !~~ "'.$substr.'" and filter_description !~~ "'.$substr . '"';
      } else {
        return '[saved_filters] filter_name ~~ "'.$substr.'" or filter_description ~~ "'.$substr.'"';
      }

    }

  } else if ( is_string($substr = check_args_prefix('F:',$inQuery)) or is_string($substr = check_args_prefix('\'',$inQuery)) ) {

    return $substr;

  } else if ( is_string($substr = check_args_prefix('h:',$inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if (empty($substr)) {

      if ($is_filtered) {
        return '[hosts] state != 0 or worst_service_state != 0';
      } else {
        return '[hosts] all';
      }

    } else {

      if ($is_filtered) {
        $statusfilter = ' and (state != 0 or worst_service_state != 0)';
      } else {
        $statusfilter = '';
      }

      if (strpos($substr, '!') === 0) {
        $substr = substr($substr, 1,  strlen($substr)-1);
        return '[hosts] (name !~~ "'.$substr.'" and alias !~~ "'.$substr.'")' . $statusfilter;
      } else {
        return '[hosts] (name ~~ "'.$substr.'" or alias ~~ "'.$substr.'")' . $statusfilter;
      }

    }

  } else if ( is_string($substr = check_args_prefix('m:',$inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if ($is_filtered) {
      return '[hosts] in "'.$substr.'" and (state != 0 or worst_service_state != 0)';
    } else {
      return '[hosts] in "'.$substr.'"';
    }

  } else if ( is_string($substr = check_args_prefix('M:',$inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if ($is_filtered) {
      return '[services] in "'.$substr.'" and state != 0';
    } else {
      return '[services] in "'.$substr.'"';
    }

  }

}

// MAIN workflow
$url_filter = set_url_filter();

// find out which opmode to use (defined in the filter between the square brackets)
$opmode = preg_replace('/\[(\w+)\].*$/', '${1}', $url_filter);


$hoststatusmap = array(
  0 => 'UP',
  1 => 'DOWN',
  2 => 'UNREACHABLE'
);

$servicestatusmap = array(
  0 => 'OK',
  1 => 'WARN',
  2 => 'CRIT',
  3 => 'UNKNOWN',
  4 => 'PENDING'
);


if ($opmode == "hosts") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  if ($result->error) {
    $w->result(
      '', 
      'api_error', 
      $result->error, 
      $result->full_error, 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;

  } else {

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
      } else if ($host->num_services_ok >0) {
        $hosticon_servicestate = 0;
      } else {
        $hosticon_servicestate = 4;
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

  }

  if (!count($result)) {
    $w->result(
      '', 
      '', 
      'No Hosts Found', 
      'No hosts matching your query were found', 
      'icons/status-failure.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;
  }


} else if ($opmode == "services") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  if ($result->error) {
    $w->result(
      '', 
      'api_error', 
      $result->error, 
      $result->full_error, 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;

  } else {


    foreach ( $result as $service ) {

      if (preg_match('/\[services\] host\.name = /', $url_filter)) {
        $service_description = $service->description;
      } else {
        $service_description = $service->host->name . " / " . $service->description;
      }

      // ########################## here's the magic
      $w->result(
        '', 
        'service:' . $service->host->name . ';' . $service->description, 
        $service_description, 
        'since ' . time_since($service->duration) . ' - ' . $service->plugin_output, 
        'icons/servicestatus-' . $service->state . '.png', 
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

  }

} else if ($opmode == "hostgroups") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  //var_dump($result);
  if ($result->error) {

    $w->result(
      '', 
      'api_error', 
      $result->error, 
      $result->full_error, 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;

  } else {

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

  }


} else if ($opmode == "servicegroups") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  if ($result->error) {

    $w->result(
      '', 
      'api_error', 
      $result->error, 
      $result->full_error, 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;

  } else {

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

  }
} else if ($opmode == "saved_filters") {

  $result = fetch_op5_api($url_filter, url_columns($opmode));

  if ($result->error) {
    $w->result(
      '', 
      'api_error', 
      $result->error, 
      $result->full_error, 
      'icon.png', 
      'no', 
      '' 
    );
    echo $w->toxml();
    exit;

  } else {

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
