<?php
/**
 * @file
 * Debugging and logging class.
 */
class Perseus_Debug {
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
