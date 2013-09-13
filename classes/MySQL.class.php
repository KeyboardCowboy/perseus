<?php
/**
 * @file
 * MySQL Database abstraction class.
 */
class Perseus_MySQL {
  private $conn;

  /**
   * Constructor
   */
  public function __construct($creds) {
    try {
      $this->connect($creds);
    }
    catch(Exception $e) {Perseus_System::handleException($e);}
  }

  /**
   * Connect to the database.
   */
  private function connect($creds) {
    // Attempt the connection.
    $conn = mysqli_connect($creds['host'], $creds['user'], $creds['pass'], $creds['name']);
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
      Perseus_System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
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
      Perseus_System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
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
        Perseus_System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
      }
    }
    catch(Exception $e) {Perseus_System::handleException($e);}

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
