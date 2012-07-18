<?php
/**
 * @file
 * MySQL Database abstraction class.
 */
class MySQL {
  private $prefix = 'skynotec';
  private $database;

  /**
   * Constructor
   */
  public function MySQL($db_name) {
    $this->database = "{$this->prefix}_{$db_name}";

    try {
      $this->connect();
    }
    catch(Exception $e) {
      print $e->getMessage();
    }
  }

  /**
   * Connect to the database.
   */
  private function connect() {
    // Load the database connection creds.
    require_once(HOME . '/private/database.inc');

    // Make sure we have credentials for the database.
    if (!isset($db[$this->database])) {
      throw new Exception('Unable to connect to MySQL.  Missing credentials.');
    }

    $creds = $db[$this->database];

    // Attempt the connection.
    $this->conn = mysqli_connect($creds['host'], $creds['user'], $creds['pass'], $this->database);
    if ($err = mysqli_connect_error()) {
      throw new Exception("Error connecting to MySQL.  {$err}. " . mysqli_errno($this->conn));
    }
  }

  /**
   * Select data from the database.
   */
  public function select($table, $cols = array(), $conds = array()) {
    $query = "SELECT";
    $query .= (!is_array($cols) || empty($cols) ? ' *' : implode(',', $cols));
    $query .= " FROM " . check_plain($table);

    // Conditionals only handle ANDs at the moment.
    // @todo Integrate ORs as well.
    if (!empty($conds)) {
      // Sanitize the conditionals.
      array_walk($conds, 'mysqli_escape_string');
      $query .= ' WHERE ';
      $query .= implode(' AND ', $conds);
    }

    $result = mysqli_query($this->conn, $query);
    if (mysqli_error($this->conn)) {
      print mysqli_errno();
    }

    return $this->parseSqlResult($result);
  }

  /**
   * Insert data into the database.
   */
  public function insert($table, $data) {
    array_walk($data, 'mysqli_escape_string');
    $query = "INSERT INTO $table (" . implode(',', array_keys($data)) . ") VALUES ('" . implode("','", $data) . "')";
  
    $result = mysqli_query($this->conn, $query);
    if (mysqli_error($this->conn) {
      print mysqli_errno();
    }
    
    return mysqli_affected_rows();
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
