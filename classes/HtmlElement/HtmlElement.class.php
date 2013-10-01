<?php
namespace Perseus;

/**
 * Define an HTML element to be rendered.
 */
class HtmlElement implements HtmlElementInterface {
  // System object used to handle rendering.
  public $system;
  private $self_closing = FALSE;

  public $type;
  public $attributes = array();
  public $content = '';

  // The template to use for theming.
  public $template;

  // Constructor
  public function __construct($type, $attributes = array(), $content = '') {
    $this->type = $type;
    $this->attributes = (array) $attributes;
    $this->value = $value;
  }

  /**
   * Set the closing type for this type of element.
   */
  private function setClosing() {
    $this->self_closing = (in_array($this->type, array('area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr')));
  }

  /**
   * Prepare the data for rendering.
   */
  protected function prepare() {
    $this->render_data = array(
      'type' => $this->type,
      'attributes' => $this->attributes,
      'content' => $this->content,
      'self_closing' => $this->setClosing(),
    );
  }

  /**
   * Render the element.
   */
  public function render() {
    // Prepare the data for rendering.
    $this->prepare();

    return $this->system->theme($this->template, $this->render_data);
  }
}

/**
 * Interface for creating HTML Element classes.
 */
class HtmlElementInterface {
  /**
   * Constructor
   *
   * @param $type
   *   The type of element to render.
   * @param $attributes
   *   An associative array of element attributes.
   * @param $content
   *   The content to place between the tags.
   */
  public function __construct($type, $attributes = array(), $content = ''){}

  /**
   * Prepare the data for rendering.
   */
  protected function prepare() {}

  /**
   * Render the element.
   *
   * @return $markup
   *   The HTML markup of the rendered element.
   */
  public function render() {}
}
