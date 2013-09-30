<?php
/**
 * @file
 * Base class to handle system services.
 */
namespace Perseus;

class Service implements ServiceInterface {
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

/**
 * Service Interface
 */
interface ServiceInterface {
  /**
   * Constructor
   *
   * @param $system
   *   Each service must have a referencable system object to leverage in order
   *   to perform its tasks.
   */
  public function __construct($system);
}
