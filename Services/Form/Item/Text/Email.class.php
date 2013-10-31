<?php
namespace Perseus\Services\Form\Item\Text;
use Perseus\Services\Form;
use Perseus\Services\PhpMail;

/**
 * Text Field.
 */
class Email extends Form\Item\Text {
  // Set some defaults
  public $label = 'E-Mail Address';

  // Constructor
  public function __construct($name, $settings = array()) {
    parent::__construct($name);
  }

  // Validate the submitted data.
  public function validate() {
    parent::validate();

    if (!PhpMail::emailIsValid($this->submitted_value)) {
      $this->setError('Invalid e-mail address.');
    }
  }
}
