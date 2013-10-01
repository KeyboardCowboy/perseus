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

  // Rendering data
  public $build = array(
    'template' => 'form/form',
    'items' => array(),
  );

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
  public function addItem($name, $field) {
    // Add the field to the form.
    $this->build['items'][$name] = $field;
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
   * Prepare the form data for rendering.
   */
  public function prepare() {
    $this->attributes = array(
      'method'  => $this->method,
      'action'  => $this->action,
      'enctype' => $this->enctype,
      'name'    => $this->name,
      'id'      => unique_id($this->name),
    );

    // Sort the fields!
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
 * Create a form item to attach to a form.  A form item may be composed of
 * multiple FormElements such as labels and fields.
 */
class FormItem {
  public $type;
  public $name;
  public $value;
  public $label;
  public $description;
  public $placeholder;
  public $options;
  public $weight;
  public $validators = array();

  // Attributes
  public $attributes = array();
  public $label_attributes = array();
  public $description_attributes = array();
  public $wrapper_attributes = array();

  // The processed data used to render the templates.
  public $build = array(
    'template' => 'form/form-item',
    'items' => array(),
  );

  // Constructor
  public function __construct($type, $name) {
    $this->type = $type;
    $this->name = $name;
  }

  /**
   * Add a validation callback.
   */
  public function addValidator($callback) {
    $this->validators[] = $callback;
  }

  // Prepare the data to be rendered.
  public function prepare() {
    // Check the placeholder and other attributes.
    if ($this->placeholder) {
      $this->attributes['placeholder'] = $this->placeholder;
    }

    // Build the Label
    if ($this->label) {
      $label = new HtmlElement('label', $this->label_attributes);
      $label->content = $this->label;
      $label->attributes['for'] = $this->name;
      $label->weight = 0;
      $this->addItem('label', $label);
    }

    // Build the description
    if ($this->description) {
      $desc = new HtmlElement('div', $this->description_attributes);
      $desc->attributes['class'][] = 'desc';
      $desc->content = $this->description;
      $desc->weight = 2;
      $this->addItem('desc', $desc);
    }

    // Build the form element
    $class = "FormElement" . ucwords($this->type);
    $field = (class_exists($class) ? new $class($this->name, $this->attributes) : new FormElement('input', $this->type, $this->attributes));
    $field->weight = 1;
    $this->addItem('field', $field);
  }

  /**
   * Render the field item.
   */
  public function render() {
    // Prepare the data.
    $this->prepare();
  }
}

/**
 * Extend HTML Element to manage form elements.
 */
class FormElement extends HtmlElement {
  public $weight = NULL;
  public $name;
  public $type;

  // Constructor
  public function __construct($element, $type, $name, $attributes = array()) {
    parent::__construct($element, $attributes);

    $this->type = $type;
    $this->name = $name;
  }

  /**
   * Merge default values into the field settings.
   */
  public function mergeDefaults($defaults) {
    foreach ($defaults as $prop => $value) {
      if (property_exists($this, $prop) && is_null($this->{$prop})) {
        $this->{$prop} = $value;
      }
    }
  }

  /**
   * Prepare the item for rendering.
   */
  public function prepare() {
    $this->attributes['type'] = $this->type;
    $this->attributes['name'] = $this->name;

    parent::prepare();
  }

  /**
   * Build a single radio option.
   */
  protected function buildRadio(array $data) {
    $elements = array();

    $radio = new HtmlElement('radio', $data['attributes']);
    $radio->attributes['name'] = $data['name'];
    $radio->attributes['value'] = (empty($data['value']) ? 1 : $data['value']);
    $radio->weight = 0;
    $elements['radio'] = $radio;

    // Build the label
    if (!empty($data['label'])) {
      $label = new HtmlElement('label');
      $label->attributes['for'] = $data['name'];
      $label->weight = 1;
      $elements['label'] = $label;
    }

    return array(
      'wrapper' => 'div',
      'elements' => $elements,
    );
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
    $submit = new FormElement('input', $data['attributes']);
    $submit->attributes['type'] = 'submit';
    $submit->attributes['name']  = $data['name'];
    $submit->attributes['value'] = $data['value'];
    return $submit;
  }

  /**
   * Build a table.
   *
   * Not actually a form item.  Need to start branching an straight HTML object.
   */
  protected function buildTable(array $data) {
    return $this->system->theme('table', $data);
  }
}

/**
 * Text Field
 */
class FormElementText extends FormElement {
  public $size;
  public $maxlength;

  // Constructor
  public function __construct($name, $attributes = array()) {
    parent::__construct('input', 'text', $name, $attributes);
  }

  /**
   * Prepare the data
   */
  public function prepare() {
    if ($this->size) {
      $this->attributes['size'] = $this->size;
    }
    if ($this->maxlength) {
      $this->attributes['maxlength'] = $this->maxlength;
    }

    // Pass it on to the parent classes.
    parent::prepare();
  }
}
