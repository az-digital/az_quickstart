<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests workbench_access integration with entity_test.
 *
 * @group workbench_access
 */
class EntityTestAccessTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UiHelperTrait;
  use UserCreationTrait;
  use WorkbenchAccessTestTrait;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Access control scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'text',
    'system',
    'user',
    'workbench_access',
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
    $this->installEntitySchema('entity_test');
    entity_test_create_bundle('access_controlled');
    entity_test_create_bundle('not_access_controlled');
    $this->installConfig(['filter', 'entity_test', 'workbench_access']);
    $this->installEntitySchema('access_scheme');
    $this->scheme = AccessScheme::create([
      'id' => 'editorial_section',
      'label' => 'Editorial section',
      'plural_label' => 'Editorial sections',
      'scheme' => 'taxonomy',
      'scheme_settings' => [
        'vocabularies' => ['workbench_access'],
        'fields' => [
          [
            'entity_type' => 'entity_test',
            'bundle' => 'access_controlled',
            'field' => WorkbenchAccessManagerInterface::FIELD_NAME,
          ],
        ],
      ],
    ]);
    $this->scheme->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['sequences']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('entity_test');
    $this->setUpTaxonomyFieldForEntityType('entity_test', 'access_controlled', $this->vocabulary->id());
    $this->userStorage = \Drupal::service('workbench_access.user_section_storage');
  }

  /**
   * Test create access integration.
   */
  public function testCreateAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create a section.
    $term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some section',
    ]);
    $term->save();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'administer entity_test content',
      'view test entity',
    ];
    $allowed_editor = $this->createUser($permissions);
    $allowed_editor->save();
    $this->userStorage->addUser($this->scheme, $allowed_editor, [$term->id()]);

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
    // Create a section.
    $term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some section',
    ]);
    $term->save();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'administer entity_test content',
      'view test entity',
    ];
    $allowed_editor = $this->createUser($permissions);
    $allowed_editor->save();
    $this->userStorage->addUser($this->scheme, $allowed_editor, [$term->id()]);

    $editor_with_no_access = $this->createUser($permissions);

    // Test an entity that is not subject to access control.
    $entity = EntityTest::create([
      'type' => 'not_access_controlled',
      'name' => 'come on in',
    ]);
    $this->assertTrue($this->accessHandler->access($entity, 'update', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($entity, 'update', $editor_with_no_access));

    // Test an entity that is not assigned to a section. Both should be allowed
    // because we do not assert access control by default.
    $entity1 = EntityTest::create([
      'type' => 'access_controlled',
      'name' => 'come on in',
    ]);
    $this->assertTrue($this->accessHandler->access($entity1, 'update', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($entity1, 'update', $editor_with_no_access));

    // Create an entity that is assigned to a section.
    $entity2 = EntityTest::create([
      'type' => 'access_controlled',
      'name' => 'restricted',
      WorkbenchAccessManagerInterface::FIELD_NAME => $term->id(),
    ]);
    $this->assertTrue($this->accessHandler->access($entity2, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($entity2, 'update', $editor_with_no_access));

    // With strict checking, entities that are not assigned to a section return
    // false.
    $this->config('workbench_access.settings')
      ->set('deny_on_empty', 1)
      ->save();

    // Test a new entity because the results for $entity1 are cached.
    $entity3 = EntityTest::create([
      'type' => 'access_controlled',
      'name' => 'restricted',
    ]);
    $this->assertFalse($this->accessHandler->access($entity3, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($entity3, 'update', $editor_with_no_access));

    // Delete the scheme.
    $this->scheme->delete();
    // Should now allow access.
    $this->accessHandler->resetCache();
    $this->assertTrue($this->accessHandler->access($entity2, 'update', $editor_with_no_access));
  }

}
