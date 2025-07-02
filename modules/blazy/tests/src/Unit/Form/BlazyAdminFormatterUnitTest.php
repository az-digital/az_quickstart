<?php

namespace Drupal\Tests\blazy\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Form\BlazyAdminFormatter;

/**
 * Tests the Blazy admin formatter form.
 *
 * @coversDefaultClass \Drupal\blazy\Form\BlazyAdminFormatter
 * @group blazy
 */
class BlazyAdminFormatterUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpUnitServices();
    $this->setUpUnitContainer();

    $this->stringTranslation = $this->createMock('\Drupal\Core\StringTranslation\TranslationInterface');
    $this->entityDisplayRepository = $this->createMock('\Drupal\Core\Entity\EntityDisplayRepositoryInterface');
    $this->typedConfig = $this->createMock('\Drupal\Core\Config\TypedConfigManagerInterface');
    $this->dateFormatter = $this->createMock('\Drupal\Core\Datetime\DateFormatter');

    $container = new ContainerBuilder();
    $container->set('entity_display.repository', $this->entityDisplayRepository);
    $container->set('config.typed', $this->typedConfig);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('date.formatter', $this->dateFormatter);
    $container->set('blazy.manager', $this->blazyManager);

    \Drupal::setContainer($container);

    $this->blazyAdminFormatter = new BlazyAdminFormatter(
      $this->entityDisplayRepository,
      $this->typedConfig,
      $this->dateFormatter,
      $this->blazyManager
    );
  }

  /**
   * Provide test cases for ::testBuildSettingsForm.
   */
  public static function providerTestBuildSettingsForm() {
    return [
      [FALSE],
      [TRUE],
    ];
  }

  /**
   * @covers ::buildSettingsForm
   * @covers ::openingForm
   * @covers ::fieldableForm
   * @covers ::imageStyleForm
   * @covers ::mediaSwitchForm
   * @covers ::gridForm
   * @covers ::closingForm
   * @covers ::finalizeForm
   * @dataProvider providerTestBuildSettingsForm
   */
  public function testBuildSettingsForm($vanilla) {
    $form = [];
    $definition = $this->getDefaulEntityFormatterDefinition()
      + $this->getScopedFormElements();

    $definition['settings'] += $this->getDefaultFields(TRUE);
    $definition['vanilla'] = $vanilla;
    $definition['_views'] = TRUE;

    $this->blazyAdminFormatter->openingForm($form, $definition);
    $this->assertEquals($vanilla, !empty($form['vanilla']));

    $this->blazyAdminFormatter->buildSettingsForm($form, $definition);
    $this->assertArrayHasKey('scopes', $definition);
    $this->assertArrayHasKey('opening', $form);
    $this->assertArrayHasKey('closing', $form);
  }

  /**
   * Provider for ::testGetSettingsSummary.
   */
  public static function providerTestGetSettingsSummary() {
    return [
      [FALSE, FALSE, FALSE, '', FALSE],
      [TRUE, TRUE, TRUE, 'blazy_responsive_test', TRUE],
      [TRUE, FALSE, FALSE, '', TRUE],
    ];
  }

  /**
   * Tests the Blazy admin ::getSettingsSummary().
   *
   * @dataProvider providerTestGetSettingsSummary
   */
  public function testGetSettingsSummary($use_settings, $vanilla, $override, $responsive_image_style, $expected) {
    $definition = $this->getFormatterDefinition();
    $settings = array_merge(BlazyDefault::gridSettings(), $definition['settings'] ?? []);

    $settings['vanilla']                = $vanilla;
    $settings['image_syle']             = 'large';
    $settings['box_style']              = 'blazy_crop';
    $settings['thumbnail_style']        = 'thumbnail';
    $settings['optionset']              = 'default';
    $settings['override']               = $override;
    $settings['overridables']           = ['foo' => 'foo', 'bar' => '0'];
    $settings['responsive_image_style'] = $responsive_image_style;
    $settings['caption']                = ['alt' => 'alt', 'title' => 'title'];

    $definition['settings'] = $use_settings ? $settings : [];

    $summary = $this->blazyAdminFormatter->getSettingsSummary($definition);
    $summary = array_filter($summary);
    $check_summary = !$expected ? empty($summary) : !empty($summary);

    $this->assertTrue($check_summary);
  }

}

namespace Drupal\blazy\Form;

if (!function_exists('responsive_image_get_image_dimensions')) {

  /**
   * Dummy function.
   */
  function responsive_image_get_image_dimensions() {
    // Empty block to satisfy coder.
  }

}
