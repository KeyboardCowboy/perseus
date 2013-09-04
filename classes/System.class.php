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
  // Database connctions
  private $db = array();

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
  static function setMessage($msg, $type = SYSTEM_NOTICE) {
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
   * Flood Controler.  Registers an event into the flood log.
   */
  public function floodRegisterEvent($name, $window = 3600, $identifier = NULL) {
    if (!isset($identifier)) {
      $identifier = $_SERVER['REMOTE_ADDR'];
    }

    // Prepare the data
    $data = array(
      'event' => $name,
      'identifier' => $identifier,
      'timestamp' => time(),
      'expiration' => time() + $window,
    );

    // Requires a MySQL connection.
    try {
      $sql = $this->db();
      $sql->insert('flood', $data);
    }
    catch (Exception $e) {$this->handleException($e);}
  }

  /**
   * Get an instance of the database object.
   */
  public function db($database = 'default') {
    try {
      if (isset($this->db[$database]) && is_object($this->db[$database])) {
        return $this->db[$database];
      }
      else {
        // Get the creds.
        $creds = $this->init('db');
        if (isset($creds[$database])) {
          $db = new MySQL($creds[$database]);

          if ($db->isConnected()) {
            $this->db[$database] = $db;
            return $this->db[$database];
          }
          else {
            throw new Exception('Unable to load database.  Connection error.', SYSTEM_ERROR);
          }
        }
        else {
          throw new Exception('Unable to load database.  Credentials not provided.', SYSTEM_ERROR);
        }
      }
    }
    catch (Exception $e) {$this->handleException($e);}
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
    $file = DOCROOT . '/settings/perseus.php';
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

/**
 * Installer Class
 */
class SystemInstaller extends Installer implements InstallerInterface {
  // Register installation procedures
  private $install = array('flood');

  /**
   * Constructor
   */
  public function __construct($system) {
    parent::__construct($system);
  }

  /**
   * Install/configure the necessary parts for the tool to function properly.
   */
  public function install($do = array()) {
    try {
      $do = (array) $do;

      if (empty($do)) {
        $do = $this->install;
      }

      // Run each installation procedure.
      foreach ($do as $install) {
        switch ($install) {
          case 'flood':
            $this->createTable('flood');
            break;
        }
      }
    }
    catch(Exception $e){System::handleException($e);}
  }

  /**
   * Define the database schemas
   */
  public function schema($table) {
    $schema['flood'] = "CREATE TABLE IF NOT EXISTS flood (
      fid int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique flood event ID.',
      event varchar(64) NOT NULL DEFAULT '' COMMENT 'Name of event (e.g. contact).',
      identifier varchar(128) NOT NULL DEFAULT '' COMMENT 'Identifier of the visitor, such as an IP address or hostname.',
      timestamp int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp of the event.',
      expiration int(11) NOT NULL DEFAULT '0' COMMENT 'Expiration timestamp. Expired events are purged on cron run.',
      PRIMARY KEY (fid),
      KEY allow (event,identifier,timestamp),
      KEY purge (expiration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Flood controls the threshold of events, such as the...' AUTO_INCREMENT=1 ;";

    return (isset($schema[$table]) ? $schema[$table] : '');
  }
}

