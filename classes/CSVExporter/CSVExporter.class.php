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
  }

  /*
   * Export the data.
   *
   * Open a file in memory, put the data into that file, output the headers to
   * have the browser open the save as dialog and then output the file in memory
   * to the browser.
   *
   * @param
   *  $rows - an array of the data to be output
   * @param
   *  $filename - string -  the name of the file to be output
   * @param
   *  $delimter - char - the character to be used as the delimiter
   *
   */
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
