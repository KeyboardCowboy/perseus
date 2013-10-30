<?php
/*
 * @file
 * Modified from Twig Debug
 */
namespace Perseus\System;

class Debug_TwigKrumoExtension extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === ini_get('html_errors') || ini_get('html_errors'))
            || 'cli' === php_sapi_name()
        ;

        return array(
            new \Twig_SimpleFunction('kvars', array($this, 'twig_krumo_var_dump'), array('is_safe' => $isDumpOutputHtmlSafe ? array('html') : array(), 'needs_context' => true, 'needs_environment' => true)),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'kvars';
    }

    public function twig_krumo_var_dump(\Twig_Environment $env, $context) {
      if (!$env->isDebug()) {
          return;
      }

      ob_start();

      $count = func_num_args();
      if (2 === $count) {
          $vars = array();
          foreach ($context as $key => $value) {
              if (!$value instanceof Twig_Template) {
                  $vars[$key] = $value;
              }
          }

          krumo($vars);
      } else {
          for ($i = 2; $i < $count; $i++) {
              krumo(func_get_arg($i));
          }
      }

      return ob_get_clean();
  }
}
