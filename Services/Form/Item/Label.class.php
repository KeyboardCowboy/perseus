<?php
namespace Perseus\Services\Form\Item;
use Perseus\System as Perseus;

/**
 * Form item labels.
 */
class Label extends Perseus\Renderable {
  public $text;
  public $for;
  public $required;

  // Constructor
  public function __construct($text, $for, $required) {
    parent::__construct('form/item/label');

    $this->text     = $text;
    $this->for      = $for;
    $this->required = $required;
  }

  // Prepare the data
  public function prepare() {
    $this->addBuildData('content', $this->text);
    $this->addBuildData('attributes', array('for' => $this->for), TRUE);
    $this->addBuildData('required', $this->required);
  }
}
