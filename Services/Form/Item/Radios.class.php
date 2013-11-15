<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Radios Field.
 */
class Radios extends Form\Item {
  public $options = array();
  protected $cols = 1;

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);

    $this->type = 'radios';
    $this->addTemplate('form/item/radios');
  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
    $radios = array();

    // Calculate grouping
    $groupcnt = (isset($this->cols) ? ceil(count($this->options) / $this->cols) : count($this->options));
    $g = 1;

    foreach ($this->options as $value => $label) {

      $radios[ceil($g/$groupcnt)][$value] = array(
        'label' => $label,
        'attributes' => array(
          'value' => $value,
          'name' => $this->name,
        ),
      );

      if ($value == $this->submitted_value) {
        $radios[ceil($g/$groupcnt)][$value]['attributes']['checked'] = 'checked';
      }

      $g++;
    }

    $this->addBuildData('radios', $radios);
  
  }

  // Validate the submitted data.
  public function validate() {
    parent::validate();
  }

  // Set the field value.
  public function setSubmittedValue() {
    $this->value = $this->submitted_value;
  }
}
