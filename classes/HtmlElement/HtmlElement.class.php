<?php
namespace Perseus;

/**
 * Define an HTML element to be rendered.
 */
class HtmlElement implements HtmlElementInterface {
  // System object used to handle rendering.
  public $system;
  public $self_closing = FALSE;

  public $element;
  public $attributes = array();
  public $content = '';
  public $weight = 0;

  // The template to use for theming.
  public $_build = array(
    'template' => 'element',
    'children' => array(),
  );

  // Constructor
  public function __construct($element, $attributes = array(), $content = '') {
    $this->element = $element;
    $this->attributes = (array) $attributes;
    $this->content = $content;
  }

  /**
   * Set the closing type for this type of element.
   */
  private function setClosing() {
    $this->self_closing = (in_array($this->element, array('area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr')));
  }

  // Nest an item under this one.
  public function addChild($name, $item) {
    $this->_build['children'][$name] = $item;
  }

  // Helper to add a CSS Class
  public function addCssClass($class) {
    if (!isset($this->attributes['class'])) {
      $this->attributes['class'] = array();
    }
    $this->attributes['class'] += (array) $class;
  }

  /**
   * Prepare the data for rendering.
   */
  public function prepare() {
    $this->setClosing();

    // Flatten the children into the render data.
    foreach ($this->_build['children'] as $name => $child) {
      if (property_exists($this, $name)) {
        $this->child_{$name} = $child;
      }
      else {
        $this->{$name} = $child;
      }
    }
  }
}

/**
 * Interface for creating HTML Element classes.
 */
interface HtmlElementInterface {
  /**
   * Prepare the data for rendering.
   */
  function prepare();
}
