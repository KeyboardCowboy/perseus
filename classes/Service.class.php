<?php
/**
 * @file
 * Base class to handle system services.
 */
namespace Perseus;

class Service {
  // The system object managing the service.
  protected $system;

  public function __construct($system) {
    try {
      if ($system instanceof \Perseus\System) {
        $this->system = $system;
      }
      else {
        throw new Exception('Inavlid system object.', SYSTEM_ERROR);
      }
    }
    catch(Exception $e){System::handleException($e);}
  }
}
