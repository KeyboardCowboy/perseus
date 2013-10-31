<?php
/**
 * @file
 * Base class to define a renderable item.
 */
namespace Perseus\System;

abstract class Renderable {
  protected $_templates = array();
  protected $_data      = array(
    //'item'   => array(),  // An array of individually rendered child elements.
    //'content' => '',       // The composite of all rendered child elements.
  );
  protected $_children  = array();

  // Generated on render
  private $_content = array();
  private $_key;
  public $rendered;

  // Common properties
  public $weight;
  public $attributes = array();

  /**
   * Constructor
   */
  public function __construct($template = '') {
    if ($template) {
      $this->addTemplate($template);
    }
  }

  /**
   * Templates
   */
  public function addTemplate($template) {
    if (!in_array($template, $this->_templates)) {
      $this->_templates[] = $template;
    }
  }

  public function removeTemplate($template) {
    $templates = array_flip($this->_templates);
    if (isset($templates[$template])) {
      unset($this->_templates[$templates[$template]]);
    }
  }

  public function getTemplates() {
    return $this->_templates;
  }

  /**
   * Build Data
   *
   * Used in template rendering.  Any data that is not another
   * renderable object goes in here.  Other renderable objects are added to
   * the children and will be recursively rendered with the result stored in
   * the 'content' value of the data array.
   */
  public function addBuildData($key, $data, $append = FALSE) {
    if ($append && isset($this->_data[$key])) {
      if (is_array($this->_data[$key])) {
        $this->_data[$key] += $data;
      }
      else {
        $this->_data[$key] .= $data;
      }
    }
    else {
      $this->_data[$key] = $data;
    }
  }

  public function getBuildData() {
    return $this->_data;
  }

  /**
   * Children
   *
   * Created nested renderable elements.  The resulting markup is stored in the
   * data array under 'content'.
   */
  public function addChild($key, Renderable $child) {
    $this->_children[$key] = $child;
  }

  public function removeChild($key) {
    unset($this->_children[$key]);
  }

  public function getChild($key) {
    return (isset($this->_children[$key]) ? $this->_children[$key] : NULL);
  }

  public function getChildren() {
    return $this->_children;
  }

  public function addContent($key, $content) {
    $this->addBuildData('item', array($key => $content), TRUE);
    $this->addBuildData('content', $content, TRUE);
  }

  /**
   * Helpers
   */
  public function setAttribute($name, $value) {
    if ($name == 'class') {
      if (!isset($this->attributes['class'])) {
        $this->attributes['class'] = array($value);
      }
      else {
        $this->attributes['class'][] = $value;
      }
    }
    else {
      $this->attributes[$name] = $value;
    }
  }

  /**
   * API & Abstracts
   */
  // This is called directly after prepare() and before rendering to allow
  // parent classes one last chance to alter the object before rendering.
  public function finalize() {}

  // Prepare the data for rendering.
  abstract public function prepare();
}
