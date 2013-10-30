<?php
/**
 * @file
 * Define common form processing and HTML elements.
 */
namespace Perseus\Services;

use Perseus\System as Perseus;
use Perseus\System\System;
use Perseus\System\HtmlElement;

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
  public function addChild($key, FormItem $item) {
    // @TODO: Validate each field as it is added to the form.  The form will
    // pick up an submitted data on instantiaion. This way we don't have to rely
    // on the child classes to call the validation method.
    if ($this->data) {
      if (isset($this->data[$item->name])) {
        $item->posted_value = $this->data[$item->name];
      }
      $item->validate();
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
        // Cycle through field submittors.
        foreach ($this->items as $name => $item) {
          $item->submit();
        }
        break;
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
}

/**
 * Form Item Interface.
 */
interface FormItemInterface {
  /**
   * Constructor
   *
   * @param $type
   *   The type of field.
   * @param $name
   *   The name value for the field.
   */
  public function __construct($name, $type = 'text');

  /**
   * Validate submitted data for the form item.  Required fields are checked at
   * the base level.
   *
   * @return TRUE || FALSE
   */
  public function validate();
}

/**
 * Form items are specific types of Renderables.  They add many default settings
 * and are extended into more specific form items such as Text Fields.
 */
class FormItem extends Perseus\Renderable implements FormItemInterface {
  // Common properties
  public $type;
  public $name;
  public $value;
  public $default_value;
  public $posted_value;
  public $placeholder;
  public $options;
  public $attributes = array('class' => array());
  public $required = FALSE;
  public $wrap = FALSE;

  // Common sibling elements
  public $label;
  public $desc;

  // Whether or not the form item has been checked for validity.
  protected $validated = FALSE;

  // Whether the field passed validation.
  private $is_valid;

  // Constructor
  public function __construct($name, $type = 'text') {
    parent::_construct('form/item');

    $this->type = $type;
    $this->name = $name;
  }

  /**
   * Prepare the data to be rendered.
   */
  public function prepare() {
    $this->buildAttributes();

    $this->addBuildData('wrap', $this->wrap);
    $this->addBuildData('required', $this->required);
    $this->addBuildData('attributes', $this->attributes, TRUE);

    // Build the Label
    if ($this->label) {
      $label = new FormItemLabel($this->label, $this->name, $this->required);
      $this->addChild('label', $label);
    }

    // Build the description
    if ($this->description) {
      $desc = new FormItemDescription($this->description);
      $this->addChild('desc', $desc);
    }
  }

  /**
   * Add the common elements to the attributes array.
   */
  public function buildAttributes() {
    $this->attributes['name'] = $this->name;

    if ($this->placeholder) {
      $this->attributes['placeholder'] = $this->placeholder;
    }

    if (!$this->isValid()) {
      $this->attributes['class'][] = 'error';
    }
  }

  /**
   * Checks to see if the item is valid.
   */
  public function isValid() {
    return ($this->validated && $this->is_valid !== FALSE);
  }

  /**
   * Generic validator for the form item.  May be extended into your own form.
   * Posted value is available in $this->posted_value;
   */
  public function validate() {
    if ($this->required && !$item->posted_value) {
      $fieldname = ($this->label ? $this->label : $this->name);
      $this->setError("Field '{$fieldname}' is required.");
    }
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
 * Form item labels.
 */
class FormItemLabel extends Perseus\Renderable {
  public $text;
  public $for;
  public $required;

  // Constructor
  public function __construct($text, $for, $required) {
    parent::__construct('form/item-label');

    $this->text     = $text;
    $this->for      = $for;
    $this->required = $required;
  }

  // Prepare the data
  public function prepare() {
    $this->addBuildData('content', $this->text);
    $this->addBuildData('attributes', array('for' => $this->for), TRUE);
    $this->addBuildData('required', $this->required);
  }
}

/**
 * Form item descriptions.
 */
class FormItemDescription extends Perseus\Renderable {
  public $attributes = array();
  public $element = 'div';
  public $content;

  // Constructor
  public function __construct($content, $element = 'div', $attributes = array()) {
    parent::__constructor('form/item-description');

    $this->content = $content;
    $this->element = $element;
    $this->attributes = $attributes;

    if (!in_array('description', $this->attributes)) {
      $this->attributes[] = 'description';
    }
  }

  // Prepare the data.
  public function prepare() {
    $this->addBuildData('content', $this->content);
    $this->addBuildData('element', $this->element);
    $this->addBuildData('attributes', $this->attributes);
  }
}

/**
 * Text Field.
 */
class FormItemText extends FormItem {
  // Constructor
  public function __construct($name, $type = 'text') {
    parent::__construct($name, 'text');
    $this->addTemplate('form/item/text');
  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
  }

  // Validate the submitted data.
  public function validate() {
    return TRUE;
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

