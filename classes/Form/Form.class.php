<?php
/**
 * @file
 * Define common form processing and HTML elements.
 */
namespace Perseus;

class Form extends Service {
  // A unique name of the form.
  protected $name;

  // Form parameters
  private $action;
  private $method;
  private $enctype = '';

  // Form fields
  private $fields = array();

  // Default field values
  protected $defaults = array();

  // Weight incrementer for unweighted form fields.
  private $weight = 0;

  /**
   * Constructor
   */
  public function __construct($system, array $settings = array()) {
    parent::__construct($system);

    $this->name    = (isset($settings['name']) ? $settings['name'] : uniqid());
    $this->action  = (isset($settings['action']) ? filter_xss($action) : filter_xss($_SERVER['PHP_SELF']));
    $this->method  = (isset($settings['method']) ? $method : 'POST');
    $this->enctype = (isset($settings['enctype']) ? $enctype : 'multipart/form-data');
  }

  /**
   * Add a form item to the form.
   */
  public function addItem($type, $data, $weight = NULL) {
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

      // Default form field settings.
      $defaults = array(
        'label' => '',
        'description' => '',
        'options' => array(),
        'attributes' => array('class' => array('form-item')),
      );

      // Add default values
      $data += $defaults;

      $item_default = array(
        'weight' => (is_null($weight) ? $this->weight += 5 : $weight),
        'name'   => $data['name'],
      );

      // Create the item.
      $this->fields[$data['name']] = $this->{$method}($data) + $item_default;
    }
    catch(Exception $e) {System::handleException($e);}
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
    $radios = array();

    // Calculate grouping
    $groupcnt = (isset($data['cols']) ? ceil(count($data['options']) / $data['cols']) : count($data['options']));
    $g = 1;

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

      $radio = $this->system->theme('form/radio', array('attributes' => $attributes, 'label' => $label));
      $radios[ceil($g/$groupcnt)][] = $this->system->theme('form/form-element', array('output' => $radio, 'attributes' => array()));
      $g++;
    }

    $data['output'] = $this->system->theme('form/radios', array('options' => $radios));
    $data['attributes']['class'][] = 'radios';
    $data['attributes']['class'][] = $data['name'];

    return $this->system->theme('form/form-element', $data);
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

      $option = $this->system->theme('form/select-option', $vars);
      $options .= $option;
    }

    // Build the select element.
    $data['attributes']['name'] = $data['name'];
    $data['output'] = $options;
    $select = $this->system->theme('form/select', $data);

    // Wrap and return
    $element['attributes']['class'][] = 'select';
    $element['attributes']['class'][] = $data['name'];
    $element['label'] = $data['label'];
    $element['output'] = $select;

    return $this->system->theme('form/form-element', $element);
  }

  /**
   * Build a submit button.
   */
  protected function buildSubmit(array $data) {
    $data['attributes']['type'] = 'submit';
    $data['attributes']['name'] = $data['name'];
    return $this->system->theme('form/submit', $data);
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
   * Build a text field.
   */
  protected function buildText(array $data) {
    $input = new FormElement('input');
    $input->attributes = $data['attributes'];
    $input->attributes['type'] = 'text';
    $input->attributes['name'] = $data['name'];
    $input->weight = 1;

    // Construct the elements
    if (!empty($data['label'])) {
      $label = new FormElement('label');
      $label->attributes['for'] = $data['name'];
      $label->value = $data['label'];
      $label->weight = 0;
    }

    $input->setPlaceholder($data);

    $elements = array(
      'label' => $label,
      'input' => $input,
    );

    return array(
      'wrapper' => 'div',
      'attributes' => array(
        'class' => array('form-text'),
      ),
      'elements' => $elements,
    );
  }

  /**
   * Render the form.
   */
  public function render() {
    $out = '';

    try {
      $out = $this->_prepareRender($this->fields);
    }
    catch(Exception $e) {System::handleException($e);}

    $vars['output'] = $out;
    $vars['attributes'] = array(
      'method'  => $this->method,
      'action'  => $this->action,
      'enctype' => $this->enctype,
      'name'    => $this->name,
      'id'      => unique_id($this->name),
    );

    return $this->system->theme('form/form', $vars);
  }

  /**
   * Helper to recursively render fields.
   */
  private function _prepareRender($fields) {
    $markup = '';
    $_fields = array();

    // Sort the fields
    foreach ($fields as $field) {
      $field_array = (array) $field;
      $weight = (isset($field_array['weight']) ? $field_array['weight'] : 0);
      $_fields[$weight + 10000000] = $field;
    }

    ksort($_fields);

    // Recurse through the items and theme each element.
    foreach ($_fields as $key => $field) {
      //list($weight,$name) = explode(':', $key);

      // The field may be an HtmlElement Object, an array of fields or a
      // rendered field.
      if (is_object($field)) {
        $markup .= $this->system->render($field);
      }
      elseif (is_string($field)) {
        $markup .= $field;
      }
      elseif (is_array($field)) {
        if (!empty($field['elements']) && isset($field['wrapper'])) {
          $wrapper = new HtmlElement($field['wrapper']);
          $wrapper->value = $this->_prepareRender($field['elements']);

          if (isset($field['attributes']) && is_array($field['attributes'])) {
            $wrapper->attributes = $field['attributes'] + array(
              'id' => unique_id("form-item-{$field['name']}"),
              'class' => array('form-item'),
            );
          }

          $markup .= $this->system->render($wrapper);
        }
        elseif (!empty($field['elements'])) {
          $markup .= $this->_prepareRender($field['elements']);
        }
        else {
          $markup .= $this->_prepareRender($field);
        }
      }
    }

    return $markup;
  }
}

/**
 * Extend HTML Element to manage form elements.
 */
class FormElement extends HtmlElement {
  public $validators = array();

  // Constructor
  public function __construct($type) {
    parent::__construct($type);
  }

  /**
   * Set the placeholder attribute.
   */
  public function setPlaceholder($data) {
    if (isset($data['placeholder'])) {
      if ($data['placeholder'] === TRUE && !empty($data['label'])) {
        $this->attributes['placeholder'] = check_plain($data['label']);
      }
      elseif (is_string($data['placeholder'])) {
        $this->attributes['placeholder'] = check_plain($data['placeholder']);
      }
    }
  }
}

