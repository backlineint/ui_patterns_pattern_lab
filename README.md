CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The UI Patterns Pattern Lab module automatically discovers patterns defined in
a [Pattern Lab](http://patternlab.io/) instance and makes them available to be
used in Drupal as [UI Patterns](https://www.drupal.org/project/ui_patterns).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/ui_patterns_pattern_lab

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/ui_patterns_pattern_lab


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * ui_patterns - https://www.drupal.org/project/ui_patterns


RECOMMENDED MODULES
-------------------

This module will recognize Pattern Lab patterns in any active module or theme's
/templates directory, along with any paths defined as Twig Namespaces in your
theme by the:

 * [Component Libraries module](https://www.drupal.org/project/components)


INSTALLATION
------------

 * Install the UI Patterns Pattern Lab module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Clear caches.
    3. Patterns should be visible at /patterns and available to use with any of
       the UI Patterns integration modules.


MAINTAINERS
-----------

This project would not exist without the work of Antonio De Marco who maintains
the UI Patterns module and Pierre Dureau who created the
[UI Patterns Fractal integration](https://github.com/pdureau/ui_patterns_fractal)
that this project is based on.

 * Antonio De Marco - https://www.drupal.org/u/ademarco
 * Pierre Dureau - https://www.drupal.org/u/pdureau
