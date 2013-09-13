<?php
/**
 * @file
 * Perseus Library Bootstrapper.
 */
define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
define('PROOT', __DIR__);

session_start();

// Bootstrap basic system tools and variables.
require_once(PROOT . '/classes/System.class.php');

// Bootstrap common utilities required by other bootstrap phases.
Perseus_System::fileRequire('includes/util.inc');

// Register autoloader
function __autoload($class) {
  $parts = explode('_', $class);
  include_once 'classes/' . end($parts) . '.class.php';
}
