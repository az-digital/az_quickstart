<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests language tokens.
 *
 * @group token
 */
class LanguageTest extends TokenKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'token',
  ];

  /**
   * Language codes of languages to enable during the test.
   *
   * @var array
   */
  protected $langcodes = ['bg', 'hu', 'nl', 'pt-pt'];

  /**
   * An array of languages used during the test, keyed by language code.
   *
   * @var \Drupal\language\Entity\ConfigurableLanguage[]
   */
  protected $languages = [];

  /**
   * Language prefixes used during the test.
   *
   * @var array
   */
  protected $language_prefixes = [];

  /**
   * Language domains used during the test.
   *
   * @var array
   */
  protected $language_domains = [];

  /**
   * The token replacement service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The mock language manager service.
   *
   * @var \Drupal\Tests\token\Kernel\MockLanguageManager
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    // Use Portuguese as the default language during the test. We're not using
    // English so we can detect if the default language is correctly honored.
    $language = Language::$defaultValues;
    $language['id'] = 'pt-pt';
    $language['name'] = 'Portuguese, Portugal';
    $container->setParameter('language.default_values', $language);
    $this->container
      ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
      ->addArgument('%language.default_values%');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->token = $this->container->get('token');

    // Use a version of the language manager in which the various languages can
    // be easily overridden during the test. We need to do this here instead of
    // in ::register() since the container is being altered by
    // LanguageServiceProvider::alter() after the services have been registered.
    $this->languageManager = new MockLanguageManager(
      $this->container->get('language.default'),
      $this->container->get('config.factory'),
      $this->container->get('module_handler'),
      $this->container->get('language.config_factory_override'),
      $this->container->get('request_stack')
    );
    $this->container->set('language_manager', $this->languageManager);

    foreach ($this->langcodes as $langcode) {
      // Enable test languages.
      $this->languages[$langcode] = ConfigurableLanguage::createFromLangcode($langcode);
      $this->languages[$langcode]->save();

      // Populate language prefixes and domains to use in the test.
      $this->language_prefixes[$langcode] = "$langcode-prefix";
      $this->language_domains[$langcode] = $langcode . '.example.com';
    }

    // Set language negotiation prefixes and domains to values that are uniquely
    // identifiable in the test.
    $language_negotiation_config = $this->config('language.negotiation');
    $language_negotiation_config->set('url.prefixes', $this->language_prefixes);
    $language_negotiation_config->set('url.domains', $this->language_domains);
    $language_negotiation_config->save();
  }

  /**
   * Tests the language tokens.
   *
   * @dataProvider languageTokenReplacementDataProvider
   */
  public function testLanguageTokenReplacement($token, $langcode, $expected_result) {
    $bubbleable_metadata = new BubbleableMetadata();
    $options = $langcode ? ['langcode' => $langcode] : [];
    // The part of the token name between the last `:` and the closing bracket
    // is the machine name of the token.
    preg_match('/\[.+:(.+)\]/', $token, $matches);
    $name = $matches[1];
    $replacements = $this->token->generate('language', [$name => $token], [], $options, $bubbleable_metadata);
    $this->assertEquals($expected_result, $replacements[$token]);
  }

  /**
   * Tests retrieving the interface and content language from the current page.
   *
   * @dataProvider currentPageLanguageTokenReplacementDataProvider
   */
  public function testCurrentPageLanguageTokenReplacement($token, $langcode, $expected_result) {
    // Set the interface language to Dutch.
    $this->languageManager->setCurrentLanguage(LanguageInterface::TYPE_INTERFACE, $this->languages['nl']);
    // Set the content language to Hungarian.
    $this->languageManager->setCurrentLanguage(LanguageInterface::TYPE_CONTENT, $this->languages['hu']);

    $options = $langcode ? ['langcode' => $langcode] : [];
    $result = $this->token->replace($token, [], $options);
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Provides test data for ::testLanguageTokenReplacement().
   *
   * @return array
   *   An array of test cases. Each test case is an array with the following
   *   values:
   *   - The token to test.
   *   - An optional language code to pass as an option.
   *   - The expected result of the token replacement.
   *
   * @see testLanguageTokenReplacement()
   */
  public function languageTokenReplacementDataProvider() {
    return [
      [
        // Test the replacement of the name of the site default language.
        '[language:name]',
        // We are not overriding the language by passing a language code as an
        // option. This means that the default language should be used which has
        // been set to Portuguese.
        NULL,
        // The expected result.
        'Portuguese, Portugal',
      ],
      // Test the replacement of the other properties of the default language.
      [
        '[language:langcode]',
        NULL,
        'pt-pt',
      ],
      [
        '[language:direction]',
        NULL,
        'ltr',
      ],
      [
        '[language:domain]',
        NULL,
        'pt-pt.example.com',
      ],
      [
        '[language:prefix]',
        NULL,
        'pt-pt-prefix',
      ],
      // Now repeat the entire test but override the language to use by passing
      // Bulgarian as an option.
      [
        '[language:name]',
        'bg',
        'Bulgarian',
      ],
      [
        '[language:langcode]',
        'bg',
        'bg',
      ],
      [
        '[language:direction]',
        'bg',
        'ltr',
      ],
      [
        '[language:domain]',
        'bg',
        'bg.example.com',
      ],
      [
        '[language:prefix]',
        'bg',
        'bg-prefix',
      ],
    ];
  }

  /**
   * Provides test data for ::testCurrentPageLanguageTokenReplacement().
   *
   * @return array
   *   An array of test cases. Each test case is an array with the following
   *   values:
   *   - The token to test.
   *   - An optional language code to pass as an option.
   *   - The expected result of the token replacement.
   *
   * @see testCurrentPageLanguageTokenReplacement()
   */
  public function currentPageLanguageTokenReplacementDataProvider() {
    return [
      [
        // Test the replacement of the language name token, taken from the
        // interface language of the current page.
        '[current-page:interface-language:name]',
        // We are not overriding the language by passing a language code as an
        // option. This means that the language should be taken from the
        // interface language which has been set to Dutch.
        NULL,
        // The expected result.
        'Dutch',
      ],
      // Test the token name in the content language.
      [
        '[current-page:content-language:name]',
        NULL,
        'Hungarian',
      ],
      // Test the other tokens both for the content and interface languages.
      [
        '[current-page:interface-language:langcode]',
        NULL,
        'nl',
      ],
      [
        '[current-page:content-language:langcode]',
        NULL,
        'hu',
      ],
      [
        '[current-page:interface-language:direction]',
        NULL,
        'ltr',
      ],
      [
        '[current-page:content-language:direction]',
        NULL,
        'ltr',
      ],
      [
        '[current-page:interface-language:domain]',
        NULL,
        'nl.example.com',
      ],
      [
        '[current-page:content-language:domain]',
        NULL,
        'hu.example.com',
      ],
      [
        '[current-page:interface-language:prefix]',
        NULL,
        'nl-prefix',
      ],
      [
        '[current-page:content-language:prefix]',
        NULL,
        'hu-prefix',
      ],
      // Now repeat the entire test with Bulgarian passed as an option. This
      // should not affect the results, the language should be sourced from the
      // current page.
      [
        // Test the replacement of the language name token, taken from the
        // interface language of the current page.
        '[current-page:interface-language:name]',
        // We are not overriding the language by passing a language code as an
        // option. This means that the language should be taken from the
        // interface language which has been set to Dutch.
        'bg',
        // The expected result.
        'Dutch',
      ],
      // Test the token name in the content language.
      [
        '[current-page:content-language:name]',
        'bg',
        'Hungarian',
      ],
      // Test the other tokens both for the content and interface languages.
      [
        '[current-page:interface-language:langcode]',
        'bg',
        'nl',
      ],
      [
        '[current-page:content-language:langcode]',
        'bg',
        'hu',
      ],
      [
        '[current-page:interface-language:direction]',
        'bg',
        'ltr',
      ],
      [
        '[current-page:content-language:direction]',
        'bg',
        'ltr',
      ],
      [
        '[current-page:interface-language:domain]',
        'bg',
        'nl.example.com',
      ],
      [
        '[current-page:content-language:domain]',
        'bg',
        'hu.example.com',
      ],
      [
        '[current-page:interface-language:prefix]',
        'bg',
        'nl-prefix',
      ],
      [
        '[current-page:content-language:prefix]',
        'bg',
        'hu-prefix',
      ],
    ];
  }

}
