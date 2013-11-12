# Orion SASS Library
Version 2.0-beta1

https://github.com/KeyboardCowboy/orion

The CSS Directory is organized in the following manner:

## Contents

- Variables
  Variables needed for certain mixins and functions, both globally and locally.

- Mixins
  Tools are SASS files containing mixins and functions that the
  components rely on to run.  They may be safely imported into child themes
  without the risk of duplicating CSS declarations.

- Placeholders
  These are commonly used styles that can be extended into CSS selectors at will.

- Components
  Components are purely CSS declarations.  They should not contain any variables
  or other functionality.

## Changelog

### Release 2.0-beta1
- Added zen-grids back into mixins
- Added Drupal QuickFix for improved status messages
- Upgraded x-rem to handle multiple values
- Removed Text-Shadow mixin.  IE just sucks at rendering text shadows.
- Completely restructured the repo.
