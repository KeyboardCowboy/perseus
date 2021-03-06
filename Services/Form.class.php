<?php
/**
 * @file
 * Define common form processing and HTML elements.
 */
namespace Perseus\Services;

use Perseus\System as Perseus;
use Perseus\System\System;

/**
 * Interface for Form objects.
 */
interface FormInterface {
  /**
   * Constructor
   *
   * @param $settings
   *   An array of form settings for the action, method, name etc.
   */
  public function __construct(array $settings = array());

  /**
   * Add generic form validation functionality.  Each form item may also
   * implement specific validators.  The parent validator should be called first.
   */
  public function validate();

  /**
   * Add generic submission functionality.  Each form item may also implement
   * specific submittors.  The parent submittor should be called first.
   */
  public function submit();
}

/**
 * Define a renderable form.
 */
class Form extends Perseus\Renderable implements FormInterface {
  const UNSUBMITTED = 0;
  const UNVALIDATED = 1;
  const INCOMPLETE  = 2;
  const INVALID     = 3;
  const VALID       = 4;

  private $state = self::UNSUBMITTED;

  // Submitted Data
  protected $data;

  // A flat list of all form items.
  public $items = array();

  // A unique name of the form.
  protected $name;

  // Form parameters
  private $action;
  private $method;
  private $enctype = '';

  // Weight incrementer for unweighted form fields.
  public $weight = 0;

  /**
   * Constructor
   */
  public function __construct(array $settings = array()) {
    parent::__construct('form/form');

    // Define the settings.
    $this->name    = (isset($settings['name']) ? $settings['name'] : uniqid());
    $this->action  = (isset($settings['action']) ? filter_xss($action) : filter_xss($_SERVER['PHP_SELF']));
    $this->method  = (isset($settings['method']) ? $settings['method'] : 'POST');
    $this->enctype = (isset($settings['enctype']) ? $settings['enctype'] : 'multipart/form-data');

    $this->state = self::UNSUBMITTED;

    // As soon as the form is instantiated, look for submitted form data to
    // store.
    try {
      $this->processFormData();
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Look for submitted form data and process it.
   */
  protected function processFormData() {
    if ($this->method == 'POST' && !empty($_POST)) {
      $this->data = $_POST;
    }
    elseif ($this->method == 'GET' && !empty($_GET)) {
      $this->data = $_GET;
    }

    // If we found submitted data, validate the form.
    if ($this->data) {
      $this->state = self::INCOMPLETE;
    }
  }

  /**
   * Add a form item to the form.
   */
  public function addChild($key, Form\Item $item) {
    // @TODO: Validate each field as it is added to the form.  The form will
    // pick up an submitted data on instantiaion. This way we don't have to rely
    // on the child classes to call the validation method.
    if ($this->data) {
      if (isset($this->data[$item->name])) {
        $item->submitted_value = $this->data[$item->name];
      }
      $item->validated = FALSE;
      $item->validate();
      $item->validated = TRUE;
    }

    // @TODO: Does the above method make this unnecessary?
    // Log the fields for validation and submission.
    $this->items[$key] = $item;

    // Add the child to be rendered.
    parent::addChild($key, $item);
  }

  /**
   * Validate the form.
   */
  public function validate() {
    if (!$this->data) {
      return FALSE;
    }

    // Fields were all validated when they were added to the form.  Now we can
    // simply cycle through them to look for any invalid fields.
    $valid = TRUE;
    foreach ($this->items as $item) {
      if (!$item->isValid()) {
        $valid = FALSE;
        break;
      }
    }

    // Set the validation state depending on the field statuses.
    $this->state = ($valid ? self::VALID : self::INVALID);
  }

  /**
   * Submit handler for the form.  This should be extended into your own form
   * to process and store data.
   */
  public function submit() {
    // Do not submit unvalidated data!
    switch ($this->state) {
      case self::UNSUBMITTED:
        return FALSE;
        break;

      case self::UNVALIDATED:
      case self::INCOMPLETE:
        System::setMessage('Form has not been validated.', SYSTEM_ERROR);
        return FALSE;
        break;

      case self::INVALID:
        System::setMessage('There are errors in the form that need to be corrected.', SYSTEM_ERROR);
        return FALSE;
        break;

      case self::VALID:
        $this->fields = array();

        // Cycle through field submittors.
        foreach ($this->items as $name => $item) {
          $item->submit();
          $this->fields[$item->name] = $item;
        }
        return TRUE;
        break;
    }
  }

  /**
   * Prepare the form data for rendering.
   */
  public function prepare() {
    $this->addBuildData('attributes', array(
      'method'  => $this->method,
      'action'  => $this->action,
      'enctype' => $this->enctype,
      'name'    => $this->name,
      'id'      => unique_id($this->name),
    ), TRUE);

    // Ensure the proper values are set for each form item depending on whether
    // or not the form has successfully validated.
    foreach ($this->items as $item) {
      $item->setValue($this->state);
    }
  }

  /**
   * Encapsulated function to validate and submit the form.
   */
  final public function process() {
    if ($this->data) {
      $this->validate();

      if ($this->state == self::VALID) {
        $this->submit();
      }
    }
  }
}
