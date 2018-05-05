# UI Patterns Pattern Lab

The UI Patterns Pattern Lab module automatically discovers patterns defined in a [Pattern Lab](http://patternlab.io/) instance and makes them available to be used in Drupal as [UI Patterns](https://www.drupal.org/project/ui_patterns).

This module will recognize Pattern Lab patterns in any active module or theme's /templates directory, along with any paths defined as Twig Namespaces in your theme by the [Component Libraries module](https://www.drupal.org/project/components). After enabling this module (which will also enable the dependencies ui_patterns and ui_patterns_library) and clearing your cache, patterns should be visible at /patterns and available to use with any of the UI Patterns integration modules.

This project would not exist without the work of [Antonio De Marco](https://www.drupal.org/u/ademarco) who maintains the UI Patterns module and [Pierre Dureau](https://www.drupal.org/u/pdureau) who created the [UI Patterns Fractal integration](https://github.com/pdureau/ui_patterns_fractal) that this project is based on.