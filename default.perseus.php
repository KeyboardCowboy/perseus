<?php
/**
 * @file
 * Perseus settings file.
 *
 * 1. Copy this file to your /[yoursite]/settings/perseus.php
 * 2. Ensure it is readable only by your webserver.
 */
/**
 * Define system variables.  These variables are required for the system to
 * operate.  DO NOT delete or rename them.
 */
$vars = array(
  'basepath' => '/',
  'krumo' => array(
    'enabled' => TRUE,
    'skin'    => 'orange',
  ),
  'twig' => array(
    'cache' => FALSE, //PROOT . '/theme/cache'
    'autoescape' => FALSE,
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
