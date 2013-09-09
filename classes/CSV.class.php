<?php
namespace Perseus;

/**
 * @file
 * Handle loading, extracting and maybe creating csv file data.
 */
class CSV {
  // The location of the csv file.
  private $filepath;

  // The column headers.
  private $headers;

  // The data from the CSV file parsed into an array.
  private $data;

  // Array of stored Indeces
  private $index = array();

  // Constructor
  public function __construct($filepath) {
    $this->filepath = $filepath;

    // Read the data out of the file.
    $this->read();

    // Create an associative index for the headers.
  }

  /**
   * Extract the data from the CSV file.
   */
  private function read() {
    try {
      // Make sure we have a file
      if (!file_exists($this->filepath)) {
        throw new Exception('Unable to locate csv data file.', SYSTEM_ERROR);
      }

      // Read the file
      $data = array();
      if (($handle = fopen($this->filepath, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
          $data[] = $row;
        }
        fclose($handle);

        // Separate the headers
        $this->headers = array_shift($data);

        // Format the data with header keys
        foreach ($data as $rid => $d) {
          foreach ($d as $hid => $val) {
            $this->data[$rid][$this->headers[$hid]] = $val;
          }
        }
      }
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Return all data.
   *
   * @param $fields
   *   Specify fields to return.
   * @param $filters
   *   Array of filter definitions.
   *   Ex. array('field', 'value', 'operator (defaults to == )')
   */
  public function getData($fields = array(), $filters = array()) {
    $return = array();
    $header_index = array_flip($this->headers);

    // If no fields are specified, return all of them.
    if (empty($fields)) {
      $fields = $this->headers;
    }

    $filter_count = count($filters);

    // @todo: Implement different operators.
    foreach ($this->data as $rid => $row) {
      // Filter out rows
      $pass = 0;
      foreach ($filters as $filter) {
        if ($row[$filter[0]] == $filter[1]) {
          $pass++;
        }
      }

      // If the data matches both filters, add it to the results.
      if ($pass == $filter_count) {
        // Limit to the fields requested.
        $return[$rid] = array_intersect_key($row, array_flip($fields));
      }
    }

    return $return;
  }

  /**
   * Get all values from a given column.
   *
   * @param $col
   *   The name of the column to retrieve.
   * @param $distinct
   *   Return only distinct values.
   * @param $index
   *   Create a unique index for each item.
   */
  public function getColumn($col, $distinct = FALSE, $index = FALSE) {
    $ret = array();

    try {
      if (!in_array($col, $this->headers)) {
        throw new Exception("Undefined column {$col}.", SYSTEM_WARNING);
      }

      // Cycle through the data to pull out the values from this col.
      foreach ($this->data as $ri => $row) {
        if (!empty($row[$col]) && (!$distinct || !in_array($row[$col], $ret))) {
          $ret[] = $row[$col];
        }
      }
    }
    catch(Exception $e) {System::handleException($e);}

    // Create an index if requested
    if ($index) {
      $this->index[$col] = $this->indexValues($ret);
      return $this->index[$col];
    }
    else {
      return $ret;
    }
  }

  /**
   * Generate unique identifiers so values can be used as parameters and in urls
   * and forms and be easily referenced for comparison later.
   *
   * @param $value
   *  String or array of values.
   *
   * @return
   *   Array of index-to-value pairs.
   */
  public function indexValues($value) {
    $new_value = array();

    if (is_string($value)) {
      $new_value = unique_id($value);
    }
    elseif (is_array($value)) {
      foreach ($value as $k => $v) {
        if (is_string($v)) {
          $new_value[unique_id($v)] = $v;
        }
        else {
          $new_value[$k] = $this->indexValues($v);
        }
      }
    }
    else {
      $new_value[] = $value;
    }

    return $new_value;
  }

  /**
   * Retrieve an index.
   */
  public function getIndex($name) {
    return (isset($this->index[$name]) ? $this->index[$name] : array());
  }
}

