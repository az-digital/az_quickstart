<?php

namespace Drupal\Tests\extlink\Kernel;

use Drupal\Tests\extlink\Traits\ExtlinkMigrationTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of extlink settings from D7 to config.
 *
 * @group extlink
 */
class ValidateD7SettingsMigrationTest extends MigrateDrupal7TestBase {
  use ExtlinkMigrationTestTrait;

  /**
   * The migration this test is testing.
   *
   * @var string
   */
  const MIGRATION_UNDER_TEST = 'd7_extlink_settings';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['extlink'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      \Drupal::service('extension.list.module')->getPath('extlink'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]));
    $this->installConfig(['extlink']);
  }

  /**
   * Test that variables are successfully migrated to configuration.
   */
  public function testMigration(): void {
    // Set up fixtures in the source database.
    $fixtureAlert = $this->randomBoolean();
    $this->setUpD6D7Variable('extlink_alert', $fixtureAlert);
    $fixtureAlertText = $this->getRandomGenerator()->paragraphs(1);
    $this->setUpD6D7Variable('extlink_alert_text', $fixtureAlertText);
    $fixtureClass = $this->randomString();
    $this->setUpD6D7Variable('extlink_class', $fixtureClass);
    $fixtureCssExclude = $this->randomSpaceSeparatedWords();
    $this->setUpD6D7Variable('extlink_css_exclude', $fixtureCssExclude);
    $fixtureCssExplicit = $this->randomSpaceSeparatedWords();
    $this->setUpD6D7Variable('extlink_css_explicit', $fixtureCssExplicit);
    $fixtureExclude = $this->randomRegex();
    $this->setUpD6D7Variable('extlink_exclude', $fixtureExclude);
    $fixtureIconPlacement = $this->randomString();
    $this->setUpD6D7Variable('extlink_icon_placement', $fixtureIconPlacement);
    $fixtureImgClass = $this->randomBoolean();
    $this->setUpD6D7Variable('extlink_img_class', $fixtureImgClass);
    $fixtureInclude = $this->randomRegex();
    $this->setUpD6D7Variable('extlink_include', $fixtureInclude);
    $fixtureLabel = $this->randomString();
    $this->setUpD6D7Variable('extlink_label', $fixtureLabel);
    $fixtureMailtoClass = $this->randomString();
    $this->setUpD6D7Variable('extlink_mailto_class', $fixtureMailtoClass);
    $fixtureMailtoLabel = $this->randomString();
    $this->setUpD6D7Variable('extlink_mailto_label', $fixtureMailtoLabel);
    $fixtureSubdomains = $this->randomBoolean();
    $this->setUpD6D7Variable('extlink_subdomains', $fixtureSubdomains);
    $fixtureTarget = $this->randomBoolean();
    $this->setUpD6D7Variable('extlink_target', $fixtureTarget);
    $fixtureUseFontAwesome = $this->randomBoolean();
    $this->setUpD6D7Variable('extlink_use_font_awesome', $fixtureUseFontAwesome);

    // Run the migration.
    $this->executeMigrations([self::MIGRATION_UNDER_TEST]);

    // Verify the variables with migrations are now present in the destination
    // site.
    $config = $this->config('extlink.settings');
    $this->assertSame($fixtureAlert, $config->get('extlink_alert'));
    $this->assertSame($fixtureAlertText, $config->get('extlink_alert_text'));
    $this->assertSame($fixtureClass, $config->get('extlink_class'));
    $this->assertSame($fixtureCssExclude, $config->get('extlink_css_exclude'));
    $this->assertSame($fixtureCssExplicit, $config->get('extlink_css_explicit'));
    $this->assertSame($fixtureExclude, $config->get('extlink_exclude'));
    $this->assertSame($fixtureIconPlacement, $config->get('extlink_icon_placement'));
    $this->assertSame($fixtureImgClass, $config->get('extlink_img_class'));
    $this->assertSame($fixtureInclude, $config->get('extlink_include'));
    $this->assertSame($fixtureLabel, $config->get('extlink_label'));
    $this->assertSame($fixtureMailtoClass, $config->get('extlink_mailto_class'));
    $this->assertSame($fixtureMailtoLabel, $config->get('extlink_mailto_label'));
    $this->assertSame($fixtureSubdomains, $config->get('extlink_subdomains'));
    $this->assertSame($fixtureTarget, $config->get('extlink_target'));
    $this->assertSame($fixtureUseFontAwesome, $config->get('extlink_use_font_awesome'));

    // Verify the settings with no source-site equivalent are set to their
    // default values in the destination site.
    $this->assertFalse($config->get('extlink_exclude_admin_routes'));
    $this->assertFalse($config->get('extlink_follow_no_override'));
    $this->assertSame([], $config->get('extlink_font_awesome_classes'));
    $this->assertFalse($config->get('extlink_nofollow'));
    $this->assertTrue($config->get('extlink_noreferrer'));
    $this->assertFalse($config->get('extlink_target_no_override'));
    $this->assertFalse($config->get('extlink_use_external_js_file'));
    $this->assertSame([], $config->get('whitelisted_domains'));
  }

}
