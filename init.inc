<?php
/**
 * @file
 * Perseus Library Bootstrapper.
 */
define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
define('PROOT', __DIR__);

session_start();

spl_autoload_register('perseus_autoload');

// Load error/exception handlers.
//require_once(PROOT . '/classes/Exception/Exception.class.php');

// Load basic system tools and variables.
require_once(PROOT . '/system/System/System.class.php');

// Load common utilities required by other bootstrap phases.
require_once(PROOT . '/includes/util.inc');

// Autoloading router
function perseus_autoload($class) {
  // Perseus classes
  if (strpos($class, 'Perseus') === 0) {
    $parts = explode('\\', $class);

    // Remove the 'Perseus' namespace
    array_shift($parts);

    // Grab the classname
    $class = array_pop($parts);

    // Create the path from the namespace.
    if (is_file($file = PROOT . '/' . strtolower(implode('/', $parts)) . "/$class/$class.class.php")) {
      require $file;
    }
  }

  // Twig classes (@see Autoloader.php)
  elseif (strpos($class, 'Twig') === 0) {
    if (is_file($file = PROOT . '/theme/twig/lib/' . str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
      require $file;
    }
  }

  // Die nicely.
  else {
    return;
  }
}
