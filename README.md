# Perseus PHP Library
[https://github.com/KeyboardCowboy/perseus](https://github.com/KeyboardCowboy/perseus)

The Perseus PHP library is a collection of useful PHP tools around a central
System manager to handle user sessions and common site-building frameworks.

## Version 3.x
Version 3 introduces the Twig theming system and PHP namespaces.

### Requirements
- PHP 5.3.0 or greater

### Included Libraries
- Krumo 2.0 for debugging
- Twig for theming

### Structure
- extensions
  Place third party service extensions here.  **This is the only directory that
  should be altered.**

- includes
  Various libraries and helper functions to assist Perseus.

- services
  Extendable tools such as MySQL connectors and PHP Mailers that Perseus
  natively implements.

- system
  The core of Perseus.  These classes manage Perseus's core functionality.

- test
  A testing class to ensure all Perseus's tools work well together.

- theme
  Templates and processors to handle all markup. May be overridden with your
  own theme files.

### Classes

#### Service Classes
Service classes are primary purpose of Perseus.  They are extendable classes
for performing specific functions such as generating forms or connecting to a
MySQL database.  They extend the Service class and are instantiated via
the System class in the following manner:



`$object = $system->newService('ServiceType', $settings);`

Where `ServiceType` is the name of the service such as Form or MySQL.

- CSV
- Form
- MySQL
- PhpMail
- XMLParser

#### System Classes
Perseus uses system classes for internal use such as debugging and installing.

- Debug
- Exception
- Installer
- System
