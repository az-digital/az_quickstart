<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a class for testing the UI to create and configure schemes.
 *
 * @group workbench_access
 */
class MenuSchemeUITest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'node',
    'menu_link_content',
    'menu_ui',
    'link',
    'options',
    'user',
    'block',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->admin = $this->setUpAdminUser(['administer workbench access']);
    $this->placeBlock('local_actions_block');
  }

  /**
   * Tests scheme UI.
   */
  public function testSchemeUi() {
    $this->assertThatUnprivilegedUsersCannotAccessAdminPages();
    $scheme = $this->assertCreatingAnAccessSchemeAsAdmin('menu', $this->admin);
    $this->assertAdminCanSelectMenus($scheme);
    $this->assertAdminCanAddNodeTypes($scheme);
    $this->assertSectionsOperation($scheme);
  }

  /**
   * Assert admin can select menus.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  public function assertAdminCanSelectMenus(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[menus][main]' => 1,
    ], 'Save');
    $updated = $this->loadUnchangedScheme($scheme->id());
    $this->assertEquals(['main'], $updated->getAccessScheme()->getConfiguration()['menus']);
  }

  /**
   * Assert admin can add node types.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCanAddNodeTypes(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[bundles][page]' => 1,
      'scheme_settings[bundles][article]' => 1,
    ], 'Save');
    $updated = $this->loadUnchangedScheme($scheme->id());
    $this->assertTrue($updated->getAccessScheme()->applies('node', 'page'));
    $this->assertTrue($updated->getAccessScheme()->applies('node', 'article'));
  }

  /**
   * Asserts there is an operations link for sections in a scheme.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   The scheme.
   */
  protected function assertSectionsOperation(AccessSchemeInterface $scheme) {
    $this->drupalGet(Url::fromRoute('entity.access_scheme.collection'));
    $assert = $this->assertSession();
    $assert->linkExists('Sections');
    $assert->linkByHrefExists($scheme->toUrl('sections')->toString());
  }

}
