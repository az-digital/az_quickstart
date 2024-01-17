<?php

namespace Drupal\az_publication;

use Drupal\Core\Language\LanguageManagerInterface;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\StyleSheet;

/**
 * Fetches locale information for publications.
 */
class AZPublicationLocaleMetadata {

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new AZPublicationLocaleMetadata object.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Gets the supported locale identifier from citation-style-language/locales.
   *
   * @return string
   *   Supported locale identifier, e.g. en-US.
   */
  public function getLocaleId() {
    // en-US is the default if no other language can be found.
    $id = 'en-US';
    $lang = $this->languageManager->getCurrentLanguage()->getId();
    /* Check if the current language is a supported locale string.
     * Most Drupal language strings do not contain a country code.
     * For example, es is more common than es-MX or es-ES unless the site is
     * making particular effort to curate specific dialects. loadLocales()
     * defaults to the default primary dialect in this case, as specified in
     * citation-style-language/locales/locales.json
     */
    try {
      $locale = StyleSheet::loadLocales($lang);
      if (!empty($locale)) {
        $id = $lang;
      }
    }
    // Language code was not seemingly a supported locale string.
    catch (CiteProcException $e) {
    }
    return $id;
  }

}
