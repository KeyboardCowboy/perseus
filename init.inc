<?php
/**
 * @file
 * Perseus Library Bootstrapper.
 */
define('DOCROOT', $_SERVER['DOCUMENT_ROOT']);
define('PROOT', __DIR__);

session_start();

spl_autoload_register('perseus_autoload');

// Bootstrap basic system tools and variables.
require_once(PROOT . '/classes/System/System.class.php');

// Bootstrap common utilities required by other bootstrap phases.
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

    // Use the remaining part of the namespace to determine the location of the
    // class
    $type = reset($parts);
    switch (strtolower($type)) {
      case 'tools':
        $path = "tools/{$class}/{$class}.class.php";
        break;

      default:
        $path = "classes/{$class}/{$class}.class.php";
        break;
    }

    require $path;
  }

  // Twig classes (@see Autoloader.php)
  elseif (strpos($class, 'Twig') === 0) {
    if (is_file($file = dirname(__FILE__) . '/theme/twig/lib/' . str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
      require $file;
    }
  }

  // Die nicely.
  else {
    return;
  }
}
