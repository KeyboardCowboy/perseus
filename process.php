<?php
$system = $_SESSION['system'];
   print '<pre>' . print_r($system, 1) . '</pre>';
   print '<pre>' . print_r($_SESSION, 1) . '</pre>';

//Check whether the form has been submitted
if (array_key_exists('check_submit', $_POST)) {
   //Converts the new line characters (\n) in the text area into HTML line breaks (the <br /> tag)
   //$_POST['Comments'] = nl2br($_POST['Comments']);

   //Let's now print out the received values in the browser
   echo "Name: {$_POST['name']}<br />";
   echo "Affiliation: {$_POST['affiliation']}<br /><br />";
   echo "Address: <br />{$_POST['address']}<br />";
   echo "{$_POST['city']}<br />";
   echo "{$_POST['state']}<br />";
   echo "{$_POST['zip']}<br />";
   echo "{$_POST['country']}<br /><br />";
   echo "Phone: {$_POST['phone']}<br />";
   echo "Fax: {$_POST['fax']}<br />";
   echo "Email: {$_POST['mail']}<br />";
   echo "Meal: {$_POST['meal']}<br />";
   $data = $_POST;
   unset($data['check_submit']);
   unset($data['submit']);
   print '<pre>' . print_r($data, 1) . '</pre>';
   $system->db()->insert('registration', $data);
} else {
    echo "You can't see this page without submitting the form.";
}
?>