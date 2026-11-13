<?php

namespace Drupal\Tests\az_webform\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests az_webform's changes to the az_linkit profile on install/uninstall.
 */
#[Group('az_webform')]
#[RunTestsInSeparateProcesses]
class AZWebformLinkitProfileTest extends KernelTestBase {

  /**
   * The config name of the Linkit profile altered by az_webform.
   */
  protected const PROFILE = 'linkit.linkit_profile.az_linkit';

  /**
   * The matcher UUIDs added by az_webform's override file.
   */
  protected const WEBFORM_MATCHERS = [
    '167a2cef-82fa-4ae2-88ef-e1005aa7e5da' => 'entity:node',
    '6eee3301-f4d9-4787-9659-9cee0ea6ca70' => 'entity:webform',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   *
   * Some contrib config installed by az_core, e.g. smart_title.settings, has
   * no schema.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    // Quickstart modules live in the install profile, so it must be active for
    // them to stay discoverable after the module list is rebuilt.
    $this->setInstallProfile('az_quickstart');
    // az_core's default config depends on modules it doesn't declare as
    // dependencies but that are always present on Quickstart sites.
    $this->container->get('module_installer')->install([
      'block_content',
      'datetime',
      'file',
      'image',
      'link',
      'media',
      'node',
      'views',
    ]);
    $this->container->get('module_installer')->install(['az_core']);
  }

  /**
   * Tests that installing and uninstalling az_webform is reversible.
   */
  public function testInstallUninstall(): void {
    $original = $this->container->get('config.factory')->get(self::PROFILE)->getRawData();
    $this->assertNotEmpty($original, 'The az_linkit profile is installed by az_core.');
    foreach (array_keys(self::WEBFORM_MATCHERS) as $uuid) {
      $this->assertArrayNotHasKey($uuid, $original['matchers']);
    }

    // Installing az_webform adds the webform matchers and dependencies while
    // keeping the profile's existing ones.
    $this->container->get('module_installer')->install(['az_webform']);
    $installed = $this->container->get('config.factory')->get(self::PROFILE)->getRawData();

    foreach (self::WEBFORM_MATCHERS as $uuid => $id) {
      $this->assertSame($id, $installed['matchers'][$uuid]['id'] ?? NULL);
    }
    foreach ($original['matchers'] as $uuid => $matcher) {
      $this->assertSame($matcher, $installed['matchers'][$uuid]);
    }

    $expected_dependencies = $original['dependencies'];
    $expected_dependencies['module'][] = 'webform';
    sort($expected_dependencies['module']);
    $this->assertSame($expected_dependencies, $installed['dependencies']);

    // Uninstalling az_webform restores the profile's original matchers and
    // dependencies.
    $this->container->get('module_installer')->uninstall(['az_webform']);
    $uninstalled = $this->container->get('config.factory')->get(self::PROFILE)->getRawData();
    $this->assertSame($original['matchers'], $uninstalled['matchers']);
    $this->assertSame($original['dependencies'], $uninstalled['dependencies']);
  }

  /**
   * Tests that uninstall keeps changes a site builder made to the profile.
   */
  public function testUninstallKeepsSiteChanges(): void {
    $original = $this->container->get('config.factory')->get(self::PROFILE)->getRawData();
    $this->container->get('module_installer')->install(['az_webform']);

    // Change the label and add a webform matcher of the site's own.
    $storage = $this->container->get('entity_type.manager')->getStorage('linkit_profile');
    /** @var \Drupal\linkit\ProfileInterface $profile */
    $profile = $storage->load('az_linkit');
    $profile->set('label', 'Changed label');
    $custom_uuid = $profile->addMatcher([
      'id' => 'entity:webform',
      'weight' => 5,
      'settings' => [
        'metadata' => '',
        'limit' => 10,
        'substitution_type' => 'canonical',
      ],
    ]);
    $profile->save();

    $this->container->get('module_installer')->uninstall(['az_webform']);
    $uninstalled = $this->container->get('config.factory')->get(self::PROFILE)->getRawData();

    // The site's changes are kept and az_webform's matchers are removed.
    $this->assertSame('Changed label', $uninstalled['label']);
    $this->assertSame('entity:webform', $uninstalled['matchers'][$custom_uuid]['id'] ?? NULL);
    foreach (array_keys(self::WEBFORM_MATCHERS) as $uuid) {
      $this->assertArrayNotHasKey($uuid, $uninstalled['matchers']);
    }

    // The site's webform matcher still needs the webform dependency.
    $expected_dependencies = $original['dependencies'];
    $expected_dependencies['module'][] = 'webform';
    sort($expected_dependencies['module']);
    $this->assertSame($expected_dependencies, $uninstalled['dependencies']);
  }

}
