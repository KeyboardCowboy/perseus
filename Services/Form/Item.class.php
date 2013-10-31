<?php
/**
 * Form Item Renderables
 */
namespace Perseus\Services\Form;

use Perseus\System as Perseus;

/**
 * Form Item Interface.
 */
interface FormItemInterface {
  /**
   * Constructor.
   *
   * @param $name
   *   The name attribute for the field.
   * @param $settings
   *   Other values to define the item.
   */
  public function __construct($name, $settings = array());

  /**
   * Validate submitted data for the form item.  Required fields are checked at
   * the base level.
   *
   * May call Item::setError() or Item::setValid()
   */
  public function validate();
}

/**
 * Form items are specific types of Renderables.  They add many default settings
 * and are extended into more specific form items such as Text Fields.
 */
abstract class Item extends Perseus\Renderable implements FormItemInterface {
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
  // NULL: No check needed (No post data), FALSE: Not checked, TRUE: Checked
  public $validated;

  // Whether the field passed validation.
  private $is_valid;

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct('form/item');

    $this->name = $name;
    foreach ($settings as $k => $v) {
      $this->{$k} = $v;
    }
  }

  /**
   * Extends Renderable::addBuildData().
   */
  /*public function addBuildData($key, $data, $append = FALSE) {
    if ($this->type == 'wrapper') {
      $field = $this->getChild('field');
      pd($field);
      $field->addBuildData($key, $data, $append);
      pd($field);
      //$this->addChild('field', $field);
    }
    else {
      parent::addBuildData($key, $data, $append);
    }
  }*/

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
      $label = new Item\Label($this->label, $this->name, $this->required);
      $this->addChild('label', $label);
    }

    // Build the description
    if ($this->description) {
      $desc = new Item\Description($this->description);
      $this->addChild('description', $desc);
    }
  }

  /**
   * Finalize the object before rendering.
   *
   * - Check for a wrapper
   */
  final public function finalize() {
    // Build the wrapper
    if ($this->wrap) {
      // Clone the item to add it as a child to the wrapper.
      $current_item = clone($this);

      // Prevent recursive wrapping
      $current_item->wrap = FALSE;
      $this->wrap = FALSE;

      // $this now becomes the wrapper
      $this->addTemplate('form/item/wrapper');
      $this->attributes = array(
        'class' => array('form-item', "item-name-{$this->name}", "{$this->type}"),
      );
      $this->addBuildData('attributes', $this->attributes);
      $this->name .= '-wrapper';
      $this->type = 'wrapper';

      // Remove all child content from the wrapper that used to belong to the
      // field item.
      $this->_children = array();

      // Add the original $this item as a child to the wrapper
      $this->addChild('field', $current_item);
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
    return (is_null($this->validated) || ($this->validated === TRUE && $this->is_valid === TRUE));
  }

  /**
   * Generic validator for the form item.  May be extended into your own form.
   * Posted value is available in $this->submitted_value;
   */
  public function validate() {
    if ($this->required && !$this->submitted_value) {
      $fieldname = ($this->label ? $this->label : $this->name);
      $this->setError("Field '{$fieldname}' is required.");
    }
    else {
      $this->setValid();
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
  protected function setError($msg) {
    // Don't post multiple error messages for the same field.
    if ($this->is_valid !== FALSE) {
      Perseus\System::setMessage($msg, SYSTEM_ERROR);
      $this->is_valid = FALSE;
    }
  }

  /**
   * Set the is_valid flag to true only if it has not already been set to false.
   */
  protected function setValid() {
    if ($this->is_valid !== FALSE) {
      $this->is_valid = TRUE;
    }
  }

  /**
   * Set the value of the item to the submitted value retrieved from the form
   * data.
   */
  abstract public function setSubmittedValue();
}
