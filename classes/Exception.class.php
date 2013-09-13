<?php
class Perseus_Exception extends Exception {
  public function __construct($message, $code) {
    parent::__construct($message, $code);
  }
}
