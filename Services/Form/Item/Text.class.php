<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Text Field.
 */
class Text extends Form\Item {
  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);

    $this->type = 'text';
    $this->addTemplate('form/item/text');
  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
    $this->addBuildData('attributes', array('value' => $this->value), TRUE);
  }

  // Validate the submitted data.
  public function validate() {
    parent::validate();
  }

  // Set the field value.
  public function setSubmittedValue() {
    $this->value = trim($this->submitted_value);
  }
}
