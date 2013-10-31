<?php

  // Load Perseus.
  require_once('init.inc');

  // Instantiate a System object.
  $system = new Perseus\RegistrationSystem(dirname(__FILE__));
  $_SESSION['system'] = $system;

  // Instantiate the database service.
  include($system->config_file);
  $system->newService('db', $db);

  // Instantiate the CSV Exporter and set the headers.
  $exporter = $system->newService('csvexporter', $csv_exporter['default']);
  $index = 0;

  $cols = array('name', 'affiliation', 'address', 'city', 'state', 'country', 'zip', 'phone', 'fax', 'mail', 'meal',);
  $registrants_objs = $system->db()->select('registration', $cols);
  foreach ($cols as $col) {
    $rows[$index][] = ucfirst($col);
  }
  $index++;
  foreach ($registrants_objs as $registrant) {
    $registrant = get_object_vars($registrant);
    //$exporter->addRow($registrant);
    $rows[$index] = array_values($registrant);
    $index++;
  }
  $exporter->export($rows);
?>