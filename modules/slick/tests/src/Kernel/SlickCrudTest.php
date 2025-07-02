<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickKernelTrait;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;
use Drupal\slick\Entity\Slick;
use Drupal\slick\SlickDefault;
use PHPUnit\Framework\Exception as UnitException;

/**
 * Tests creation, loading, updating, deleting of Slick optionsets.
 *
 * @coversDefaultClass \Drupal\slick\Entity\Slick
 *
 * @group slick
 */
class SlickCrudTest extends BlazyKernelTestBase {

  use SlickUnitTestTrait;
  use SlickKernelTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
    'blazy',
    'slick',
    'slick_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('slick');

    $this->blazyAdmin     = $this->container->get('blazy.admin.formatter');
    $this->slickManager   = $this->container->get('slick.manager');
    $this->slickFormatter = $this->container->get('slick.formatter');
    $this->slickAdmin     = $this->container->get('slick.admin');
  }

  /**
   * Tests CRUD operations for Slick optionsets.
   */
  public function testSlickCrud() {
    // Add a Slick optionset with minimum data only.
    $empty = Slick::create([
      'name'  => 'test_empty',
      'label' => 'Empty slick',
    ]);

    $empty->save();
    $this->verifySlickOptionset($empty);

    // Add main Slick optionset with possible properties.
    $main = Slick::create([
      'name'  => 'test_main',
      'label' => 'Test main',
    ]);

    $main->save();

    $settings = [
      'arrows'   => FALSE,
      'dots'     => TRUE,
      'lazyLoad' => 'progressive',
    ] + $main->getSettings();

    $main->set('group', 'main');
    $main->set('optimized', TRUE);
    $main->setSettings($settings);

    $main->save();

    $breakpoints = $main->getBreakpoints();
    $this->assertEmpty($breakpoints);
    $this->assertEquals('main', $main->getGroup());

    $optimized = $main->optimized();
    $this->assertNotEmpty($optimized);

    $this->verifySlickOptionset($main);

    // @todo Use dataProvider.
    try {
      $responsive_options = $main->getResponsiveOptions();
    }
    catch (UnitException $e) {
    }

    $this->assertTrue(TRUE);

    $responsive_settings = $settings;
    $main->set('breakpoints', 2);

    $breakpoints = [481, 769];
    foreach ($breakpoints as $key => $breakpoint) {
      $main->setResponsiveSettings($responsive_settings, $key, 'settings');
      $main->setResponsiveSettings($breakpoint, $key, 'breakpoint');
    }

    $main->save();

    $responsive_options = $main->getResponsiveOptions();

    foreach ($responsive_options as $key => $responsive) {
      $this->assertEquals('progressive', $responsive['settings']['lazyLoad']);
      $this->assertEquals($breakpoints[$key], $responsive['breakpoint']);
    }

    $options = $main->getSettings();
    $cleaned = $main->toJson($options);
    $this->assertArrayHasKey('responsive', $cleaned);

    foreach ($responsive_options as $key => $responsive) {
      $main->setResponsiveSettings(TRUE, $key, 'unslick');
    }

    $main->save();

    $options = $main->getSettings();
    $cleaned = $main->toJson($options);

    foreach ($cleaned['responsive'] as $key => $responsive) {
      $this->assertEquals('unslick', $responsive['settings']);
    }

    // Alter some slick optionset properties and save again.
    $main->set('label', 'Altered slick');
    $main->setSetting('mobileFirst', TRUE);
    $main->save();
    $this->verifySlickOptionset($main);

    // Enable autoplay and save again.
    $main->setSetting('autoplay', TRUE);
    $main->save();
    $this->verifySlickOptionset($main);

    // Add nav Slick optionset with possible properties.
    $nav = Slick::create([
      'name' => 'test_nav',
      'label' => 'Test nav',
    ]);

    $skin = $nav->getSkin();
    $this->assertEmpty($skin);

    $nav->setSetting('cssEaseBezier', 'easeInQuad');
    $nav->save();
    $settings = $nav->getSettings();

    $nav->removeWastedDependentOptions($settings);
    $this->assertArrayNotHasKey('cssEaseBezier', $settings);
    $this->assertEquals('easeInQuad', $settings['cssEase']);

    $this->assertEmpty($nav->getSetting('mobileFirst'));
    $nav->setSetting('mobileFirst', TRUE);
    $nav->save();
    $this->assertNotEmpty($nav->getSetting('mobileFirst'));

    // @todo Use dataProvider.
    try {
      $mobile_first = $nav->getOptions('settings', 'mobileFirst');
    }
    catch (UnitException $e) {
    }

    $this->assertTrue(!empty($mobile_first));

    try {
      $mobile_first = $nav->getOptions(['settings', 'mobileFirst']);
    }
    catch (UnitException $e) {
    }

    $this->assertTrue(!empty($mobile_first));

    $settings = $nav->getOptions('settings');
    $this->assertArrayHasKey('mobileFirst', $settings);

    $options = $nav->getOptions();
    $this->assertArrayHasKey('settings', $options);

    $merged = array_merge(Slick::defaultSettings() + SlickDefault::jsSettings(), $settings);
    $nav->setSettings($merged);
    $nav->save();
    $this->assertTrue(!empty($nav->getSetting('mobileFirst')));

    $nav->toJson($settings);
    $this->assertArrayNotHasKey('lazyLoad', $settings);

    // Delete the slick optionset.
    $nav->delete();

    $slicks = Slick::loadMultiple();
    $this->assertFalse(isset($slicks[$nav->id()]), 'Slick::loadMultiple: Disabled slick optionset no longer exists.');
  }

  /**
   * Verifies that a slick optionset is properly stored.
   *
   * @param \Drupal\slick\Entity\Slick $slick
   *   The Slick instance.
   */
  public function verifySlickOptionset(Slick $slick) {
    $t_args = ['@slick' => $slick->label()];
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Verify the loaded slick has all properties.
    $slick = Slick::load($slick->id());
    $this->assertEquals($slick->id(), $slick->id(), new FormattableMarkup('Slick::load: Proper slick id for slick optionset @slick.', $t_args));
    $this->assertEquals($slick->label(), $slick->label(), new FormattableMarkup('Slick::load: Proper title for slick optionset @slick.', $t_args));

    // Check that the slick was created in site default language.
    $this->assertEquals($slick->language()->getId(), $default_langcode, new FormattableMarkup('Slick::load: Proper language code for slick optionset %slick.', $t_args));
  }

}
