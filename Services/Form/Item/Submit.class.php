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
    $this->setAttribute('value', $this->value);
  }

  // Validate
  public function validate() {
    parent::validate();
  }

  // Overrides Item::setValue().
  // Leave it as is.  Don't change the value of the submit button.
  public function setValue() {}
}
