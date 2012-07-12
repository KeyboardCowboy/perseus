<?php
/**
 * @file
 * PHP and MySQL library bootstrapper.
 */
// Define site constants.
define('HOME', '/home2/skynotec');
define('WEBROOT', '/home2/skynotec/public_html');

// Bootstrap common utilities required by other bootstrap phases.
require_once(WEBROOT . '/includes/util.inc');

// Bootstrap MySQL.
require_once(WEBROOT . '/includes/mysql.inc');
