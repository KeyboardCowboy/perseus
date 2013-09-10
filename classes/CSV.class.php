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
  private $data = array();

  // Array of stored Indeces
  public $index = array();

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
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Return the headers.
   */
  public function getHeaders() {
    return $this->headers;
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

/**
 * Define a query object to retrive and parse specific data from a CSV
 * resource.
 */
class CSVQuery {
  // The CSV resource to query against.
  private $csv;

  // Fields
  private $fields = array();

  // Filters
  private $filters = array();

  // Sorts
  private $sorts = array();

  // The results of the query.
  private $results = array();

  /**
   * Constructor
   *
   * @param $res
   *   A CSV object resource.
   */
  public function __construct($res) {
    try {
      if ($res instanceof CSV) {
        $this->csv = $res;
      }
      else {
        throw new Exception('Invalid CSV reference.', SYSTEM_ERROR);
      }
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Add fields to the query.
   */
  public function addField($field) {
    if (is_array($field)) {
      foreach ($field as $_field) {
        $this->addField($_field);
      }
    }
    elseif (is_string($field)) {
      $this->fields[$field] = $field;
    }
  }

  /**
   * Add filters to the index.
   */
  public function addFilter($filter, $value, $index = NULL, $op = '=') {
    if ($index) {
      $this->filters["{$filter}|{$value}"] = array($filter, $this->csv->index[$index][$value], $op);
    }
    else {
      $this->filters["{$filter}|{$value}"] = array($filter, $value, $op);
    }
  }

  /**
   * Add a sorting parameter.
   */
  public function addSort($field, $order = 'ASC', $weight = 0) {
    $this->sorts[$field] = array(
      'weight' => $weight,
      'order' => $order,
    );
  }

  /**
   * Return the results of the query.
   */
  public function results() {
    return $this->results;
  }

  /**
   * Execute the query.
   */
  public function query() {
    $results = array();
    $headers = $this->csv->getHeaders();
    $data    = $this->csv->getData();

    try {
      // If no fields are specified, return all of them.
      if (empty($this->fields)) {
        $fields = $headers;
      }

      $filter_count = count($this->filters);

      // @todo: Implement different operators.
      foreach ($data as $rid => $row) {
        // Filter out rows
        $pass = 0;
        foreach ($this->filters as $filter) {
          if ($row[$filter[0]] == $filter[1]) {
            $pass++;
          }
        }

        // If the data matches both filters, add it to the results.
        if ($pass == $filter_count) {
          // Limit to the fields requested.
          $results[$rid] = array_intersect_key($row, array_flip($this->fields));
        }
      }

      // Sort the remaining data.
      if (!empty($this->sorts)) {
        // Weight the sorters
        foreach ($this->sorts as $field => $sort) {
          $weighted["{$sort['weight']}:{$field}"] = $sort;
        }
        ksort($weighted);

        // Prepare the sorting array
        foreach ($results as $rid => $row) {
          foreach ($weighted as $key => $sort) {
            list(,$field) = explode(':', $key);

            $s[$field][$rid] = $row[$field];
            $o[$field] = $sort['order'];
          }
        }

        // Merge the sorting array vars into an eval string
        $ss = array();
        foreach ($s as $field => $sorter) {
          $ss[] = '$s[\'' . $field . '\'], ' . ($o[$field] == SORT_DESC ? 'SORT_DESC' : 'SORT_ASC');
        }
        $sort_string = 'array_multisort(' . implode(', ', $ss) . ', $results);';

        // Execute the sorting.
        eval($sort_string);
      }

      // Store the results so we can retrieve them later.
      $this->results = $results;
    }
    catch(Exception $e) {System::handleException($e);}

    return $this->results;
  }
}

