<?php
namespace Perseus\Services;
use Perseus\System\Exception;
use Perseus\System\Service;
use Perseus\System\System;

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
      foreach (array('host', 'user', 'pass', 'name', 'port') as $field) {
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
    $conn = mysqli_connect($creds['host'], $creds['user'], $creds['pass'], $creds['name'], $creds['port']);
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
   *
   * @param string $table
   *   The table to select from.
   * @param $cols array
   *   An array of string column names to select. If an array is not passed or an empty
   *   array is passed, then all columns will be selected.
   * @param $conds array
   *   An array of conditions. Each condition is itself an array containing 3
   *   elements:
   *     $field string
   *       The name of the field
   *     $condition string
   *       The condition
   *     $value mixed
   *       The value in the condition
   *
   *   Example: array(
   *              array(
   *                'field' => 'name',
   *                'condition' => '=',
   *                'value' => 'Charlie',
   *              ),
   *              array(
   *                'field' => 'timestamp',
   *                'condition' => '<',
   *                'value' => 1384358291,
   *              ),
   *            )
   * @return array
   *   An array of objects, where each object contains the value of the
   *   requested fields, keyed by the name of requested fields.
   */
  public function select($table, $cols = array(), $conds = array()) {
    $query = "SELECT ";
    $query .= (!is_array($cols) || empty($cols) ? ' *' : implode(',', $cols));
    $query .= " FROM " . check_plain($table);

    // Form the where clause with placeholders and a corresponding array of
    // paramenters. Conditionals only handle ANDs at the moment.
    // @todo Integrate ORs as well.
    $where_clause = '';
    $params = array();
    if (count($conds)) {
      foreach ($conds as $cond) {
        $placeholders[] = $cond['field'] . ' ' . $cond['condition'] . ' ?';
        $params[] = $cond['value'];
      }
      $where_clause .= ' WHERE ' . implode(' AND ', $placeholders);
    }

    $query .= $where_clause;

    $stmt = $this->query_init($query, $params);

    if ($stmt) {
      $results = $this->fetch_all($stmt);
      mysqli_stmt_close($stmt);
    }

    if (mysqli_error($this->conn)) {
      System::setMessage('MySQL error[' . mysqli_errno($this->conn) . ']: ' . mysqli_error($this->conn));
      return array();
    }

    return $results;
  }

  /**
   * Initializes an SQL query.
   *
   * @param string $sql - the query string with question marks as parameter
   * placeholders.
   * @param array $params - an array of parameters, the number and order
   * matching the placeholders.
   * @return mysqli_stsmt (or bool) $stmt - returns FALSE on failure.
   *  For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query()
   *  will return a mysqli_result object. For other successful queries
   *  mysqli_query() will return TRUE.
   *
   * @see http://php.net/manual/en/mysqli.query.php
   */
  private function query_init($sql, $params) {

    // If no connection has been established, return FALSE.
    if (!($this->isConnected())) {
      return FALSE;
    }

    // Check that the number of parameters matches the number of placeholders.
    // create a prepared statement, bind parameters, execute and bind results
    $status = FALSE;
    if (substr_count($sql, '?') == count($params)) {
      if ($stmt = mysqli_prepare($this->conn, $sql)) {

        $queryParams = array('');

        if (TRUE === is_array($params)) {
          // Create the empty 0 index which will be filled with marker type
          // characters - @see determineType().
          $queryParams = array('');
          foreach ($params as $prop => $val) {
            $queryParams[0] .= $this->determineType($val);
            array_push($queryParams, $params[$prop]);
          }
          $refValues = $this->refValues($queryParams);
          call_user_func_array(array($stmt,'bind_param'), $refValues);
        }
        // Execute query.
        $status = mysqli_stmt_execute($stmt);

      }
    }

    if (!$status) {
      $params_str = '';
      if (!empty($params)) {
        $params_str = implode(", ", $params);
      }
      System::setMessage('Query Failed');
      return FALSE;
    }

    return $stmt;

  }

  /**
   * This method is needed for prepared statements. They require
   * the data type of the field to be bound with "i" s", etc.
   * This function takes the input, determines what type it is,
   * and then updates the param_type.
   *
   * @param mixed $item Input to determine the type.
   *
   * @return string The joined parameter types.
   */
  private function determineType($item) {

    switch (gettype($item)) {
      case 'NULL':
      case 'string':
        return 's';
        break;

      case 'integer':
        return 'i';
        break;

      case 'blob':
        return 'b';
        break;

      case 'double':
        return 'd';
        break;
    }
    return '';
  }

  /**
   * Change the values in an array to be references to those values.
   *
   * @param array $arr
   *
   * @return array
  */
  private function refValues(&$arr) {
    //Reference is required for PHP 5.3+
    if (strnatcmp(phpversion(), '5.3') >= 0) {
      foreach ($arr as $key => $value) {
          $arr[$key] = & $arr[$key];
      }
    }
    return $arr;
  }

  /**
   * Returns all query results
   * @param mysqli_stmt $stmt
   * @return array - all results
   */
  private function fetch_all($stmt){

    // Get resultset for metadata and retrieve field information from metadata
    // result set.
    $results_meta = mysqli_stmt_result_metadata($stmt);
    $fields = mysqli_fetch_fields($results_meta);

    $data = array();
    // Array of fields passed to the bind_result method.
    $params = array();
    // Returns a copy of a value.
    $copy = create_function('$a', 'return $a;');
    while ($field = $results_meta->fetch_field()) {
      $params[$field->name] = &$data[$field->name]; // pass by reference
    }
    call_user_func_array(array($stmt, 'bind_result'), $params);
    // Fetch values - @see http://www.php.net/manual/en/mysqli-stmt.bind-result.php#92505
    // for an explanation of the use of array_map and copy function.
    $results = array();
    while (mysqli_stmt_fetch($stmt)) {
      $results[] = array_map($copy, $params);
    }
    return $results;
  }

  /**
   * Insert data into the database.
   */
  public function insert($table, $data) {
    //array_walk($data, 'mysqli_escape_string');
    $num_fields = count($data);
    $index = 1;
    $placeholders = '';
    while ($index++ < $num_fields) {
      $placeholders .= '?, ';
    }
    $placeholders .= '?';
    $query = "INSERT INTO $table (" . implode(',', array_keys($data)) . ") VALUES (" . $placeholders . ")";

    $stmt = $this->query_init($query, array_values($data));
    $results = array();

    if ($stmt) {
      $results[]['id'] = @mysqli_stmt_insert_id($stmt);
      mysqli_stmt_close($stmt);
    }

    return $results;
  }

  /**
   * Generic SQL query.
   * INSERT and SELECT queries should used insert() and select(), which use
   * prepared statements.
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
