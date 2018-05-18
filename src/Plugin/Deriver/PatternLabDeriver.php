<?php

namespace Drupal\ui_patterns_pattern_lab\Plugin\Deriver;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Json;
use Drupal\ui_patterns_library\Plugin\Deriver\LibraryDeriver;
use Spatie\YamlFrontMatter\YamlFrontMatter;

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

        // Pattern definition needs to have some valid content
        if (empty($content)) {
          continue;
        }

        // We need a Twig file to have a valid pattern.
        if (!$this->templateExists($content, $absolute_base_path, $id)) {
          continue;
        }

        // Skip pattern if overriden and set to ignore.
        if (isset($content['ui_pattern_definition']['ignore']) && $content['ui_pattern_definition']['ignore'] == TRUE) {
          continue;
        }

        // Parse markdown documentation if it exists
        $documentation = $this->getDocumentation($absolute_base_path, $id);

        // Set pattern meta.
        // Convert hyphens to underscores so that the pattern id will validate.
        // Also strip initial numbers that are ignored by Pattern Lab when naming.
        $definition['id'] = ltrim(str_replace("-", "_", $id), "0..9_");
        $definition['base path'] = dirname($file_path);
        $definition['file name'] = $absolute_base_path;
        // If pattern is provided by a twig namespace, pass just the theme name
        // as the provider
        $definition['provider'] = array_shift(explode("@", $provider));

        // Set other pattern values.
        # The label is typically displayed in any UI navigation items that
        # refer to the component. Defaults to a title-cased version of the
        # component name if not specified.
        $definition['label'] = $this->getLabel($content, $documentation, $definition['id']);
        $definition['description'] = $this->getDescription($content, $documentation);
        $definition['fields'] = $this->getFields($content);
        $definition['libraries'] = $this->getLibraries($id, $absolute_base_path);

        // Override patterns behavior.
        // Use a stand-alone Twig file as template.
        $definition["use"] = $this->getTemplatePath($content, $base_path, $absolute_base_path, $id);

        // Add pattern to collection.
        $patterns[] = $this->getPatternDefinition($definition);
      }
    }
    return $patterns;
  }

  /**
   *
   */
  private function templateExists($content, $absolute_base_path, $id) {
    // If a Twig template is explicitly defined, use that...
    if (isset($content['ui_pattern_definition']['use'])) {
      // Strip out only the file name in case a path was provided in the use value
      $template_file = end(explode("/", $content['ui_pattern_definition']['use']));
      return file_exists($absolute_base_path . "/" . $template_file) ? TRUE : FALSE;
    }
    // Otherwise look for a template that contains the same name as the pattern deifnition file.
    else {
      if (array_shift(glob($absolute_base_path . "/*" . ltrim($id, "0..9_-") . ".twig")) != NULL) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   *
   */
  private function getLabel($content, $documentation, $id) {
    // If the label was manually overriden, use that.
    if (isset($content['ui_pattern_definition']['label'])) {
      return $content['ui_pattern_definition']['label'];
    }
    // If a title is included in documentaton frontmatter, use that.
    elseif ($documentation->matter('title') !== NULL) {
      return $documentation->matter('title');
    }
    // Otherwise, fall back on a cleaned up version of the id
    else {
      return ucwords(urldecode($id));
    }
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
  private function getDescription($content, $documentation) {
    // If description was manually overriden, use that.
    if (isset($content['ui_pattern_definition']['description'])) {
      return $content['ui_pattern_definition']['description'];
    }
    else {
      // If there is a description in the body, return that. Otherwise this will
      // return an empty string.
      return $documentation->body();
    }
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

  /**
   *
   */
  private function getTemplatePath($content, $base_path, $absolute_base_path, $id) {
    // If a Twig template is explicitly defined, use that...
    if (isset($content['ui_pattern_definition']['use'])) {
      // Strip out only the file name in case a path was provided in the use value
      $template_file = end(explode("/", $content['ui_pattern_definition']['use']));
      return $base_path . "/" . $template_file;
    }
    // Next try an exact match for a template with the same name as the
    // pattern deifnition file.
    elseif (file_exists($absolute_base_path . "/" . $id . ".twig")) {
      return $base_path . "/" . $id . ".twig";
    }
    // Finally, look for a match that contains the id. This allows for a template
    // name that only differs by leading numbers for example.
    else {
      // Assuming here that the first match is our best option.
      $closest_template = array_shift(glob($absolute_base_path . "/*" . ltrim($id, "0..9_-") . ".twig"));
      return str_replace($absolute_base_path, $base_path, $closest_template);
    }
  }

  /**
   *
   */
  private function getDocumentation($absolute_base_path, $id) {
    // Try an exact match for a markdown file with the same name as the
    // pattern definition file.
    if (file_exists($absolute_base_path . "/" . $id . ".md")) {
      $md = file_get_contents($absolute_base_path . "/" . $id . ".md");
      return YamlFrontMatter::parse($md);
    }
    // Otherwise, look for a match that contains the id. This allows for a markdown
    // name that only differs by leading numbers for example.
    elseif (array_shift(glob($absolute_base_path . "/*" . ltrim($id, "0..9_-") . ".md")) != NULL) {
      // Assuming here that the first match is our best option.
      $closest_md = array_shift(glob($absolute_base_path . "/*" . ltrim($id, "0..9_-") . ".md"));
      $md = file_get_contents($closest_md);
      return YamlFrontMatter::parse($md);
    }
    // If we can't find any .md file return an empty YamlFrontMatter object.
    else {
      return YamlFrontMatter::parse("");
    }
  }

}