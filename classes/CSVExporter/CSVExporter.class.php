<?php
namespace Perseus;

/**
 * @file
 * Handle exporting of file data to CSV.
 */

/**
 * CSVExporter Class
 *
 * CSV export.
 */
class CSVExporter extends Service {

  // Constructor
  public function __construct($system, array $settings = array()) {
    $settings['delimiter'] = ',';
    parent::__construct($system, $settings);
    //$this->setHeaders();
  }

  public function export($rows, $filename = "export.csv", $delimiter=",") {
    // open raw memory as file so no temp files needed, you might run out of memory though
    $f = fopen('php://memory', 'w');
    // loop over the input array
    foreach ($rows as $row) {
      // generate csv lines from the inner arrays
      fputcsv($f, $row, $delimiter);
    }
    // rewrind the "file" with the csv lines
    fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: application/csv; charset=UTF-8;');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachement; filename="'.$filename.'"');
    // make php send the generated csv lines to the browser
    fpassthru($f);
  }
}
