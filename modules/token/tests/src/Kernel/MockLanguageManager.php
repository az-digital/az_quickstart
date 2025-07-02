<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ConfigurableLanguageManager;

/**
 * A language manager that can be easily overridden for testing purposes.
 */
class MockLanguageManager extends ConfigurableLanguageManager {

  /**
   * List of current languages used in the test.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $currentLanguages;

  /**
   * {@inheritdoc}
   */
  public function getCurrentLanguage($type = LanguageInterface::TYPE_INTERFACE) {
    if (isset($this->currentLanguages[$type])) {
      return $this->currentLanguages[$type];
    }
    return parent::getCurrentLanguage($type);
  }

  /**
   * Sets the current language of the given type to use during tests.
   *
   * @param string $type
   *   The language type.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   */
  public function setCurrentLanguage($type, LanguageInterface $language) {
    $this->currentLanguages[$type] = $language;
  }

}
