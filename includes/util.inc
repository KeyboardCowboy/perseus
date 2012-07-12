<?php
/**
 * @file
 * Basic utility functions.
 */

/**
 * Encodes special characters in a plain-text string for display as HTML.
 * Also validates strings as UTF-8 to prevent cross site scripting attacks on
 * Internet Explorer 6.
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Cleanly dump a variable to the screen.
 */
function dump($var) {
  print '<pre>' . print_r($var, 1) . '</pre>';
}
