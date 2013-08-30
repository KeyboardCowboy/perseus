<?php

define('SYSTEM_NOTICE',  1);
define('SYSTEM_WARNING', 2);
define('SYSTEM_ERROR',   3);

/**
 * @file
 * Class to manage system variables and processes.
 */
class System {
  public $docroot;
  public $proot;
  public $error_codes = array(
    SYSTEM_NOTICE =>  'Notice',
    SYSTEM_WARNING => 'Warning',
    SYSTEM_ERROR =>   'Error',
  );

  /**
   * Constructor
   */
  public function System() {
    // Instantiate system messages.
    if (!isset($_SESSION['messages'])) {
      $_SESSION['messages'] = array();
    }

    // Set base vars.
    $this->docroot = $_SERVER['DOCUMENT_ROOT'];
    $this->proot = __DIR__;

    // Load the system vars.
    try {
      $vars = $this->init();
      foreach ($vars as $name => $val) {
        $this->$name = $val;
      }
    }
    catch (Exception $e) {$this->handleException($e);}
  }

  /**
   * Set a status message.
   */
  public function setMessage($msg, $type = 'info') {
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
    $file = __DIR__ . "/$path";
    if (file_exists($file)) {
      include $file;
      return $file;
    }
  }

  /**
   * Require a file.
   */
  static function fileRequire($path) {
    $file = __DIR__ . "/$path";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }

  /**
   * Exception Handler
   */
  public function handleException($e) {
    $this->setMessage($e->getMessage(), $e->getCode());
  }

  /**
   * Initialize the system variables.
   */
  private function init($type = 'vars') {
    $file = DOCROOT . '/perseus.settings.php';
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
   * Get a variable from the system settings.
   */
  public function val($var, $default = NULL) {
    return (property_exists($this, $var) ? $this->$var : $default);
  }

}
