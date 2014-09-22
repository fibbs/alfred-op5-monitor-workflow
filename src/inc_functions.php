<?php

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
      'duration',
      'acknowledged',
      'worst_service_hard_state',
      'pnpgraph_present'
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
      'state_text',
      'plugin_output',
      'host.name',
      'duration',
      'acknowledged',
      'pnpgraph_present'
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
  } else if ($opmode == "notifications") {
    $columns = array(
      'notification_type',
      'start_time',
      'end_time',
      'contact_name',
      'host_name',
      'service_description',
      'state',
      'output'
    );
  }

  return implode(',', $columns);
}

function fetch_op5_api ($filter, $columns) {
  global $api_hostname;
  global $username;
  global $password;
  global $w;

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

  if ($output->error) {
    $w->result(
      '',
      'api_error',
      $output->error,
      $output->full_error,
      'icon.png',
      'no',
      ''
    );
    echo $w->toxml();
    exit;
  }

  return $output;
}

function put_op5_api ($url, $data) {
  global $api_hostname;
  global $username;
  global $password;
  global $w;

  $ch = curl_init ( $url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $password );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
  curl_setopt( $ch, CURLOPT_POST, 1);
  curl_setopt( $ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

  $output_json = curl_exec( $ch );
  $info = curl_getinfo($ch);
  curl_close($ch);

  $output = json_decode($output_json);

  if ($output->error) {
    $w->result(
      '',
      'api_error',
      $output->error,
      $output->full_error,
      'icon.png',
      'no',
      ''
    );
    echo $w->toxml();
    exit;
  }

  return $output;
}

function time_since ($duration) {

  if ($duration > 86400) {
    $my_days = floor($duration / 86400);
    $my_hours = floor(($duration % 86400) / 3600);
    $my_minutes = floor(($duration - ($my_days * 86400) - ($my_hours * 3600)) / 60);
    $my_seconds = $duration - ($my_days * 86400) - ($my_hours * 3600) - ($my_minutes * 60);
    $return = "${my_days}d, ${my_hours}h, ${my_minutes}m, ${my_seconds}s";
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
  global $notification_filter_contact;

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

  } else if ( is_string($substr = check_args_prefix('n:', $inQuery)) ) {

    list($is_filtered, $substr) = check_filter_problems_only($substr);

    if ($is_filtered) {
      $statusfilter = ' and state != 0';
    } else {
      $statusfilter = '';
    }

    if (is_string($notification_filter_contact) and $notification_filter_contact != "") {
      $contactfilter = ' and contact_name = "' . $notification_filter_contact . '"';
    } else {
      $contactfilter = '';
    }

    if (empty($substr)) {

      return '[notifications] all' . $statusfilter . $contactfilter;

    } else {

      if (strpos($substr, '!') === 0) {
        $substr = substr($substr, 1,  strlen($substr)-1);
        return '[notifications] host_name !~~ "'.$substr.'" and service_description !~~ "'.$substr . '" and output !~~ "'.$substr . '"' . $statusfilter . $contactfilter;
      } else {
        return '[notifications] (host_name ~~ "'.$substr.'" or service_description ~~ "'.$substr.'" or output ~~ "' . $substr . '")' . $statusfilter . $contactfilter;
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

function build_object_url($query) {
  global $api_hostname;
  global $username;
  global $password;
  global $get_authentication;

  if ( is_string($objectname = check_args_prefix('host:', $query)) ) {

    $filter = "[services] host.name=\"$objectname\"";
    $url = 'https://'.$api_hostname.'/monitor/index.php/listview?q=' . urlencode($filter);

    if ($get_authentication) {
      $url = $url . "&username=".urlencode($username)."&password=".urlencode($password);
    }

  } else if ( is_string($objectname = check_args_prefix('hostgroup:', $query)) ) {

    $filter = "[hosts] groups >= \"$objectname\"";
    $url = 'https://'.$api_hostname.'/monitor/index.php/listview?q=' . urlencode($filter);

    if ($get_authentication) {
      $url = $url . "&username=".urlencode($username)."&password=".urlencode($password);
    }

  } else if ( is_string($objectname = check_args_prefix('service:', $query)) ) {

    list( $hostname, $servicename) = explode(";", $objectname);
    $url = 'https://'.$api_hostname.'/monitor/index.php/extinfo/details?host=' . urlencode($hostname) . '&service=' . urlencode($servicename);

    if ($get_authentication) {
      $url = $url . "&username=".urlencode($username)."&password=".urlencode($password);
    }

  } else if ( is_string($objectname = check_args_prefix('servicegroup:', $query)) ) {

    $filter = "[services] groups >= \"$objectname\"";
    $url = 'https://'.$api_hostname.'/monitor/index.php/listview?q=' . urlencode($filter);

    if ($get_authentication) {
      $url = $url . "&username=".urlencode($username)."&password=".urlencode($password);
    }

  } else if ( is_string($querystring = check_args_prefix('notifications:', $query)) ) {

    $filter = '[notifications] host_name ~~ "'.$querystring.'" or service_description ~~ "'.$querystring.'" or output ~~ "' . $querystring . '"';
    $url = 'https://'.$api_hostname.'/monitor/index.php/listview?q=' . urlencode($filter);

    if ($get_authentication) {
      $url = $url . "&username=".urlencode($username)."&password=".urlencode($password);
    }

  } else {

    $url = 'https://'.$api_hostname.'/monitor/index.php/tac/index';

    if ($get_authentication) {
      $url = $url . "?username=".urlencode($username)."&password=".urlencode($password);
    }

  }

  return $url;
}

function build_pnpgraph_url($hostname, $servicedescription) {
  global $api_hostname;
  global $get_authentication;
  global $username;
  global $password;

  $url = 'https://' . $api_hostname . '/monitor/index.php/pnp/?host=' . urlencode($hostname) . '&srv=' . urlencode($servicedescription);

  if ($get_authentication) {
    $url = $url . "&username=".urlencode($username)."&password=".urlencode($password);
  }

  return $url;
}

function determine_hosticon($host_object) {
  if ($host_object->last_check == 0) {
   $hosticon_hoststate = 3;
  } else {
    $hosticon_hoststate = $host_object->state;
  }

  if ($host_object->num_services_crit >0) {
    $hosticon_servicestate = 2;
  } else if ($host_object->num_services_warn >0) {
    $hosticon_servicestate = 1;
  } else if ($host_object->num_services_unknown >0) {
    $hosticon_servicestate = 3;
  } else if ($host_object->num_services_pending >0) {
    $hosticon_servicestate = 4;
  } else {
    $hosticon_servicestate = 0;
  }  

  return 'icons/hoststatus-'.$hosticon_hoststate.'-'.$hosticon_servicestate.'.png';
}

function determine_serviceicon($service_object) {
  if ($service_object->state_text == "pending") {
    return 'icons/servicestatus-4.png';
  } else {
    return 'icons/servicestatus-'.$service_object->state.'.png';
  }
}

function determine_hostgroupicon($hostgroup_object) {
  if ($hostgroup_object->num_hosts > 0) {
    $icon_hostpart = $hostgroup_object->worst_host_state;
  } else {
    $icon_hostpart = 3;
  }

  if ($hostgroup_object->num_services >0) {
    $icon_servicepart = $hostgroup_object->worst_service_state;
  } else {
    $icon_servicepart = 4;
  }

  return 'icons/hoststatus-'.$icon_hostpart.'-'.$icon_servicepart.'.png';
}

function determine_servicegroupicon($servicegroup_object) {
  if ($servicegroup_object->num_services >0) {
    return 'icons/servicestatus-' . $servicegroup_object->worst_service_state . '.png';
  } else {
    return 'icons/servicestatus-4.png';
  }
}

function determine_notificationsicon($notification_object) {
  if ($notification_object->notification_type == 0) {
    # host notification
    if ($notification_object->state == 1) {
      $state = 2;
    } else {
      $state = $notification_object->state;
    }
  } else {
    # all other notification types
    $state = $notification_object->state;
  }
  return 'icons/servicestatus-' . $state . '.png';
}

function acknowledge_host($hostname, $comment) {
  global $api_hostname;
  $url = 'https://' . $api_hostname . '/api/command/ACKNOWLEDGE_HOST_PROBLEM';

  $data = json_encode(array(
    "host_name"   =>    $hostname,
    "sticky"      =>    0,
    "notify"      =>    "true",
    "persistent"  =>    "true",
    "comment"     =>    $comment));

  return put_op5_api($url, $data);
}

function acknowledge_service($hostname, $servicedescription, $comment) {
  global $api_hostname;
  $url = 'https://' . $api_hostname . '/api/command/ACKNOWLEDGE_SVC_PROBLEM';

  $data = json_encode(array(
    "host_name"             =>    $hostname,
    "service_description"   =>    $servicedescription,
    "sticky"                =>    0,
    "notify"                =>    "true",
    "persistent"            =>    "true",
    "comment"               =>    $comment));

  return put_op5_api($url, $data);
}

function acknowledge_host_with_services($hostname, $comment) {
  acknowledge_host($hostname, $comment);

  $filter = '[services] host.name = "' . $hostname . '" and state != 0 and acknowledged = 0';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));

  $counter = 0;
  foreach ($fetch_result as $service) {
    acknowledge_service($hostname, $service->description, $comment);
    $counter++;
  }

  return $counter;
}

function acknowledge_hosts_services($hostname, $comment) {
  $filter = '[services] host.name = "' . $hostname . '" and state != 0 and acknowledged = 0';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));

  $counter = 0;
  foreach ($fetch_result as $service) {
    acknowledge_service($hostname, $service->description, $comment);
    $counter++;
  }

  return $counter;
}

function acknowledge_hg_hosts($groupname, $comment) {
  $filter = '[hosts] groups >= "' . $groupname . '" and state != 0 and acknowledged = 0';
  $fetch_result = fetch_op5_api($filter, url_columns('hosts'));

  $hostcounter = 0;
  $servicecounter = 0;
  foreach ($fetch_result as $host) {
    $nr_of_svc = acknowledge_host_with_services($host->name, $comment);
    $servicecounter = $servicecounter + $nr_of_svc;
    $hostcounter++;
  }

  return array($hostcounter, $servicecounter);
}

function acknowledge_hg_svcs($groupname, $comment) {
  $filter = '[services] host.groups >= "' . $groupname . '" and host.state = 0 and state != 0 and acknowledged = 0';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));

  $counter = 0;
  foreach ($fetch_result as $service) {
    acknowledge_service($service->host->name, $service->description, $comment);
    $counter++;
  }

  return $counter;
}

