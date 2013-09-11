<?php
/**
 * @file
 * Wrapper class to process XML data using SimpleXMLElement.
 */
namespace Perseus;

class XMLParser {
  private $root = '<root/>';

  /**
   * Constructor
   *
   * @param $root_element
   *   The top level element for the XML structure in the format '<root/>'.
   */
  public function __construct($root = NULL) {
    if ($root) {
      $this->root = $root;
    }
  }

  /**
   * Generate an XML file from an array.
   */
  public function createFile($data, $filename) {
    $saved = NULL;

    try {
      $xml = new \SimpleXMLElement($this->root);
      array_walk_recursive($data, array($xml, 'addChild'));
      $filedata = $xml->asXML();

      $saved = file_put_contents($filename, $filedata);
    }
    catch(Exception $e) {System::handleException($e);}

    return $saved;
  }
}
