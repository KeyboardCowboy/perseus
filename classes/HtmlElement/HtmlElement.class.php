<?php
namespace Perseus;

/**
 * Define an HTML element to be rendered.
 */
class HtmlElement implements HtmlElementInterface {
  // System object used to handle rendering.
  public $system;
  private $self_closing = FALSE;

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

  /**
   * Prepare the data for rendering.
   */
  public function prepare() {
    $this->self_closing = $this->setClosing();
    $this->_rendered = array();
  }

  /**
   * Render the element.
   */
  public function render() {
    // Prepare the data for rendering.
    $this->prepare();

    return $this->system->theme($this->_build->template, $this);
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

  /**
   * Render the element.
   *
   * @return $markup
   *   The HTML markup of the rendered element.
   */
  function render();
}
