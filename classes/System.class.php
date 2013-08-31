<?php
namespace Perseus;

/**
 * @file
 * Class to manage system variables and processes.
 */
define('SYSTEM_NOTICE',  1);
define('SYSTEM_WARNING', 2);
define('SYSTEM_ERROR',   3);

class System {
  // The server path to the root of the website.
  protected $siteroot;

  // Registered theme locations
  protected $themes = array();

  /**
   * Constructor
   *
   * @param $siteroot
   *   The server path to the root of the website.
   */
  public function __construct($siteroot) {
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

      // Register theme directories.
      $this->registerThemes();
    }
    catch (Exception $e) {$this->handleException($e);}
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
   * Return error codes.
   */
  static function errorCodes($code = NULL) {
    $codes = array(
      SYSTEM_NOTICE =>  'notice',
      SYSTEM_WARNING => 'warning',
      SYSTEM_ERROR =>   'error',
    );

    return ($code ? $codes[$code] : $codes);
  }

  /**
   * Retrieve an object from the Session var.
   */
  protected function fetchObject($name) {
    if (!empty($_SESSION['perseus']['object'][$name])) {
      return $_SESSION['perseus']['object'][$name];
    }
  }

  /**
   * Retrieve an object from the Session var.
   */
  protected function storeObject($obj, $name) {
    $_SESSION['perseus']['object'][$name] = $obj;
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
    $code = $e->getCode();
    $code = (is_numeric($code) && $code > 0 ? $code : 1);

    System::setMessage($e->getMessage(), $code);
  }

  /**
   * Initialize the system variables.
   */
  private function init($type = 'vars') {
    $file = $this->siteroot . '/settings/perseus.php';
    $init = array();

    if (file_exists($file)) {
      include($file);
      $init['vars'] = $vars;
      $init['db'] = $db;
    }
    else {
      throw new Exception('Unable to load perseus settings at ' . $file . '.', SYSTEM_ERROR);
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
  private function registerThemes() {
    // First, the default theme
    $this->themes[] = PROOT . '/theme';

    // Next, site overrides
    $site_theme = $this->siteroot . '/theme';
    if (file_exists($site_theme)) {
      $this->themes[] = $site_theme;
    }
  }

  /**
   * Theme an item.
   */
  public function theme($hook, $vars = array()) {
    // Call processors for each implementation.
    foreach ($this->themes as $theme) {
      $processor_file = "$theme/processors/{$hook}.inc";
      if (file_exists($processor_file)) {
        System::themeProcessVars($processor_file, $vars);
      }
    }

    // Look for a template starting with the most recently registered theme.
    foreach (array_reverse($this->themes) as $theme) {
      $template_file = "$theme/templates/{$hook}.tpl.php";
      if (file_exists($template_file)) {
        print System::themeRenderTemplate($template_file, $vars);
        return;
      }
    }

    // If no template, look for a function.
    $func = "theme_{$hook}";
    foreach (array_reverse($this->themes) as $theme) {
      include('theme/themes.inc');
      if (function_exists($func)) {
        print $func($vars);
        return;
      }
    }

    // Still nothing?
    print '';
  }

  /**
   * Process variables for a theme.
   *
   * Keep this in a separate function to isolate variable scope.
   */
  static function themeProcessVars($file, &$vars) {
    include($file);
  }

  /**
   * Render a system default template, which is essentially a PHP template.
   *
   * Borrowed from Drupal.
   * http://api.drupal.org/api/drupal/includes%21theme.inc/function/theme_render_template/7
   */
  static function themeRenderTemplate($template_file, $variables) {
    extract($variables, EXTR_SKIP); // Extract the variables to a local namespace
    ob_start(); // Start output buffering
    include($template_file); // Include the template file
    return ob_get_clean(); // End buffering and return its contents
  }

  /**
   * Get a variable from the system settings.
   */
  public function val($var, $default = NULL) {
    return (property_exists($this, $var) ? $this->$var : $default);
  }
}
