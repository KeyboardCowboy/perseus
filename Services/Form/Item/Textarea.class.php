<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Textarea Field.
 */
class Textarea extends Form\Item {
  // Set some defaults
  public $rows = 5;

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);
    $this->addTemplate('form/item/textarea');
  }

  // Prepare the data.
  public function prepare() {
    $this->setAttribute('rows', $this->rows);
    $this->addBuildData('value', $this->content, TRUE);
    parent::prepare();
  }
}
