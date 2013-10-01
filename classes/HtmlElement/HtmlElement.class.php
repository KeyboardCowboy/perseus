<?php
namespace Perseus;

/**
 * Define an HTML element to be rendered.
 */
class HtmlElement {
  // System object used to handle rendering.
  public $system;
  private $self_closing = FALSE;

  public $type;
  public $attributes = array();
  public $value = '';

  // Constructor
  public function __construct($type, $attributes = array(), $value = '') {
    $this->type = $type;
    $this->attributes = (array) $attributes;
    $this->value = $value;

    $this->setClosing();
  }

  /**
   * Set the attributes.
   *
   * @todo: Implement attribute filtering and protect the property.
   */
  public function setAttributes() {

  }

  /**
   * Set the closing type for this type of element.
   */
  private function setClosing() {
    $this->self_closing = (in_array($this->type, array('area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr')));
  }

  /**
   * Render the element.
   */
  public function render() {
    $data = array(
      'type' => $this->type,
      'attributes' => $this->attributes,
      'value' => $this->value,
      'self_closing' => $this->self_closing,
    );

    return $this->system->theme('element', $data);
  }
}
