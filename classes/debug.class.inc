<?php
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
  public function dump($var) {
    print '<pre>' . htmlentities(print_r($var, 1)) . '</pre>';
  }
}
