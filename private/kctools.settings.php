<?php
/**
 * @file
 * Settings file for the KC Tools library.  The 'Private' directory should not
 * be readable.
 */
/**
 * REQUIRED
 * Define system variables.
 */
$vars = array(
  'base_path' => '/',
  'site_mail' => 'daveykopas@gmail.com',
  'site_name' => 'SkyNote',
);

/**
 * Configure databases.
 */
$db['skynotec_logs'] = array(
  'host' => 'localhost',
  'user' => 'skynotec_db',
  'pass' => '5uBiTu-2VRL9',
);

/**
 * Configure PHP runtime settings.
 */
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
