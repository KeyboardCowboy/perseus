<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Submit field.
 */
class Submit extends Form\Item {
  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);

    $this->type = 'submit';
    $this->addTemplate('form/item/submit');
  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
    $this->addBuildData('value', $this->value);
  }

  // Validate
  public function validate() {
    parent::validate();
  }

  public function setSubmittedValue() {}
}
