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
  public function Debug() {}

  /**
   * Dump a variable.
   */
  static function dump($var) {
    print '<pre>' . print_r($var, 1) . '</pre>';
  }
}
