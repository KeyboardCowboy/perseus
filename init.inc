<?php
/**
 * @file
 * Perseus Library Bootstrapper.
 */
define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
define('PROOT', __DIR__);

session_start();
//system_start();

// Bootstrap basic system tools and variables.
require_once(PROOT . '/bootstrap/system.class.php');

// Bootstrap common utilities required by other bootstrap phases.
System::fileRequire('includes/util.inc');

// Load themeing utilities.
System::fileRequire('includes/theme.inc');

/**
 * Load system settings and variables.
 */
function system_start() {
  global $system;

  $system = new System();
}
