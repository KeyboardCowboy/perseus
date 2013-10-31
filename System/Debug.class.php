<?php
namespace Perseus\System;

/**
 * @file
 * Debugging and logging class.
 */
class Debug {
  /**
   * Constructor
   */
  public function __construct() {}

  /**
   * Dump a variable.
   */
  static function dump($var, $name = NULL, $return = FALSE) {
    $str = '<pre>' . print_r($var, 1) . '</pre>';

    if ($return) {
      return $str;
    }
    else {
      print $str;
    }
  }

  /**
   * Krumo a variable.
   */
  static function kdump($var, $name = NULL) {
    if (function_exists('krumo')) {
      krumo($var);
    }
    else {
      self::dump($var, $name);
    }
  }

  /**
   * Dump a variable into a message.
   */
  static function mdump($var, $name = NULL) {
    $message = self::dump($var, $name, TRUE);
    System::setMessage($message, SYSTEM_DEBUG);
  }

  /**
   * Krumo a variable into a message.
   */
  static function mkdump($var, $name = NULL) {
    if (function_exists('krumo')) {
      $message = self::captureKrumo($var);
      System::setMessage($message, SYSTEM_DEBUG);
    }
    else {
      self::mdump($var, $name);
    }
  }

  /**
   * Load the krumo library
   */
  static function loadKrumo($krumo) {
    $path = PROOT . '/includes/krumo';

    // Write the .ini file
    $ini = array(
      'skin' => array('selected' => $krumo['skin']),
      'css'  => array('url' => $path),
    );
    $content = parse_ini_array($ini);
    file_put_contents("$path/krumo.ini", $content);

    // Include the Krumo class
    include_once("{$path}/class.krumo.php");
  }

  /**
   * Capture the value from a krumo.
   */
  public static function captureKrumo($var) {
    ob_start();
    \krumo($var);
    return ob_get_clean();
  }
}
