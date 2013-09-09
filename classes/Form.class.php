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
      $method = "build" . ucwords($type);
      if (!method_exists($this, $method)) {
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
      $this->fields["{$weight}:{$data['name']}"] = $this->{$method}($data);
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Build a set of radio options.
   */
  protected function buildRadios(array $data) {
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
   * Build a Select list.
   */
  protected function buildSelect(array $data) {
    $options = '';

    // Build the options
    foreach ($data['options'] as $value => $label) {
      $vars = array(
        'attributes' => array(
          'value' => $value,
        ),
        'label' => $label,
      );

      $option = $this->system->theme('select-option', $vars);
      $options .= $option;
    }

    // Build the select element.
    $data['attributes']['name'] = $data['name'];
    $data['output'] = $options;
    $select = $this->system->theme('select', $data);

    // Wrap and return
    $element['attributes']['class'][] = 'select';
    $element['attributes']['class'][] = $data['name'];
    $element['label'] = $data['label'];
    $element['output'] = $select;

    return $this->system->theme('form-element', $element);
  }

  /**
   * Build a table.
   *
   * Not actually a form item.  Need to start branching an straight HTML object.
   */
  protected function buildTable(array $data) {
    return $this->system->theme('table', $data);
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
