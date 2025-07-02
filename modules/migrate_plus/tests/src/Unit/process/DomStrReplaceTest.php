<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Plugin\migrate\process\DomStrReplace;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom_str_replace process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\DomStrReplace
 */
final class DomStrReplaceTest extends MigrateProcessTestCase {

  /**
   * Example configuration for the dom_str_replace process plugin.
   *
   * @var array
   */
  private static $exampleConfiguration = [
    'mode' => 'attribute',
    'xpath' => '//a',
    'attribute_options' => [
      'name' => 'href',
    ],
    'search' => 'foo',
    'replace' => 'bar',
  ];

  /**
   * @covers ::__construct
   *
   * @dataProvider providerTestConfigEmpty
   */
  public function testConfigValidation(array $config_overrides, string $message): void {
    $configuration = $config_overrides + self::$exampleConfiguration;
    $value = '<p>A simple paragraph.</p>';
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage($message);
    (new DomStrReplace($configuration, 'dom_str_replace', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Dataprovider for testConfigValidation().
   */
  public static function providerTestConfigEmpty(): array {
    $cases = [
      'xpath-null' => [
        ['xpath' => NULL],
        "Configuration option 'xpath' is required.",
      ],
      'mode-null' => [
        ['mode' => NULL],
        "Configuration option 'mode' is required.",
      ],
      'mode-invalid' => [
        ['mode' => 'invalid'],
        'Configuration option "mode" only accepts the following values: attribute, element.',
      ],
      'attribute_options-null' => [
        ['attribute_options' => NULL],
        "Configuration option 'attribute_options' is required for mode 'attribute'.",
      ],
      'search-null' => [
        ['search' => NULL],
        "Configuration option 'search' is required.",
      ],
      'replace-null' => [
        ['replace' => NULL],
        "Configuration option 'replace' is required.",
      ],
    ];

    return $cases;
  }

  /**
   * @covers ::transform
   */
  public function testTransformInvalidInput(): void {
    $configuration = [
      'xpath' => '//a',
      'mode' => 'attribute',
      'attribute_options' => [
        'name' => 'href',
      ],
      'search' => 'foo',
      'replace' => 'bar',
    ];
    $value = 'string';
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage('The dom_str_replace plugin in the destinationproperty process pipeline requires a \DOMDocument object. You can use the dom plugin to convert a string to \DOMDocument.');
    (new DomStrReplace($configuration, 'dom_str_replace', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(string $input_string, array $configuration, string $output_string): void {
    $value = Html::load($input_string);
    $document = (new DomStrReplace($configuration, 'dom_str_replace', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
    $this->assertEquals($output_string, Html::serialize($document));
  }

  /**
   * Dataprovider for testTransform().
   */
  public static function providerTestTransform(): array {
    $cases = [
      'string:case_sensitive' => [
        '<a href="/foo/Foo/foo">text</a>',
        self::$exampleConfiguration,
        '<a href="/bar/Foo/bar">text</a>',
      ],
      'string:case_insensitive' => [
        '<a href="/foo/Foo/foo">text</a>',
        [
          'case_insensitive' => TRUE,
        ] + self::$exampleConfiguration,
        '<a href="/bar/bar/bar">text</a>',
      ],
      'regex' => [
        '<a href="/foo/Foo/foo">text</a>',
        [
          'search' => '/(.)\1/',
          'regex' => TRUE,
        ] + self::$exampleConfiguration,
        '<a href="/fbar/Fbar/fbar">text</a>',
      ],
    ];

    return $cases;
  }

}
