<?php
namespace Perseus;

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
  static function dump($var, $name = NULL) {
    print '<pre>' . print_r($var, 1) . '</pre>';
  }

  static function kdump($var, $name = NULL) {
    \krumo($var);
  }

  static function captureKrumoOutput($var) {
    ob_start();
    \krumo($var);
    return ob_get_clean();
  }
}
