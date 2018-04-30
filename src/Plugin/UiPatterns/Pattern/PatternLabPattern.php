<?php

namespace Drupal\ui_patterns_pattern_lab\Plugin\UiPatterns\Pattern;

use Drupal\ui_patterns\Plugin\PatternBase;

/**
 * The UI Pattern plugin.
 *
 * @UiPattern(
 *   id = "pattern_lab",
 *   label = @Translation("Pattern Lab Pattern"),
 *   description = @Translation("Pattern provided by a Pattern Lab instance."),
 *   deriver = "\Drupal\ui_patterns_pattern_lab\Plugin\Deriver\PatternLabDeriver"
 * )
 */
class PatternLabPattern extends PatternBase {

}