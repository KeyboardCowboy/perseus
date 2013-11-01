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

  // Extends Renderable::prepare().
  public function prepare() {
    parent::prepare();
    $this->setAttribute('value', $this->value);
  }

  // Extends Item::validate().
  public function validate() {
    parent::validate();
  }

  // Overrides Item::setValue().
  public function setValue() {
    $this->value = $this->default_value;
  }
}
