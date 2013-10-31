<?php
namespace Perseus\Services\Form\Item;
use Perseus\System as Perseus;

/**
 * Form item descriptions.
 */
class Description extends Perseus\Renderable {
  public $attributes = array();
  public $element = 'div';
  public $content;

  // Constructor
  public function __construct($content, $element = 'div', $attributes = array()) {
    parent::__construct('form/item/description');

    $this->content = $content;
    $this->element = $element;
    $this->attributes = $attributes;

    $this->attributes['class'][] = 'description';
  }

  // Prepare the data.
  public function prepare() {
    $this->addBuildData('content', $this->content);
    $this->addBuildData('element', $this->element);
    $this->addBuildData('attributes', $this->attributes);
  }
}
