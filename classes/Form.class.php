<?php
/**
 * @file
 * Define common form processing and HTML elements.
 */
class Perseus_Form {
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

  // Default field values
  protected $defaults = array();

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
    $this->enctype = ($enctype ? $enctype : 'multipart/form-data');
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

      // Default form field settings.
      $defaults = array(
        'label' => '',
        'description' => '',
        'options' => array(),
        'attributes' => array('class' => array('form-item')),
      );

      // Add default values
      $data += $defaults;

      // Create the item.
      $this->fields["{$weight}:{$data['name']}"] = $this->{$method}($data);
    }
    catch(Exception $e) {Perseus_System::handleException($e);}
  }

  /**
   * Set default values
   */
  public function setDefaults($vals) {
    // Sanitize the data.
    foreach ($vals as $field => $val) {
      $this->defaults[check_plain($field)] = check_plain($val);
    }
  }

  /**
   * Build a set of radio options.
   */
  protected function buildRadios(array $data) {
    $radios = '';

    // Calculate grouping
    $groupcnt = (isset($data['cols']) ? ceil(count($data['options']) / $data['cols']) : count($data['options']));
    $g = 0;

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

      $g++;

      $radios[ceil($g/$groupcnt)][] = $this->system->theme('form-element', array('output' => $radio, 'attributes' => array()));
    }

    $data['output'] = $this->system->theme('radios', array('options' => $radios));
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
      $attributes = array('value' => $value);
      $vars = array('label' => $label);

      // Add the selected value
      if (isset($data['default']) && $value == $data['default']) {
        $attributes['selected'] = 'selected';
      }

      $vars['attributes'] = $attributes;

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
   * Build a submit button.
   */
  protected function buildSubmit(array $data) {
    $data['attributes']['type'] = 'submit';
    $data['attributes']['name'] = $data['name'];
    return $this->system->theme('submit', $data);
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
    catch(Exception $e) {Perseus_System::handleException($e);}

    $vars['output'] = $out;
    $vars['attributes'] = array(
      'method'  => $this->method,
      'action'  => $this->action,
      'enctype' => $this->enctype,
      'name'    => $this->name,
      'id'      => unique_id($this->name),
    );

    return $this->system->theme('form', $vars);
  }
}
