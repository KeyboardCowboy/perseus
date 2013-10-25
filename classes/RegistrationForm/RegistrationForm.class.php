<?php
/**
 * @file
 * A registration form built on Perseus.
 */
namespace Perseus\Tools;

use Perseus\Form;

class RegistrationForm extends Form {
  // Constructor
  public function __construct($system, $settings = array()) {
    parent::__construct($system, $settings);

    $provisions = '<strong>Provisions:</strong>  Continental Breakfast, Lunch, and Afternoon
                  Breaks will be provided for each day.  Please indicate if you
                  will require a vegetarian meal for lunch or if you have any
                  other special dietary requests.';
    $contact = '<strong>Please submit this registration form no later than
                November 16th, 2013 to:</strong><br /><br />
                Morgan Beck<br />
                National Renewable Energy Laboratory, National Bioenergy
                Center<br />
                15013 Denver West Parkway, MS 3322, Golden, Colorado
                80401-3393<br />
                <strong>Phone:</strong>  (303) 384-6233<br />
                <strong>Fax:</strong>  (303) 384-6363<br />
                <strong>E-mail:</strong>  <a href="mailto:Morgan.beck@nrel.gov">Morgan.beck@nrel.gov</a>';

    // Build the form
    $this->createNameInput();
    $this->createCheckSubmitHidden();
    $this->createAffiliationInput();
    $this->createAddressInput();
    $this->createCityInput();
    $this->createStateSelect();
    $this->createCountrySelect();
    $this->createZipInput();
    $this->createPhoneInput();
    $this->createFaxInput();
    $this->createEmailInput();
    $this->createHtml('provisions', $provisions);
    $this->createMealRadios();
    $this->createDietaryNeedTextarea();
    $this->createHtml('contact', $contact);
    $this->createSubmit();
  }

  /**
   * Create the address field.
   */
  private function createAddressInput() {
    $data = array(
      'name' => 'address',
      'label' => 'Address:',
      'attributes' => array(
        'maxlength' => 128,
        'size'      => 39,
      ),
      'validators' => array('plain_text'),
    );
    $this->addItem('input', $data);
  }

  /**
   * Create the affiliation field.
   */
  private function createAffiliationInput() {
    $data = array(
      'name' => 'affiliation',
      'label' => 'Affiliation:',
      'attributes' => array(
        'maxlength' => 128,
        'size'      => 39,
      ),
      'required' => TRUE,
      'validators' => array('plain_text'),
    );
    $this->addItem('input', $data);
  }

  /**
   * Create the hidden submit field.
   */
  private function createCheckSubmitHidden() {
    $data = array(
      'name' => 'check_submit',
      'value' => 1,
    );
    $this->addItem('hidden', $data);
  }

  /**
   * Create the affiliation field.
   */
  private function createCityInput() {
    $data = array(
      'name' => 'city',
      'label' => 'City:',
      'attributes' => array(
        'maxlength' => 128,
        'size'      => 39,
      ),
      'validators' => array('plain_text'),
    );
    $this->addItem('input', $data);
  }

  /**
   * Create the select of US states.
   */
  private function createCountrySelect() {
    $data = array(
      'name' => 'country',
      'label' => 'Country:',
      'options' => get_countries(),
    );
    $this->addItem('select', $data);
  }

  /**
   * Create the email field.
   */
  private function createDietaryNeedTextarea() {
    $data = array(
      'name' => 'dietary_needs',
      'label' => 'Other Special dietary needs:',
      'attributes' => array(
        'maxlength' => 255,
        'cols'      => 39,
        'rows'      => 5,
      ),
      'required' => TRUE,
    );
    $this->addItem('textarea', $data);
  }

  /**
   * Create the email field.
   */
  private function createEmailInput() {
    $data = array(
      'name' => 'mail',
      'label' => 'E-mail',
      'attributes' => array(
        'maxlength' => 255,
        'size'      => 39,
      ),
      'required' => TRUE,
      'validators' => array('email'),
    );
    $this->addItem('input', $data);
  }

  /**
   * Create the affiliation field.
   */
  private function createFaxInput() {
    $data = array(
      'name' => 'fax',
      'label' => 'Fax:',
      'attributes' => array(
        'maxlength' => 20,
        'size'      => 39,
      ),
      'validators' => array('plain_text'),
    );
    $this->addItem('input', $data);
  }

  /**
   * Create an HTML field.
   */
  private function createHtml($name, $html) {
    $data = array(
      'name' => $name,
      'html' => $html,
    );
    $this->addItem('html', $data);
  }

  /**
   * Create the affiliation field.
   */
  private function createMealRadios() {
    $data = array(
      'name' => 'meal',
      'label' => 'I will require a Vegetarian meal:',
      'options' => array(
        0 => 'No',
        1 => 'Yes',
      ),
      'default' => 0,
      'required' => TRUE,
    );
    $this->addItem('radios', $data);
  }

  /**
   * Create the name field.
   */
  private function createNameInput() {
    $data = array(
      'name' => 'name',
      'label' => 'First, Middle Initial & Last:',
      'attributes' => array(
        'maxlength' => 128,
        'size'      => 39,
      ),
      'required' => TRUE,
      'validators' => array('plain_text'),
    );
    // Start at weight 10 to avoid the key sorting issue
    // @see Form::render.
    $this->addItem('input', $data, 10);
  }

  /**
   * Create the affiliation field.
   */
  private function createPhoneInput() {
    $data = array(
      'name' => 'phone',
      'label' => 'Phone:',
      'attributes' => array(
        'maxlength' => 20,
        'size'      => 39,
      ),
      'required' => TRUE,
      'validators' => array('phone'),
    );
    $this->addItem('input', $data);
  }

  /**
   * Create the select of US states.
   */
  private function createStateSelect() {
    $data = array(
      'name' => 'state',
      'label' => 'State/Province:',
      'options' => array_merge(get_us_states(), get_canadian_provinces()),
      'required' => TRUE,
    );
    $this->addItem('select', $data);
  }

  /**
   * Create the select of US states.
   */
  private function createSubmit() {
    $data = array(
      'name' => 'submit',
      'attributes' => array(
        'value' => 'Submit',
      ),
    );
    $this->addItem('submit', $data);
  }

  /**
   * Create the affiliation field.
   */
  private function createZipInput() {
    $data = array(
      'name' => 'zip',
      'label' => 'Zip/Postal Code:',
      'attributes' => array(
        'maxlength' => 10,
        'size'      => 39,
      ),
      'validators' => array('plain_text'),
    );
    $this->addItem('input', $data);
  }
}