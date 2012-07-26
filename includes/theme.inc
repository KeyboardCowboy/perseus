<?php
/**
 * @file
 * Manage standard content markup.
 */
/**
 * Callback function to initiate theming.
 */
function theme($theme, $vars = array()) {
  // Call processors.
  $processor_file = "/kctools/theme/processors/{$theme}.inc";
  if (file_exists($processor_file)) {
    theme_process_vars($processor_file, $vars);
  }

  // Look for a template.
  $template_file = "/kctools/theme/templates/{$theme}.tpl.php";
  if (file_exists(DOCROOT . $template_file)) {
    print theme_render_template($template_file, $vars);
    return;
  }

  // If no template, look for a function.
  System::fileInclude('theme/themes.inc');
  $func = "theme_{$theme}";
  if (function_exists($func)) {
    print $func($vars);
    return;
  }
}

/**
 * Render a system default template, which is essentially a PHP template.
 *
 * Borrowed from Drupal.
 * http://api.drupal.org/api/drupal/includes%21theme.inc/function/theme_render_template/7
 */
function theme_render_template($template_file, $variables) {
  extract($variables, EXTR_SKIP); // Extract the variables to a local namespace
  ob_start(); // Start output buffering
  include DOCROOT . $template_file; // Include the template file
  return ob_get_clean(); // End buffering and return its contents
}

/**
 * Process variables for a theme.
 *
 * Keep this in a separate function to isolate variable scope.
 */
function theme_process_vars($file, &$vars) {
  include(DOCROOT . $file);
}
