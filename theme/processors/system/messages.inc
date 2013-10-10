<?php
/**
 * @file
 * Prepare messages.
 */
use Perseus\System\System;

// Translate the error codes to something readable.
foreach (System::getMessages() as $type => $group) {
  $vars['messages'][System::errorCodes($type)] = $group;
}
