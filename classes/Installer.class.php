<?php
/**
 * @file
 * Perseus installer.
 *
 * - Creates database tables.
 */
/**
 * Define the installer class.
 */
class Perseus_Installer {
  private $system;

  /**
   * Constructor
   *
   * @param $system
   *   A system object with which to interact with the database.
   */
  public function __construct($system) {
    $this->system = $system;
  }

  /**
   * Install a database table.
   */
  protected function createTable($table) {
    // Make sure we have a SQL connection
    $db = $this->system->db();

    // Get the schema
    if (is_object($db) && ($schema = $this->schema($table))) {
      $db->query($schema);
    }
  }
}

/**
 * Define the installer interface.
 */
interface InstallerInterface {
  /**
   * Constructor
   */
  public function __construct($system);

  /**
   * Run the installation commands.
   */
  public function install();

  /**
   * Define any database schemas.
   */
  public function schema($name);
}
