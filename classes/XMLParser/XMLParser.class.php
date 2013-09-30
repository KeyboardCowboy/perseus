<?php
/**
 * @file
 * Wrapper class to process XML data using SimpleXMLElement.
 */
namespace Perseus;

class XMLParser {
  // The system instantiating the object.
  protected $system;

  // The SimpleXML object.
  protected $xml;

  /**
   * Constructor
   *
   * @param $root_element
   *   The top level element for the XML structure in the format '<root/>'.
   */
  public function __construct($system, array $settings = array()) {
    parent::__construct($system);

    $this->xml = new \SimpleXMLElement((isset($settings['root']) ? $settings['root'] : '<data/>'));
  }

  /**
   * Generate an XML file from an array.
   */
  public function createFile($filename) {
    $saved = NULL;

    try {
      $saved = $this->xml->asXML($filename);
    }
    catch(Exception $e) {System::handleException($e);}

    return $saved;
  }
}
