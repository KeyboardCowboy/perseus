# Perseus PHP Library
[https://github.com/KeyboardCowboy/perseus](https://github.com/KeyboardCowboy/perseus)

The Perseus PHP library is a collection of useful PHP tools around a central
System manager to handle user sessions and common site-building frameworks.

## Version 3.x
Version 3 introduces the Twig theming system and PHP namespaces.

### Requirements
- PHP 5.3.0 or greater


### Classes

#### Service Classes
Service classes are primary purpose of Perseus.  They are extendable classes
for performing specific functions such as generating forms or connecting to a
MySQL database.  They are extensions of the Service class and instantiated via
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
