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
    print "{$class}";
    $parts = explode('\\', $class);

    // Take into account class families in the format Type_SubClass.
    $family = array_pop($parts);
    $family = explode('_', $family);
    if (count($family) == 1) {
      $parent = $child = $family[0];
    }
    else {
      $parent = array_shift($family);
      $child  = implode('/', $family);
    }

    // Remove the 'Perseus' namespace
    array_shift($parts);

    // Create the path from the namespace.
    $file = PROOT . '/' . strtolower(implode('/', $parts)) . "/{$parent}/{$child}.class.php";
    print " => $file<br />";
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
