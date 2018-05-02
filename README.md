# UI Patterns Pattern Lab

The UI Patterns Pattern Lab module automatically discovers patterns defined in a Pattern Lab instance and makes them available to be used in Drupal as UI Patterns.

Currently your Pattern Lab pattern must exist in (or be linked from) the /templates directory of a module or theme, but custom Twig namespaces will be supported in the near future so that your patterns can live elsewhere. After enabling this module (which will also enable the dependencies ui_patterns and ui_patterns_library) and clearing your cache, patterns should be visible at /patterns and available to use with any of the UI Patterns integration modules.

This project would not exist without the work of Antonio De Marco who maintains the UI Patterns module and Pierre Dureau who created the UI Patterns Fractal integration that this project is based on.