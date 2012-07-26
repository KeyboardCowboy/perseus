<?php
/**
 * @file
 * Class to manage system variables and processes.
 */
class System {
  public $docroot;

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

    // Load the system vars.
    $vars = $this->init();
    foreach ($vars as $name => $val) {
      $this->$name = $val;
    }
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
  public function getMessages($type = NULL, $purge = TRUE) {
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
  public function fileInclude($path) {
    $file = DOCROOT . "/kctools/$path";
    if (file_exists($file)) {
      include $file;
      return $file;
    }
  }

  /**
   * Require a file.
   */
  public function fileRequire($path) {
    $file = DOCROOT . "/kctools/$path";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }

  /**
   * Initialize the system variables.
   */
  private function init($type = 'vars') {
    include(DOCROOT . '/kctools/private/kctools.settings.php');

    $init['vars'] = $vars;
    $init['db'] = $db;

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
