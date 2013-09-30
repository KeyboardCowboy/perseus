<?php
/**
 * @file
 * Classes for testing service implementaitons.
 */
namespace Perseus;

class Test extends Service {
  // Constructor
  public function __construct($system, array $settings = array()) {
    parent::__construct($system);
  }

  // Create a form
  public function testForm() {
    try {
      $form = $this->system->newService('form');

      $vars = array(
        'label' => 'My Radios',
        'name' => 'my-radios',
        'options' => array(
          1 => 'One',
          2 => 'Two',
          3 => 'Three',
          4 => 'Four',
          5 => 'Five',
          6 => 'Six',
          7 => 'Seven',
        ),
        'cols' => 3,
      );

      // Test a form
      $form->addItem('radios', $vars);
      print $form->render();
    }
    catch(Exception $e) {System::handleException($e);}
  }

  /**
   * Test a MySQL Connection
   */
  public function testMySql() {
    try {
      $db = $this->system->newService('db', array('database' => 'default'));
      $db->insert('test', array('val1' => 'yes!', 'val2' => 'no!'));
    }
    catch(Exception $e) {System::handleException($e);}
  }
}
