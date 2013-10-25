<?php
namespace Perseus;

/**
 * @file
 * Class to manage system variables and processes.
 */
/**
 * RegistrationSystemInstaller Class
 */
class RegistrationSystemInstaller extends SystemInstaller {

  /**
   * Constructor
   */
  public function __construct($system) {
    parent::__construct($system);

    // Register installation procedures
    $this->install[] = 'registration';

  }

  /**
   * Install/configure the necessary parts for the tool to function properly.
   */
  public function install($do = array()) {
    try {
      $do = (array) $do;

      if (empty($do)) {
        $do = $this->install;
      }

      // Delegate to the parent installer.
      parent::install($do);

      // Run each installation procedure that needs to be handled by this
      // installer.
      foreach ($do as $install) {
        switch ($install) {
          case 'registration':
            $this->createTable('registration');
            break;
        }
      }
    }
    catch(Exception $e){System::handleException($e);}
  }

  /**
   * Define the database schemas
   */
  public function schema($table) {
    $schema['registration'] = "CREATE TABLE IF NOT EXISTS registration (
      rid int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique registration ID.',
      name varchar(128) NOT NULL DEFAULT '' COMMENT 'First, Middle Initial and Last Name of the registrant.',
      affiliation varchar(128) NOT NULL DEFAULT '' COMMENT 'Affiliation of the registrant.',
      address varchar(128) NOT NULL DEFAULT '' COMMENT 'Registrant address.',
      city varchar(128) NOT NULL DEFAULT '' COMMENT 'Registrant city.',
      state char(2) NOT NULL DEFAULT '' COMMENT 'Registrant state.',
      zip varchar(10) NOT NULL DEFAULT '' COMMENT 'Registrant zip/postal code.',
      country char(2) NOT NULL DEFAULT '' COMMENT 'Registrant country.',
      phone varchar(20) NOT NULL DEFAULT '' COMMENT 'Registrant phone number.',
      fax varchar(20) NOT NULL DEFAULT '' COMMENT 'Registrant FAX number.',
      mail varchar(255) NOT NULL DEFAULT '' COMMENT 'Registrant email address.',
      meal tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Registrant requires vegetarian meal.',
      dietary_needs varchar(255) DEFAULT '' COMMENT 'Registrant dietary needs.',
      PRIMARY KEY (rid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Records registration submissions' AUTO_INCREMENT=1 ;";

    return (isset($schema[$table]) ? $schema[$table] : '');
  }
}

