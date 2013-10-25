<?php
// Load Perseus.
require_once('init.inc');

// Instantiate a System object.
$system = new Perseus\RegistrationSystem(dirname(__FILE__));
$_SESSION['system'] = $system;

// Instantiate the database service.
include($system->config_file);
$system->newService('db', $db);

// Instantiate the Installer and install.
$installer = new Perseus\RegistrationSystemInstaller($system);
$installer->install();

// Instantiate the form.
$form_settings = array('action' => 'index.php', 'name' => 'registration');
$form = new Perseus\Tools\RegistrationForm($system, $form_settings);

// Instantiate the mailer.
$mailer = new Perseus\PhpMail($system);

include_once('top.inc');

//Check whether the form has been submitted

if (array_key_exists('check_submit', $_POST)) {

  //Converts the new line characters (\n) in the text area into HTML line breaks
  // (the <br /> tag).
  $_POST['dietary_needs'] = nl2br($_POST['dietary_needs']);

  // Store the submitted data.
  $data = $_POST;
  unset($data['check_submit']);
  unset($data['submit']);
  $system->db()->insert('registration', $data);

  // Email the submitted data.
  //@todo - add the recipient to the site settings,
  $mailer->addRecipient('Shaun.Laws@nrel.gov', 'Shaun Laws');
  $mailer->from($data['mail'], $data['name']);
  $mailer->replyTo($data['mail'], $data['name']);
  $mailer->subject('BESC Characterization Workshop registration');
  $mailer->body($data['name'] . ' has registered for the workshop');
  $mailer->send();

  // Print out the values received in the browser.
  echo '<h1>Bioenergy Science Center (BESC) Characterization Workshop</h1>';
  echo '<p>Thank you for submitting your registration. The data that we received was:</p>';
  echo "Name: {$_POST['name']}<br />";
  echo "Affiliation: {$_POST['affiliation']}<br /><br />";
  echo "Address: <br />{$_POST['address']}<br />";
  echo "{$_POST['city']}<br />";
  echo "{$_POST['state']}<br />";
  echo "{$_POST['zip']}<br />";
  echo "{$_POST['country']}<br /><br />";
  echo "Phone: {$_POST['phone']}<br />";
  echo "Fax: {$_POST['fax']}<br />";
  echo "Email: {$_POST['mail']}<br />";
  echo "Meal: {$_POST['meal']}<br />";
  echo "Dietary needs: {$_POST['dietary_needs']}<br />";

  print $system->theme('system/messages', $system->getMessages(SYSTEM_NOTICE));
  
} else {
  ?>
          <h1>Bioenergy Science Center (BESC) Characterization Workshop</h1>
          <h3>December 17th and 18th, 2012</h3>
          <h3>Hosted by the National Renewable Energy Laboratory<br />
            15013 Denver West Parkway<br />
            Golden, CO 80401<br />
          </h3>
          <div class="location label">Meeting Location:</div>
          <div class="location">National Renewable Energy Laboratory<br />
            15013 Denver West Parkway<br />
            Golden, CO 80401<br />
          </div>
          </p>
          <p><span class="red">*</span> <em>Required Fields</em></p>
          <?php print $form->render(); ?>
          <?php //print $system->theme('system/messages', $system->getMessages(SYSTEM_NOTICE)); ?>
<?php
  }
  include_once('bottom.inc');
?>