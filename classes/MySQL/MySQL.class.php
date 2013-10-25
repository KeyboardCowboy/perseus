<?php
namespace Perseus;

/**
 * @file
 * MySQL Database abstraction class.
 */
class MySQL extends Service {
  private $conn;

  /**
   * Constructor
   */
  public function __construct($system, array $settings = array()) {
    parent::__construct($system);

    $creds = array();

    // Do we have a dbname or creds?
    if (!empty($settings['creds'])) {
      // @todo: Validate creds.
      $creds = $settings['creds'];
    }
    elseif (!empty($settings['database'])) {
      $creds = $this->retrieveCreds($settings['database']);
    }
    else {
      $creds = $this->retrieveCreds('default');
    }

    try {
      // Make sure our creds are set.
      foreach (array('host', 'user', 'pass', 'name') as $field) {
        if (empty($creds[$field])) {
          throw new Exception('Invalid database credentials.  Unable to connect.', SYSTEM_ERROR);
        }
      }

      $this->connect($creds);
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Connect to the database.
   */
  private function connect($creds) {
    // Attempt the connection.
    // @todo - allow configurable port.
    $conn = mysqli_connect($creds['host'], $creds['user'], $creds['pass'], $creds['name'], 33066);
    if ($err = mysqli_connect_error()) {
      throw new Exception("Error connecting to MySQL.  {$err}. " . mysqli_errno($this->conn), SYSTEM_ERROR);
    }
    else {
      $this->conn = $conn;
    }
  }

  /**
   * Determine whether this connection has been established.
   */
  public function isConnected() {
    return (is_object($this->conn));
  }

  /**
   * Retrieve the credentials from the perseus settings file.
   */
  private function retrieveCreds($name = 'default') {
    // At this point we have already validated that the file exists.
    include($this->system->config_file);

    if (isset($db[$name])) {
      return $db[$name];
    }
    else {
      throw new Exception("Unable to connect to database {$name}.  Invalid credentials.", SYSTEM_ERROR);
    }
  }

  /**
   * Select data from the database.
   */
  public function select($table, $cols = array(), $conds = array()) {
    $query = "SELECT ";
    $query .= (!is_array($cols) || empty($cols) ? ' *' : implode(',', $cols));
    $query .= " FROM " . check_plain($table);

    // Conditionals only handle ANDs at the moment.
    // @todo Integrate ORs as well.
    if (!empty($conds)) {
      // Sanitize the conditionals.
      //array_walk($conds, 'mysqli_escape_string');
      $query .= ' WHERE ';
      $query .= implode(' AND ', $conds);
    }

    $result = mysqli_query($this->conn, $query);
    if (mysqli_error($this->conn)) {
      System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
      return array();
    }

    return $this->parseSqlResult($result);
  }

  /**
   * Insert data into the database.
   */
  public function insert($table, $data) {
    //array_walk($data, 'mysqli_escape_string');
    $query = "INSERT INTO $table (" . implode(',', array_keys($data)) . ") VALUES ('" . implode("','", $data) . "')";

    $result = mysqli_query($this->conn, $query);
    if (mysqli_error($this->conn)) {
     pd('FAILED');
     System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
    }

    return mysqli_affected_rows($this->conn);
  }

  /**
   * Generic SQL query
   */
  public function query($query) {
    try {
      $result = mysqli_query($this->conn, $query);
      if (mysqli_error($this->conn)) {
        System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
      }
    }
    catch(Exception $e) {System::handleException($e);}

    return mysqli_affected_rows($this->conn);
  }

  /**
   * Parse select results into a usable array.
   */
  public function parseSqlResult($res) {
    $ret = array();
    while ($resobj = mysqli_fetch_object($res)) {
      $ret[] = $resobj;
    }

    return $ret;
  }
}
