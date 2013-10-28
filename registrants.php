<?php

  // Load Perseus.
  require_once('init.inc');

  // Instantiate a System object.
  $system = new Perseus\RegistrationSystem(dirname(__FILE__));

  // Instantiate the database service.
  include($system->config_file);
  $system->newService('db', $db);

  $cols = array('name', 'affiliation', 'phone','mail',);
  $registrants_objs = $system->db()->select('registration', $cols);
  foreach ($show_fields as $col) {
    $headers[$col] = ucfirst($col);
  }
  foreach ($registrants_objs as $registrant) {
    $registrant = get_object_vars($registrant);
    $registrants[] = $registrant;
  }
  $vars = array(
    'attributes'  => array('class' => 'registrants'),
    'headers'     => $headers,
    'rows'        => $registrants,
  );
  include_once('top.inc');
  echo '<h1>Bioenergy Science Center (BESC) Characterization Workshop</h1>';
  echo '<p>This is the list of people who have signed up:</p>';
  print $system->theme('table', $vars);
  print '<p><a href="/besc/registrants_csv.php">Download as csv</a></p>';
  print $system->theme('system/messages', $system->getMessages(SYSTEM_NOTICE));

  include_once('bottom.inc');
?>