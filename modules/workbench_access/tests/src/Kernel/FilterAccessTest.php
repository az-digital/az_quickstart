<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Entity\AccessScheme;

/**
 * Tests workbench_access integration with entity_test.
 *
 * @group workbench_access
 */
class FilterAccessTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UiHelperTrait;
  use UserCreationTrait;
  use WorkbenchAccessTestTrait;

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat1;

  /**
   * Filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat2;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'text',
    'system',
    'user',
    'entity_test',
    'workbench_access',
    'workbench_access_filter_test',
    'field',
    'filter',
    'taxonomy',
    'options',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['filter', 'entity_test', 'workbench_access']);
    $this->scheme = AccessScheme::create([
      'id' => 'editorial_section',
      'label' => 'Editorial section',
      'plural_label' => 'Editorial sections',
      'scheme' => 'workbench_access_filter_test',
      'scheme_settings' => [],
    ]);
    $this->scheme->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['sequences']);
    $this->accessHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('filter_format');

    $this->filterFormat1 = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);

    $this->filterFormat2 = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => 1],
      ],
    ]);
  }

  /**
   * Test create access integration.
   */
  public function testCreateAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'administer filters',
    ];
    $allowed_editor = $this->createUser($permissions);
    $this->container->get('workbench_access.user_section_storage')->addUser($this->scheme, $allowed_editor, ['filter_html_escape']);
    $allowed_editor->save();
    $editor_with_no_access = $this->createUser($permissions);
    $permissions[] = 'bypass workbench access';
    $editor_with_bypass_access = $this->createUser($permissions);

    $this->assertTrue($this->accessHandler->createAccess('access_controlled', $allowed_editor));
    $this->assertFalse($this->accessHandler->createAccess('access_controlled', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->createAccess('access_controlled', $editor_with_bypass_access));
  }

  /**
   * Test edit access integration.
   */
  public function testEditAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'administer filters',
    ];
    $allowed_editor = $this->createUser($permissions);
    $this->container->get('workbench_access.user_section_storage')->addUser($this->scheme, $allowed_editor, ['filter_html_escape']);
    $allowed_editor->save();
    $editor_with_no_access = $this->createUser($permissions);

    // Test an entity that is not assigned to a section. Both should be allowed
    // because we do not assert access control by default.
    $this->assertTrue($this->accessHandler->access($this->filterFormat1, 'update', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($this->filterFormat1, 'update', $editor_with_no_access));

    // Create an entity that is assigned to a section.
    $this->assertTrue($this->accessHandler->access($this->filterFormat2, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($this->filterFormat2, 'update', $editor_with_no_access));

    // With strict checking, entities that are not assigned to a section return
    // false.
    $this->config('workbench_access.settings')
      ->set('deny_on_empty', 1)
      ->save();
    $this->accessHandler->resetCache();
    $this->assertFalse($this->accessHandler->access($this->filterFormat1, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($this->filterFormat1, 'update', $editor_with_no_access));
  }

}
