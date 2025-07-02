<?php

namespace Drupal\smart_date\FormElement;

use Drupal\Core\Language\LanguageInterface;
use Drupal\config_translation\FormElement\FormElementBase;

/**
 * Defines the boolean element for the configuration translation interface.
 */
class Boolean extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getTranslationElement(LanguageInterface $translation_language, $source_config, $translation_config) {
    return [
      '#type' => 'checkbox',
      '#return_value' => '1',
    ] + parent::getTranslationElement($translation_language, $source_config, $translation_config);
  }

}
