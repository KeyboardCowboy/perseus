<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Select Field.
 */
class Select extends Form\Item {
  public $options = array();

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);

    $this->type = 'select';
    $this->addTemplate('form/item/select');
  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
    $options = array();

    foreach ($this->options as $value => $label) {
      $options[$value] = array(
        'label' => $label,
        'attributes' => array('value' => $value),
      );

      if ($value == $this->submitted_value) {
        $options[$value]['attributes']['selected'] = 'selected';
      }
    }

    $this->addBuildData('options', $options);
  }

  // Validate the submitted data.
  public function validate() {
    parent::validate();
  }

  // Set the field value.
  public function setSubmittedValue() {}
}
