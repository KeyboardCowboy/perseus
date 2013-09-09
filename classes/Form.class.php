<?php
/**
 * @file
 * Define common form processing and HTML elements.
 */
namespace Perseus;

class Form {
  // A unique name of the form.
  protected $name;

  // Form parameters
  private $action;
  private $method;
  private $enctype = '';

  // The perseus system managing the form.
  protected $system;

  // Form fields
  private $fields = array();

  // Default form field settings.
  static $defaults = array(
    'label' => '',
    'description' => '',
    'options' => array(),
    'attributes' => array('class' => array('form-item')),
  );

  // Weight incrementer for unweighted form fields.
  private $weight = 0;

  /**
   * Constructor
   */
  public function __construct($perseus, $name, $action = NULL, $method = NULL, $enctype = NULL) {
    $this->system  = $perseus;
    $this->name    = $name;
    $this->action  = ($action ? filter_xss($action) : filter_xss($_SERVER['PHP_SELF']));
    $this->method  = ($method ? $method : 'POST');
    $this->enctype = ($enctype ? $enctype : '');
  }

  /**
   * Add a form item to the form.
   */
  public function addItem($type, $data, $weight = 0) {
    try {
      // Make sure the field definition method exists.
      if (!method_exists($this, $type)) {
        throw new Exception("Undefined form field type: {$type}", SYSTEM_ERROR);
      }

      // Make sure the form field has a name
      if (!isset($data['name'])) {
        throw new Exception("Name not provided for form field.", SYSTEM_ERROR);
      }

      // Autoincrememnt a weight for the item if not provided.
      if (!$weight) {
        $weight = $this->weight =+ 5;
      }

      // Add default values
      $data += Form::$defaults;

      // Create the item.
      $this->fields["{$weight}:{$data['name']}"] = $this->{$type}($data);
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Form builder functions.
   */
  protected function radios(array $data) {
    $radios = '';
    foreach ($data['options'] as $value => $label) {
      $attributes = array(
        'input' => array(
          'value' => $value,
          'name'  => $data['name'],
        ),
        'label' => array(
          'for' => $value,
        ),
      );

      // Add the selected value
      if (isset($data['default']) && $value == $data['default']) {
        $attributes['input']['checked'] = 'checked';
      }

      $radio = $this->system->theme('radio', array('attributes' => $attributes, 'label' => $label));
      $radios .= $this->system->theme('form-element', array('output' => $radio, 'attributes' => array()));
    }

    $data['output'] = $radios;
    $data['attributes']['class'][] = 'radios';
    $data['attributes']['class'][] = $data['name'];

    return $this->system->theme('form-element', $data);
  }

  /**
   * Render the form.
   */
  public function render() {
    $out = '';

    try {
      ksort($this->fields);

      foreach ($this->fields as $weight => $field) {
        list(,$name) = explode(':', $weight);
        $out .= $field;
      }
    }
    catch(Exception $e) {System::handleException($e);}

    return $out;
  }
}
