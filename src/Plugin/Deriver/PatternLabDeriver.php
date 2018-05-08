<?php

namespace Drupal\ui_patterns_pattern_lab\Plugin\Deriver;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Json;
use Drupal\ui_patterns_library\Plugin\Deriver\LibraryDeriver;

/**
 * Class PatternLabDeriver.
 *
 * @package Drupal\ui_patterns_pattern_lab\Deriver
 */
class PatternLabDeriver extends LibraryDeriver {

  /**
   * {@inheritdoc}
   */
  public function getFileExtensions() {
    // Configuration files can be formatted as JSON or YAML
    return [
      ".json",
      ".yml",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPatterns() {
    $patterns = [];
    $themes = [];

    // Get a list of the path to /templates in all active modules and themes
    $active_directories = $this->getDirectories();
    foreach ($active_directories as $provider => $path) {
      $active_directories[$provider] = $path . "/templates";
    }

    // Get the list of currently active default theme and related base themes
    $theme_handler = \Drupal::service('theme_handler');
    $default_theme = $theme_handler->getDefault();
    $themes[$default_theme] = $default_theme;
    $base_themes = $theme_handler->getBaseThemes($theme_handler->listInfo(), $default_theme);
    $themes = $themes + $base_themes;

    // Determine the paths to any defined component libraries.
    $namespace_paths = [];
    foreach ($themes as $theme => $item) {
      $theme_config = $theme_handler->getTheme($theme);
      if (isset($theme_config->info["component-libraries"])) {
        foreach ($theme_config->info["component-libraries"] as $namespace => $path) {
          foreach($path['paths'] as $key => $path_item) {
            $root = $this->root;
            $subpath = $theme_config->getPath();
            $namespace_path = $path_item;
            $provider = $theme . "@" . $namespace . "_" . $key;
            $namespace_paths[$provider] =  $root . "/" . $subpath . "/" . $namespace_path;
          }
        }
      }
    }

    // Combine module, theme, and component library paths
    $all_directories = $active_directories + $namespace_paths;

    // Traverse directories looking for pattern definitions
    foreach ($all_directories as $provider => $directory) {
      foreach ($this->fileScanDirectory($directory) as $file_path => $file) {
        $absolute_base_path = dirname($file_path);
        $base_path = str_replace($this->root, "", $absolute_base_path);
        $id = $file->name;
        $definition = [];

        // We need a Twig file to have a valid pattern.
        if (!file_exists($absolute_base_path . "/" . $id . ".twig")) {
          continue;
        }

        // Parse definition file.
        $content = [];
        if (preg_match('/\.yml$/', $file_path)) {
          $content = file_get_contents($file_path);
          $content = Yaml::decode($content);
        }
        elseif (preg_match('/\.json$/', $file_path)) {
          $content = file_get_contents($file_path);
          $content = Json::decode($content);
        }
        if (empty($content)) {
          continue;
        }

        // Set pattern meta.
        // Convert hyphens to underscores so that the pattern id will validate.
        $definition['id'] = str_replace("-", "_", $id);
        $definition['base path'] = dirname($file_path);
        $definition['file name'] = $absolute_base_path;
        // If pattern is provided by a twig namespace, pass just the theme name
        // as the provider
        $definition['provider'] = array_shift(explode("@", $provider));

        // Set other pattern values.
        # The label is typically displayed in any UI navigation items that
        # refer to the component. Defaults to a title-cased version of the
        # component name if not specified.
        $definition['label'] = isset($content['ui_pattern_definition']['label']) ? $content['ui_pattern_definition']['label'] : ucwords(urldecode(str_replace("-", "_", $id)));
        $definition['description'] = $this->getDescription($content, $absolute_base_path, $id);
        $definition['fields'] = $this->getFields($content);
        $definition['libraries'] = $this->getLibraries($id, $absolute_base_path);

        // Override patterns behavior.
        // Use a stand-alone Twig file as template.
        $definition["use"] = $base_path . "/" . $id . ".twig";

        // Add pattern to collection.
        $patterns[] = $this->getPatternDefinition($definition);
      }
    }
    return $patterns;
  }

  /**
   *
   */
  private function getFields($content) {

    // The field data to pass to the template when rendering previews.
    $fields = [];

    // If we've explicitly defined ui_pattern_definitions, parse fields from there
    if (isset($content['ui_pattern_definition']['fields'])) {
      foreach ($content['ui_pattern_definition']['fields'] as $field => $definition) {
        $fields[$field] = [
          "type" => isset($definition['type']) ? $definition['type'] : NULL,
          "label" => isset($definition['label']) ? $definition['label'] : '',
          "description" => isset($definition['description']) ? $definition['description'] : NULL,
          "preview" => isset($definition['preview']) ? $definition['preview'] : NULL,
        ];
      }
    }
    // Otherwise, cross our fingers and use the fields in the definition file
    else {
      foreach ($content as $field => $preview) {
        // Ignore the ui_pattern_definiton key if we're using it to define other
        // aspects of the pattern
        if ($field != 'ui_pattern_definition') {
          $fields[$field] = [
            "label" => $field,
            "preview" => $preview,
          ];
        }
      }
    }

    // Remove illegal attributes field.
    unset($fields['attributes']);

    return $fields;
  }

  /**
   *
   */
  private function getDescription($content, $base_path, $id) {
    // Any notes set here override content taken from the component’s README.md
    // file, if there is one. Accepts markdown.
    if (isset($content['ui_pattern_definition']['description'])) {
      return $content['ui_pattern_definition']['description'];
    }
    if (file_exists($base_path . "/" . $id . ".md")) {
      $md = file_get_contents($base_path . "/" . $id . ".md");
      // TODO: Markdown parsing.
      return $md;
    }
    return "";
  }

  /**
   *
   */
  private function getLibraries($id, $base_path) {
    $libraries = [];

    // If we've explicitly defined a libraries key in our ui_pattern_definitions,
    // parse them there
    if (isset($content['ui_pattern_definition']['libraries'])) {
      // TODO - add libraries support
    }
    // If libraries aren't explicitly defined, we'll look for css and js assets
    // with the same name as the pattern.
    else {
      if (file_exists($base_path . "/" . $id . ".css")) {
        $libraries[$id]["css"]["theme"][$id . ".css"] = [];
      }

      if (file_exists($base_path . "/" . $id . ".js")) {
        $libraries[$id]["js"][$id . ".js"] = [];
      }

      // The root level of libraries must be a list.
      if (!empty($libraries)) {
        $libraries = [
          $libraries,
        ];
      }
    }

    return $libraries;
  }

}