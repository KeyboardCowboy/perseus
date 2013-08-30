<?php
/**
 * @file
 * Class to manage system variables and processes.
 */
define('SYSTEM_NOTICE',  1);
define('SYSTEM_WARNING', 2);
define('SYSTEM_ERROR',   3);

class System {
  // The server path to the root of the website.
  private $siteroot;

  // Registered theme locations
  private $themes;

  // Define readable error codes
  public $error_codes = array(
    SYSTEM_NOTICE =>  'Notice',
    SYSTEM_WARNING => 'Warning',
    SYSTEM_ERROR =>   'Error',
  );

  /**
   * Constructor
   *
   * @param $siteroot
   *   The server path to the root of the website.
   */
  public function System($siteroot) {
    $this->siteroot = $siteroot;

    // Instantiate system messages.
    if (!isset($_SESSION['messages'])) {
      $_SESSION['messages'] = array();
    }

    try {
      // Load the system vars.
      $vars = $this->init();
      foreach ($vars as $name => $val) {
        $this->$name = $val;
      }

      // Register the base theme directory.
      $this->registerTheme();
    }
    catch (Exception $e) {$this->handleException($e);}
  }

  /**
   * Load additional services on request,
   */
  public function load($service) {
    try {
      System::fileRequire("services/$service.class.php");
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Set a status message.
   */
  static function setMessage($msg, $type = 'info') {
    $_SESSION['messages'][$type][] = $msg;
  }

  /**
   * Retrieve a message.
   */
  static function getMessages($type = NULL, $purge = TRUE) {
    if (isset($type)) {
      $messages = $_SESSION['messages'][$type];
      if ($purge) {
        unset($_SESSION['messages'][$type]);
      }
    }
    else {
      $messages = $_SESSION['messages'];
      if ($purge) {
        unset($_SESSION['messages']);
      }
    }

    return (array)$messages;
  }

  /**
   * Get DB creds.
   */
  public function db($dbname) {
    $db = $this->init('db');
    return (isset($db[$dbname]) ? $db[$dbname] : array());
  }

  /**
   * Include a file.
   */
  static function fileInclude($path) {
    $file = PROOT . "/$path";
    if (file_exists($file)) {
      include $file;
      return $file;
    }
    else {
      throw new Exception("Unable to locate file at $file.", SYSTEM_WARNING);
    }
  }

  /**
   * Require a file.
   */
  static function fileRequire($path) {
    $file = PROOT . "/$path";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
    else {
      throw new Exception("Unable to locate file at $file.", SYSTEM_ERROR);
    }
  }

  /**
   * Exception Handler
   */
  static function handleException($e) {
    System::setMessage($e->getMessage(), $e->getCode());
  }

  /**
   * Initialize the system variables.
   */
  private function init($type = 'vars') {
    $file = $this->siteroot . '/perseus.settings.php';
    $init = array();

    if (file_exists($file)) {
      include($file);
      $init['vars'] = $vars;
      $init['db'] = $db;
    }
    else {
      throw new Exception('Unable to load perseus settings.', SYSTEM_ERROR);
    }

    return ($type ? $init[$type] : $init);
  }

  /**
   * Redirect to a new URL.
   */
  public function redirect($path, $options = array(), $code = '302') {
    $url = url($path, $options);
    header("Location: $url", TRUE, $code);
    exit;
  }

  /**
   * Register a theme.
   *
   * @param $loc
   *   The location of the theme directory relative to docroot.
   */
  static function registerTheme($loc = NULL) {
    $loc = ($loc ? DOCROOT . "/$loc" : PROOT . '/theme');

    if (file_exists($loc)) {
      $this->themes[] = $loc;
    }
    else {
      System::setMessage('Unable to locate theme at $loc.', SYSTEM_WARNING);
    }
  }

  /**
   * Get a variable from the system settings.
   */
  public function val($var, $default = NULL) {
    return (property_exists($this, $var) ? $this->$var : $default);
  }
}