function acknowledge_svcgroup($groupname, $comment) {
  $filter = '[services] groups >= "' . $groupname . '" and state != 0 and acknowledged = 0';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));

  $counter = 0;
  foreach ($fetch_result as $service) {
    acknowledge_service($service->host->name, $service->description, $comment);
    $counter++;
  }

  return $counter;
}

function reschedule_host($hostname) {
  global $api_hostname;
  $url = 'https://' . $api_hostname . '/api/command/SCHEDULE_HOST_CHECK';

  $data = json_encode(array(
    "host_name"   =>      $hostname,
    "check_time"     =>   time()
    )
  );

  return put_op5_api($url, $data);
}

function reschedule_service($hostname, $servicedescription) {
  global $api_hostname;
  $url = 'https://' . $api_hostname . '/api/command/SCHEDULE_SVC_CHECK';

  $data = json_encode(array(
    "host_name"           =>      $hostname,
    "service_description" =>      $servicedescription,
    "check_time"          =>      time()
    )
  );

  return put_op5_api($url, $data);
}

function reschedule_host_with_services($hostname) {
  reschedule_host($hostname);

  $filter = '[services] host.name = "' . $hostname . '" and active_checks_enabled = 1';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));

  $counter = 0;
  foreach ($fetch_result as $service) {
    reschedule_service($hostname, $service->description);
    $counter++;
  }

  return $counter;
}

function reschedule_hostgroup($hostgroupname) {
  $filter = '[hosts] groups >= "' . $hostgroupname . '"';
  $fetch_result = fetch_op5_api($filter, url_columns('hosts'));

  $hostcounter = 0;
  $servicecounter = 0;
  foreach ($fetch_result as $host) {
    $nr_of_svc = reschedule_host_with_services($host->name);
    $servicecounter = $servicecounter + $nr_of_svc;
    $hostcounter++;
  }

  return array($hostcounter, $servicecounter);
}

function reschedule_svcgrp($groupname) {
  $filter = '[services] groups >= "' . $groupname . '" and active_checks_enabled = 1';
  $fetch_result = fetch_op5_api($filter, url_columns('services'));

  $counter = 0;
  foreach ($fetch_result as $service) {
    reschedule_service($service->host->name, $service->description);
    $counter++;
  }

  return $counter;
}
?>
