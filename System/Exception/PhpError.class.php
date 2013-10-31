<?php
/**
 * Exception handler to grab PHP errors.
 */
namespace Perseus\System\Exception;

use Perseus\System as Perseus;
use Perseus\System\System;
use Perseus\System\Debug;

class PhpError extends Perseus\Exception {
  private $php_error_code = array(
    E_ERROR             => 'ERROR',
    E_WARNING           => 'WARNING',
    E_PARSE             => 'PARSE',
    E_NOTICE            => 'NOTICE',
    E_CORE_ERROR        => 'CORE ERROR',
    E_CORE_WARNING      => 'CORE WARNING',
    E_CORE_ERROR        => 'COMPILE ERROR',
    E_CORE_WARNING      => 'COMPILE WARNING',
    E_USER_ERROR        => 'USER ERROR',
    E_USER_WARNING      => 'USER WARNING',
    E_USER_NOTICE       => 'USER NOTICE',
    E_STRICT            => 'STRICT',
    E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
    E_DEPRECATED        => 'DEPRECATED',
    E_USER_DEPRECATED   => 'USER DEPRECATED',
  );

  // Constructor
  public function __construct($errno, $errstr, $errfile, $errline, $system, $krumo) {
    $error = $this->php_error_code[$errno];

    $vars = array(
      'type'    => $error,
      'errno'   => $errno,
      'errstr'  => $errstr,
      'errfile' => $errfile,
      'errline' => $errline,
    );
    $message = $system->theme('system/php-error', $vars);

    System::setMessage($message, SYSTEM_ERROR);

    if ($krumo) {
      $backtrace = debug_backtrace();
      // Remove exception handler
      array_shift($backtrace);

      // Remove error handler
      array_shift($backtrace);

      // Give the keys a better context
      $_backtrace = array();
      $i = 0;
      foreach ($backtrace as $item) {
        $key = $i++ . " ";
        if (isset($item['class'])) {
          $key .= $item['class'] . ":";
        }
        $key .= $item['function'];

        $_backtrace[$key] = $item;
      }

      $message = Debug::captureKrumo($_backtrace);
      System::setMessage($message, SYSTEM_ERROR);
    }
  }
}
