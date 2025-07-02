<?php

namespace Drupal\metatag_hreflang\Plugin\metatag\Tag;

/**
 * A new hreflang tag will be made available for each language.
 *
 * The meta tag's values will be based upon this annotation.
 *
 * @MetatagTag(
 *   id = "hreflang_per_language",
 *   deriver = "Drupal\metatag_hreflang\Plugin\Derivative\HreflangDeriver",
 *   label = @Translation("Hreflang per language"),
 *   description = @Translation("This plugin will be cloned from these settings for each enabled language."),
 *   name = "hreflang_per_language",
 *   group = "hreflang",
 *   weight = 1,
 *   type = "uri",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class HreflangPerLanguage extends HreflangBase {

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    // Three languages are available, so there will be three three of these tags
    // available.
    // @see Drupal\Tests\metatag_hreflang\Functional::setUp()
    return [
      "//input[@name='hreflang_per_language:hreflang_en' and @type='text']",
      "//input[@name='hreflang_per_language:hreflang_es' and @type='text']",
      "//input[@name='hreflang_per_language:hreflang_fr' and @type='text']",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormData(): array {
    return [];

    // @todo Submitting this value results in the following error:
    // Drupal\Core\Config\Schema\SchemaIncompleteException:
    // Schema errors for metatag.metatag_defaults.global
    // with the following errors:
    // metatag.metatag_defaults.global:tags.hreflang_per_language:hreflang_en
    // missing schema in
    // Drupal\Core\Config\Development\ConfigSchemaChecker->onConfigSave()
    // Line 94 of
    // core/lib/Drupal/Core/Config/Development/ConfigSchemaChecker.php
    // @code
    // $random = new Random();
    // $hreflang = 'https://www.example.com/' . $random->word(6) . '.html';
    //
    // return [
    //   'hreflang_per_language:hreflang_en' => $hreflang,
    // ];
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputExistsXpath(): array {
    // @todo Take care of this once the problem above is solved.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    // @todo Take care of this once the problem above is solved.
    return [];
  }

}
