<?php
/**
 * Form Item Renderables
 */
namespace Perseus\Services\Form;

use Perseus\System;

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
class FormItem extends System\Renderable implements FormItemInterface {
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
class FormItemLabel extends System\Renderable {
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
class FormItemDescription extends System\Renderable {
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
class FormItemText extends System\FormItem {
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
class FormElement extends System\HtmlElement {
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
class FormElementRadio extends System\FormElement {
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
class FormElementText extends System\FormElement {
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
class FormElementSubmit extends System\FormElement {
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

