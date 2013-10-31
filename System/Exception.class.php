<?php
namespace Perseus\System;

/**
 * Regular exception handler for our namespace.
 */
class Exception extends \Exception {
  public function __construct($message, $code) {
    $code = (is_numeric($code) && $code > 0 ? $code : 1);
    System::setMessage($message, $code);
    //parent::__construct($message, $code);
  }
}
