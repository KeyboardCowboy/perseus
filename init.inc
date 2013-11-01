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
//require_once(PROOT . '/classes/Exception.class.php');

// Load basic system tools and variables.
require_once(PROOT . '/system/System.class.php');

// Load common utilities required by other bootstrap phases.
require_once(PROOT . '/includes/util.inc');

global $perseus;
$perseus = new Perseus\System\System(dirname($_SERVER['SCRIPT_FILENAME']));

// Autoloading router
function perseus_autoload($class) {
  // Perseus classes
  if (strpos($class, 'Perseus') === 0) {
    $parts = explode('\\', $class);

    // Remove the 'Perseus' namespace
    array_shift($parts);

    // Check for extensions
    $ext_class = ($parts[0] == 'Extensions' ? "/{$parts[1]}" : '');

    // Get the classname
    $file  = PROOT . '/' . implode('/', $parts) . "{$ext_class}.class.php";
    if (is_file($file)) {
      require $file;
    }
  }

  // Twig classes (@see Twig's Autoloader.php)
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
