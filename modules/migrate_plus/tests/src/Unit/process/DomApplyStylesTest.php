<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_plus\Plugin\migrate\process\DomApplyStyles;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom_apply_styles process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\DomApplyStyles
 */
final class DomApplyStylesTest extends MigrateProcessTestCase {

  /**
   * Example configuration for the dom_apply_styles process plugin.
   *
   * @var array
   */
  protected $exampleConfiguration = [
    'format' => 'test_format',
    'rules' => [
      [
        'xpath' => '//b',
        'style' => 'Bold',
      ],
      [
        'xpath' => '//span/i',
        'style' => 'Italic',
        'depth' => 1,
      ],
    ],
  ];

  /**
   * Mock a config factory object.
   */
  protected ?object $configFactory = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Mock a config object.
    $prophecy = $this->prophesize(ImmutableConfig::class);
    $prophecy
      ->get('editor')
      ->willReturn('ckeditor');
    $prophecy
      ->get('settings.plugins.stylescombo.styles')
      ->willReturn("strong.foo|Bold\r\nem.foo.bar|Italic\r\n");
    $style_config = $prophecy->reveal();
    // Mock the config factory.
    $prophecy = $this->prophesize(ConfigFactory::class);
    $prophecy
      ->get('editor.editor.test_format')
      ->willReturn($style_config);
    $this->configFactory = $prophecy->reveal();

    parent::setUp();
  }

  /**
   * @covers ::__construct
   *
   * @dataProvider providerTestConfig
   */
  public function testValidateRules(array $config_overrides, string $message): void {
    $configuration = $config_overrides + $this->exampleConfiguration;
    $value = '<p>A simple paragraph.</p>';
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage($message);
    (new DomApplyStyles($configuration, 'dom_apply_styles', [], $this->configFactory))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Dataprovider for testValidateRules().
   */
  public static function providerTestConfig(): array {
    $cases = [
      'format-empty' => [
        ['format' => ''],
        'The "format" option must be a non-empty string.',
      ],
      'format-not-string' => [
        ['format' => [1, 2, 3]],
        'The "format" option must be a non-empty string.',
      ],
      'rules-not-array' => [
        ['rules' => 'invalid'],
        'The "rules" option must be an array.',
      ],
      'xpath-null' => [
        [
          'rules' => [['xpath' => NULL, 'style' => 'Bold']],
        ],
        'The "xpath" and "style" options are required for each rule.',
      ],
      'style-invalid' => [
        [
          'rules' => [['xpath' => '//b', 'style' => 'invalid-style']],
        ],
        'The style "invalid-style" is not defined.',
      ],
    ];

    return $cases;
  }

  /**
   * @covers ::transform
   */
  public function testTransformInvalidInput(): void {
    $value = 'string';
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage('The dom_apply_styles plugin in the destinationproperty process pipeline requires a \DOMDocument object. You can use the dom plugin to convert a string to \DOMDocument.');
    (new DomApplyStyles($this->exampleConfiguration, 'dom_apply_styles', [], $this->configFactory))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::transform
   */
  public function testTransform(): void {
    $input_string = '<div><span><b>Bold text</b></span><span><i>Italic text</i></span></div>';
    $output_string = '<div><span><strong class="foo">Bold text</strong></span><em class="foo bar">Italic text</em></div>';
    $value = Html::load($input_string);
    $document = (new DomApplyStyles($this->exampleConfiguration, 'dom_apply_styles', [], $this->configFactory))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
    $this->assertEquals($output_string, Html::serialize($document));
  }

}
