<?php
/**
 * @file
 * Perseus settings file.
 *
 * 1. Copy this file to your /[yoursite]/settings/perseus.php
 * 2. Ensure it is readable only by your webserver.
 */
/**
 * Define system variables.
 */
$vars = array(
  'basepath' => '/',
  'krumo' => array(
    'enabled' => TRUE,
    'skin'    => 'orange',
  ),
);

/**
 * Configure databases.
 */
$db['default'] = array(
  'name' => 'perseus',
  'host' => 'localhost',
  'user' => '',
  'pass' => '',
);
