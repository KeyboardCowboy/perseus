<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Select Field.
 */
class Select extends Form\Item {
  public $options = array();
  public $empty_text = '-- Choose One --';

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);

    $this->type = 'select';
    $this->addTemplate('form/item/select');
  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
    $this->buildOptions();
  }

  // Validate the submitted data.
  public function validate() {
    parent::validate();

    // Compare against allowed values.
    if (!isset($this->options[$this->submitted_value])) {
      $name = $this->displayName();
      $this->setError("Invalid option selected in '{$name}' field.");
    }
  }

  // Build the options array and set the selected item here if necessary.
  public function buildOptions() {
    $options = array();

    if (!$this->required) {
      $this->options = array_merge(array('' => $this->empty_text), $this->options);
    }

    foreach ($this->options as $value => $label) {
      $options[$value] = array(
        'label' => $label,
        'attributes' => array('value' => $value),
      );

      if ($value == $this->value) {
        $options[$value]['attributes']['selected'] = 'selected';
      }
    }

    $this->addBuildData('options', $options);
  }
}
