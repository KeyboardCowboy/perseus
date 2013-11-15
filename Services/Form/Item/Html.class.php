<?php
namespace Perseus\Services\Form\Item;
use Perseus\Services\Form;

/**
 * Form item descriptions.
 */
class Html extends Form\Item {
  public $attributes = array();

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name, $settings);

    $this->type = 'html';
    $this->addTemplate('form/item/html');

  }

  // Prepare the data.
  public function prepare() {
    parent::prepare();
    $this->addBuildData('html', $this->html);
  }
}
