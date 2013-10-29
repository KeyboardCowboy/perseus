<?php
/**
 * @file
 * Define common form processing and HTML elements.
 *
 * Composition
 * - Form
 *   - FormItem(s)
 *     - FormElement(s) => HtmlElement(s)
 *
 * Example
 * - Form
 *   - Text Field
 *     - label
 *     - input
 *     - div (description, etc)
 */
namespace Perseus\Services;

use Perseus\System as Perseus;
use Perseus\System\System;
use Perseus\System\HtmlElement;

class Form extends Perseus\Service implements FormInterface {
  const UNSUBMITTED = 0;
  const UNVALIDATED = 1;
  const INCOMPLETE  = 2;
  const INVALID     = 3;
  const VALID       = 4;

  private $state = self::UNSUBMITTED;

  // The form element to render.
  public $form;

  // Submitted Data
  protected $data = array();

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
  public function __construct($system, array $settings = array()) {
    parent::__construct($system);

    $this->form = new HtmlElement('form');
    $this->form->_build['template'] = 'form/form';

    $this->name    = (isset($settings['name']) ? $settings['name'] : uniqid());
    $this->action  = (isset($settings['action']) ? filter_xss($action) : filter_xss($_SERVER['PHP_SELF']));
    $this->method  = (isset($settings['method']) ? $method : 'POST');
    $this->enctype = (isset($settings['enctype']) ? $enctype : 'multipart/form-data');

    $this->state = self::UNSUBMITTED;

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
  public function addItem($name, FormItem $item, $wrap = FALSE) {
    $item->wrap = $wrap;

    // Check for submitted data to assign as the value of the field.
    switch ($item->type) {
      case 'radio':
      case 'checkbox':
      case 'submit':
        if (isset($this->data[$item->name])) {
          $item->posted_value = $this->data[$item->name];
        }
        break;

      default:
        if (isset($this->data[$item->name])) {
          $item->posted_value = $this->data[$item->name];
        }
        break;
    }

    // Log the fields for validation and submission.
    $this->items[$name] = $item;
  }

  /**
   * Validate the form.
   */
  public function validate() {
    if (!$this->data) {
      return FALSE;
    }

    $valid = TRUE;

    // Cycle through field validators.
    foreach ($this->items as $item) {
      // Check for required fields.  We do this first so the information is
      // available to the item's extended validation function and it can
      // call the parent validator last.
      if ($item->required && !$item->posted_value) {
        System::setMessage("Field '{$item->label}' is required.", SYSTEM_ERROR);
        $item->is_valid = FALSE;
        $valid = FALSE;
      }

      // Call each field's own validation method.
      $item->validate();
      if (!$item->isValid()) {
        $valid = FALSE;
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
    }

    // Do not submit unvalidated data!
    if ($this->state != self::VALID) {
      System::setMessage('Form has not been validated.', SYSTEM_ERROR);
      return FALSE;
    }

    // Cycle through field submittors.
    foreach ($this->items as $name => $item) {
      $item->submit();
    }
  }

  /**
   * Prepare the form data for rendering.
   */
  public function prepare() {
    $this->form->attributes = array(
      'method'  => $this->method,
      'action'  => $this->action,
      'enctype' => $this->enctype,
      'name'    => $this->name,
      'id'      => unique_id($this->name),
    );

    foreach ($this->items as $item) {
      $item->prepare();

      // Prepare the renderable elements.
      if ($item->wrap) {
        $wrapper = new HtmlElement('div');
        $wrapper->addCssClass(array('form-item', $item->name, $item->type));
        $wrapper->_build['template'][] = 'form/form-item';
        $wrapper->_build['template'][] = 'form/form-item-' . $item->type;
        $wrapper->_build['children'] = $item->_build['children'];

        // Add a flag to aid in rendering.
        if ($item->required) {
          $wrapper->required = TRUE;
        }

        $this->form->_build['children'][$item->name] = $wrapper;
      }
      else {
        // Add the field to the form.
        foreach ($item->_build['children'] as $element_name => $field) {
          $this->form->_build['children']["{$item->name}-{$element_name}"] = $field;
        }
      }
    }
  }

  /**
   * Since the FORM is an extension of a service and not of an element, we need
   * to implement our own rendering method.
   */
  public function render() {
    $this->prepare();
    return $this->system->render($this->form);
  }
}

/**
 * Interface for Form objects.
 */
interface FormInterface {
  /**
   * Constructor
   *
   * @param $system
   *   A system object to handle the rendering and processing.
   * @param $settings
   *   An array of form settings for the action, method, name etc.
   */
  public function __construct($system, array $settings = array());

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
 * Create a form item to attach to a form.  A form item may be composed of
 * multiple FormElements such as labels and fields.
 */
class FormItem {
  public $type;
  public $name;
  public $value;
  public $default_value;
  public $label;
  public $description;
  public $placeholder;
  public $required;
  public $options;
  public $weight = 0;

  // Attributes
  public $attributes = array();
  public $label_attributes = array();
  public $description_attributes = array();
  public $wrapper_attributes = array();

  // Whether or not the form item has been checked for validity.
  protected $validated = FALSE;

  // Whether the field passed validation.
  private $is_valid;

  // Constructor
  public function __construct($type, $name) {
    $this->type = $type;
    $this->name = $name;
  }

  /**
   * Add an element to the FormItem
   */
  protected function addItem($name, $item) {
    $this->_build['children'][$name] = $item;
  }

  /**
   * Prepare the data to be rendered.
   */
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
      $label->weight = ($this->type == 'radio' || $this->type == 'checkbox' ? 3 : 0);
      $this->addItem('label', $label);
    }

    // Add the required indicator
    if ($this->required) {
      $req = new HtmlElement('');
      $req->_build['template'] = 'form/required';
      $req->content = '*';
      $req->addCssClass('required');
      $this->addItem('req', $req);
    }

    // Build the description
    if ($this->description) {
      $desc = new HtmlElement('div', $this->description_attributes);
      $desc->attributes['class'][] = 'desc';
      $desc->content = $this->description;
      $desc->weight = 5;
      $this->addItem('desc', $desc);
    }

    // Build the form element
    switch ($this->type) {
      case 'radio':
      case 'checkbox':
        break;

      case 'radios':
      case 'checkboxes':
        $class = "Perseus\Services\FormElement" . ucwords($this->type);
        $field = (class_exists($class) ? new $class($this->name, $this->attributes, $this->options) : new FormElement('input', $this->type, $this->name, $this->attributes));
        //-----
        break;

      case 'select':
        break;

      default:
        $class = "Perseus\Services\FormElement" . ucwords($this->type);
        $field = (class_exists($class) ? new $class($this->name, $this->attributes) : new FormElement('input', $this->type, $this->name, $this->attributes));
        $field->value = (is_null($this->value) ? $this->default_value : $this->value);
        break;
    }
    $field->weight = 1;

    // Add an error class if we did not validate.
    if (!$this->isValid()) {
      $field->addCssClass('error');
    }

    $this->addItem('field', $field);
  }

  /**
   * Checks to see if the item is valid.
   */
  public function isValid() {
    return ($this->validated && $this->is_valid !== FALSE);
  }

  /**
   * Generic validator for the form item.  May be extended into your own form.
   */
  public function validate() {
    $this->validated = TRUE;
  }

  /**
   * Generic submittor.  May be extended into your own form.
   */
  public function submit() {
    // Let the form handle the submitting.  Extend this method into your own
    // form items for item specific submit handling.
  }

  /**
   * Report a validation error on the form item.
   */
  public function setError($msg) {
    System::setMessage($msg, SYSTEM_ERROR);
    $this->is_valid = FALSE;
  }
}

/**
 * Extend HTML Element to manage form elements.
 */
class FormElement extends Perseus\HtmlElement {
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
 * Radio Field
 */
class FormElementRadio extends FormElement {
  // Constructor
  public function __construct($name, $attributes = array()) {
    parent::__construct('input', 'radio', $name, $attributes);
  }

  /**
   * Prepare the data
   */
  public function prepare() {
    // Pass it on to the parent classes.
    parent::prepare();
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

    $this->attributes['value'] = $this->value;

    // Pass it on to the parent classes.
    parent::prepare();
  }
}

/**
 * Submit Field
 */
class FormElementSubmit extends FormElement {
  public $value;

  // Constructor
  public function __construct($name, $attributes = array()) {
    parent::__construct('input', 'submit', $name, $attributes);
  }

  /**
   * Prepare the data
   */
  public function prepare() {
    $this->attributes['value'] = $this->value;
    parent::prepare();
  }
}

