<?php
namespace Perseus;

/**
 * @file
 * PHP Mailing class.
 */

define('MAIL_FORMAT_PLAIN', 0);
define('MAIL_FORMAT_HTML',  1);

class PhpMail extends Service {
  private $to = array();
  private $cc = array();
  private $bcc = array();
  private $from = array();
  private $reply_to = array();
  private $subject;
  private $headers = array();
  private $body;
  private $format = MAIL_FORMAT_HTML;
  private $eol = "\r\n";

  /**
   * Constructor
   */
  public function __construct($system, array $settings = array()) {
    parent::__construct($system);
  }

  /**
   * Add addresses to the 'To' field.
   */
  public function addRecipient($mail, $name = '') {$this->parseAddress(array($mail => $name), 'to');}

  /**
   * Add addresses to the 'CC' field.
   */
  public function addCC($mail, $name = '') {
    $this->parseAddress(array($mail => $name), 'cc');
  }

  /**
   * Add addresses to the 'BCC' field.
   */
  public function addBCC($mail, $name = '') {
    $this->parseAddress(array($mail => $name), 'bcc');
  }

  /**
   * Set the 'From' header.
   */
  public function from($mail, $name = '') {
    $this->parseAddress(array($mail => $name), 'from', FALSE);
  }

  /**
   * Set the 'Reply-to' header.
   */
  public function replyTo($mail, $name = '') {
    $this->parseAddress(array($mail => $name), 'reply_to', FALSE);
  }

  /**
   * Set the subject of the message.
   */
  public function subject($string) {
    $this->subject = check_plain($string);
  }

  /**
   * Set the body of the message.
   */
  public function body($string) {
    $this->body = filter_xss($string);
  }

  /**
   * Send the mail.
   */
  public function send() {
    // Check required fields.
    if (empty($this->to)) {
      System::setMessage('Message has no recipients.', SYSTEM_ERROR);
      return FALSE;
    }
    if (empty($this->subject)) {
      System::setMessage('Message has no subject.', SYSTEM_ERROR);
      return FALSE;
    }
    if (empty($this->body)) {
      System::setMessage('Message has no body.', SYSTEM_ERROR);
      return FALSE;
    }

    // Add required headers
    $req_headers = array(
      "MIME-Version: 1.0",
      "X-Mailer: PHP/" . phpversion(),
    );
    if ($this->format == MAIL_FORMAT_HTML) {
      $req_headers[] = "Content-type: text/html; charset=iso-8859-1;";
    }
    else {
      $req_headers[] = "Content-type: text/plain; charset=iso-8859-1";
    }
    $this->headers = array_merge($req_headers, $this->headers);

    // Add recipient headers
    $this->headers[] = $this->prepareAddressHeader($this->cc, 'Cc');
    $this->headers[] = $this->prepareAddressHeader($this->bcc, 'Bcc');
    $this->headers[] = $this->prepareAddressHeader($this->from, 'From');
    $this->headers[] = $this->prepareAddressHeader($this->reply_to, 'Reply-To');

    // Remove any empty spaces in the headers
    $this->headers = array_filter($this->headers);

    // Wrap the body to prevent splitting HTML on natural breaks.
    $this->body = wordwrap($this->body, 60);

    // Prepare the To value and Headers
    $to = $this->prepareAddressHeader($this->to);
    $headers = implode($this->eol, $this->headers);
    pd($headers);

    // Deliver the email!
    return mail($to, $this->subject, $this->body, $headers);
  }

  /**
   * Validate email address.
   */
  public function emailIsValid($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * Parse email addresses.
   */
  private function parseAddress($emails, $field, $multiple = TRUE) {
    foreach ($emails as $email => $name) {
      if ($this->emailIsValid($email)) {
        if ($multiple) {
          $this->{$field}[$email] = (is_numeric($name) ? '' : check_plain($name));
        }
        else {
          $this->{$field} = array($email => (is_numeric($name) ? '' : check_plain($name)));
        }
      }
      else {
        System::setMessage("Invalid e-mail address '$email'.", SYSTEM_ERROR);
      }
    }
  }

  /**
   * Prepare addresses for insertion into the header.
   */
  private function prepareAddressHeader($address, $prefix = NULL) {
    $header = array();

    foreach ($address as $email => $name) {
      $header[] = ($name ? "'{$name}' <{$email}>" : "$email");
    }

    return (!empty($header) ? ($prefix ? "{$prefix}: " : "") . implode(',', $header) : NULL);
  }
}
