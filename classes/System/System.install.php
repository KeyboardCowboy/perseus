<?php
/**
 * @file
 * Perseus installer.
 *
 * - Creates database tables.
 */
namespace Perseus;

class Installer {
  public function __construct() {

  }

  /**
   * Run the installer for given tools.
   */
  public function install($tools = array()) {
    if (empty($tools)) {
      // @todo: Create complete installer.
    }
    else {

    }
  }
}
