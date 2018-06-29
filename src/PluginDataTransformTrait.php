<?php

namespace Drupal\ui_patterns_pattern_lab;


  /**
   * Manage Data transform functions.
   * https://github.com/aleksip/plugin-data-transform
   */
trait PluginDataTransformTrait {

  /**
   * "Include pattern file" function.
   *
   *  @param array $preview
   *    Pattern Lab preview array.
   *
   *  @return array
   *    Drupal render array.
   */
  private function includePatternFiles($preview) {
    if (in_array("pattern", array_keys($preview))) {
      // Advanced syntax
      $render_array = $preview['with'];
      $render_array["type"] = "pattern";
      $shorthand = explode("-", $preview["pattern"]);
      $pattern_name = str_replace($shorthand[0] . '-', "", $preview["pattern"]);
      $pattern_id = str_replace("-", "_", $pattern_name);
      // ui_patterns doesn't manage variants yet. So, only patttern_id is set.
      // https://github.com/nuvoleweb/ui_patterns/issues/118
      $render_array["id"] = $pattern_id;
      // TODO: Pattern's variables are not passed.
    }
    else {
      // TODO: Shorthand syntax.
    }
    return $render_array;
  }

  /**
   * "Join text values" function 
   *
   *  @param array $preview
   *    Pattern Lab preview array.
   *
   *  @return array
   *    Drupal render arrays.
   */
  private function joinTextValues($preview) {
    $render_arrays = [];
    foreach ($preview as $value) {
      if (in_array("include()", array_keys($value))) {
        // https://github.com/aleksip/plugin-data-transform/issues/15
        $render_array = $this->includePatternFiles($value["include()"]);
      }
      elseif ($value !== strip_tags($value)) {
        $render_array = [
          "type" => "inline_template",
          "template" => $value,
        ];
      }
      else {
        // TODO: Get pattern ID. Beware of Patter Lab prefix, and 
        // pseudo-patterns suffix.
        // TODO: Test pattern existant with plugin.manager.ui_patterns service.
        // TODO: Render with [ "type" => "pattern", "id" => $pattern_id]
        // else with [ "type" => "inline_template", "template" => $value].
      }
      $render_arrays[] = $render_array;
    }
    return $render_arrays;
  }

  /**
   * "Create Drupal Attribute objects" function.
   *
   *  @param array $preview
   *    Pattern Lab preview array.
   *
   *  @return array
   *    Drupal render array.
   */
  private function createAttributeObjects($preview) {
    // TODO
    return [];
  }

  /**
   * "Create Drupal Url objects" function.
   *
   *  @param array $preview
   *    Pattern Lab preview array.
   *
   *  @return array
   *    Drupal render array.
   */
  private function createUrlObjects($preview) {
    // TODO
    return [];
  }

}
