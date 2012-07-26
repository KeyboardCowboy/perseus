<?php
/**
 * @file
 * Theme functions.
 */
/**
 * Render a list element.
 */
function theme_list($vars) {
  $vars += array(
    'items' => array(),
    'type' => 'ul',
  );

  $out = '<' . $vars['type'] . '>';
  foreach ($vars['items'] as $item) {
    if (is_array($item)) {
      $out .= theme_list(array('items' => $item, 'type' => $vars['type']));
    }
    elseif (is_string($item)) {
      $out .= '<li>' . filter_xss($item) . '</li>';
    }
  }
  $out .= '</' . $vars['type'] . '>';

  return $out;
}
