<?php
/**
 * @file
 * Perseus Library Bootstrapper.
 */
define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);

session_start();
system_start();

global $system;

// Bootstrap common utilities required by other bootstrap phases.
System::fileRequire('includes/util.inc');

// Add debugging class.
System::fileRequire('classes/debug.class.inc');

// Load themeing utilities.
System::fileRequire('includes/theme.inc');

// Bootstrap MySQL.
System::fileRequire('classes/mysql.class.inc');

// Load PHP Mailer
System::fileRequire('classes/phpMail.class.inc');

/**
 * Load system settings and variables.
 */
function system_start() {
  global $system;

  // Bootstrap basic system tools and variables.
  require_once(__DIR__ . '/classes/system.class.inc');
  $system = new System();
}
