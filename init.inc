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
require_once(PROOT . '/classes/System.class.php');

// Bootstrap common utilities required by other bootstrap phases.
require_once(PROOT . '/includes/util.inc');

// Register Twig
//require_once(PROOT . '/theme/twig/lib/Twig/Autoloader.php');
//Twig_Autoloader::register();

// Autoloading router
function perseus_autoload($class) {
  //print '<pre>' . $class . '</pre>';

  // Perseus classes
  if (strpos($class, 'Perseus') === 0) {
    $parts = explode('\\', $class);
    $path = 'classes/' . end($parts) . '.class.php';

  //print '<pre>' . $path . '</pre>';

    require $path;
  }
  // Twig classes (@see Autoloader.php)
  elseif (strpos($class, 'Twig') === 0) {
    if (is_file($file = dirname(__FILE__) . '/theme/twig/lib/' . str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
      require $file;
    }
  }
  else {
    return;
  }
}
