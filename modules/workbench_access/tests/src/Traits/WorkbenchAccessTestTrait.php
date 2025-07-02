<?php

namespace Drupal\Tests\workbench_access\Traits;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Contains helper classes for tests to set up various configuration.
 *
 * Note that this Trait does not load additional Traits called from
 * BrowserTestBase. For Kernel tests, you will need to load those traits
 * (such as Drupal\Tests\user\Traits\UserCreationTrait) directly.
 */
trait WorkbenchAccessTestTrait {

  use EntityReferenceFieldCreationTrait;

  /**
   * Set up a content type with workbench access enabled.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The node type entity.
   */
  public function setUpContentType() {
    $node_type = $this->createContentType(['type' => 'page']);

    return $node_type;
  }

  /**
   * Create a test vocabulary.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary
   *   The vocabulary entity.
   */
  public function setUpVocabulary() {
    $vocab = Vocabulary::create([
      'vid' => 'workbench_access',
      'name' => 'Test Vocabulary',
    ]);
    $vocab->save();

    return $vocab;
  }

  /**
   * Sets up a taxonomy field on a given entity type and bundle.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   * @param string $vocabulary_id
   *   Vocabulary ID.
   * @param string $field_name
   *   Field machine name.
   * @param string $title
   *   Field display title.
   * @param int $cardinality
   *   Indicates the number of values to save. -1 is unlimited.
   * @param string $field_type
   *   The type of field widget to enable: options_select|options_buttons.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface|null
   *   The created field entity.
   */
  protected function setUpTaxonomyFieldForEntityType($entity_type_id, $bundle, $vocabulary_id, $field_name = WorkbenchAccessManagerInterface::FIELD_NAME, $title = 'Section', $cardinality = 1, $field_type = 'options_select') {
    // Create an instance of the access field on the bundle.
    $handler_id = 'workbench_access:taxonomy_term:editorial_section';
    if (!AccessScheme::load('editorial_section')) {
      // The scheme doesn't exist yet so there is no plugin yet.
      $handler_id = 'default:taxonomy_term';
    }
    $this->createEntityReferenceField($entity_type_id, $bundle, $field_name, $title, 'taxonomy_term', $handler_id, [
      'target_bundles' => [
        $vocabulary_id => $vocabulary_id,
      ],
    ], $cardinality);
    // Set the field to display as a dropdown on the form.
    if (!$form_display = EntityFormDisplay::load("$entity_type_id.$bundle.default")) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $form_display->setComponent($field_name, ['type' => $field_type]);
    $form_display->save();

    return FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
  }

  /**
   * Sets up a user with an editor role that has access to content.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function setUpEditorUser() {
    $editor_rid = $this->createRole([
      'access administration pages',
      'create page content',
      'edit any page content',
      'administer menu',
      'delete any page content',
      'use workbench access',
    ], 'editor');

    return $this->createUserWithRole($editor_rid);
  }

  /**
   * Sets up a user with an editor role that has access to content.
   *
   * @param array $additional_permissions
   *   Array of additional permissions beyond 'access administration pages' and
   *   'assign workbench access'.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  protected function setUpAdminUser(array $additional_permissions = []) {
    $admin_rid = $this->createRole(array_merge($additional_permissions, [
      'access administration pages',
      'assign workbench access',
    ]), 'admin');

    return $this->createUserWithRole($admin_rid);
  }

  /**
   * Sets up a user with a given role and saves it.
   *
   * @param string $rid
   *   The role id.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  protected function createUserWithRole($rid) {
    $user = $this->createUser();
    $user->addRole($rid);
    $user->save();
    return $user;
  }

  /**
   * Sets up a taxonomy scheme for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $node_type
   *   Node type to set it up for.
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocab
   *   The vocab to use for the scheme.
   * @param string $id
   *   Scheme ID.
   *
   * @return \Drupal\workbench_access\Entity\AccessSchemeInterface
   *   Created scheme.
   */
  public function setUpTaxonomyScheme(NodeType $node_type, Vocabulary $vocab, $id = 'editorial_section') {
    $scheme = AccessScheme::create([
      'id' => $id,
      'label' => ucfirst(str_replace('_', ' ', $id)),
      'plural_label' => ucfirst(str_replace('_', ' ', $id)) . 's',
      'scheme' => 'taxonomy',
      'scheme_settings' => [
        'vocabularies' => [$vocab->id()],
        'fields' => [
          [
            'entity_type' => 'node',
            'field' => WorkbenchAccessManagerInterface::FIELD_NAME,
            'bundle' => $node_type->id(),
          ],
        ],
      ],
    ]);
    $scheme->save();
    return $scheme;
  }

  /**
   * Sets up a menu scheme for a given node type.
   *
   * @param array $node_type_ids
   *   Node type.
   * @param array $menu_ids
   *   Menu IDs.
   * @param string $id
   *   Scheme ID.
   *
   * @return \Drupal\workbench_access\Entity\AccessSchemeInterface
   *   Created scheme.
   */
  public function setUpMenuScheme(array $node_type_ids, array $menu_ids, $id = 'editorial_section') {
    $scheme = AccessScheme::create([
      'id' => $id,
      'label' => ucfirst(str_replace('_', ' ', $id)),
      'plural_label' => ucfirst(str_replace('_', ' ', $id)) . 's',
      'scheme' => 'menu',
      'scheme_settings' => [
        'menus' => $menu_ids,
        'bundles' => $node_type_ids,
      ],
    ]);
    $scheme->save();
    return $scheme;
  }

  /**
   * Assert that unprivileged users cannot access admin pages.
   */
  protected function assertThatUnprivilegedUsersCannotAccessAdminPages() {
    $this->drupalGet(Url::fromRoute('entity.access_scheme.collection'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(403);
  }

  /**
   * Assert that admin can create an access scheme.
   *
   * @param string $scheme_id
   *   Scheme plugin ID.
   * @param \Drupal\Core\Session\AccountInterface|null $admin_user
   *   The existing admin user, if present, or FALSE.
   *
   * @return \Drupal\workbench_access\Entity\AccessSchemeInterface
   *   Created scheme.
   */
  protected function assertCreatingAnAccessSchemeAsAdmin($scheme_id = 'taxonomy', ?AccountInterface $admin_user = NULL) {
    if (!$admin_user) {
      $admin_user = $this->setUpAdminUser(['administer workbench access']);
    }
    $this->drupalLogin($admin_user);
    $this->drupalGet(Url::fromRoute('entity.access_scheme.collection'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $this->clickLink('Add Access scheme');
    $this->submitForm([
      'label' => 'Section',
      'plural_label' => 'Sections',
      'id' => 'editorial_section',
      'scheme' => $scheme_id,
    ], 'Save');
    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
    $scheme = $this->loadUnchangedScheme('editorial_section');
    $this->assertEquals('Section', $scheme->label());
    $this->assertEquals('Sections', $scheme->getPluralLabel());

    return $scheme;
  }

  /**
   * Loads the given scheme.
   *
   * @param string $scheme_id
   *   Scheme ID.
   *
   * @return \Drupal\workbench_access\Entity\AccessSchemeInterface
   *   Unchanged scheme.
   */
  protected function loadUnchangedScheme($scheme_id) {
    return $this->container->get('entity_type.manager')
      ->getStorage('access_scheme')
      ->loadUnchanged($scheme_id);
  }

  /**
   * Sets the field type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   * @param string $type
   *   The field type to use.
   * @param string $field_name
   *   Field machine name.
   */
  protected function setFieldType($entity_type_id, $bundle, $type = 'options_select', $field_name = WorkbenchAccessManagerInterface::FIELD_NAME) {
    // Set the field to display as a dropdown on the form.
    $form_display = EntityFormDisplay::load("$entity_type_id.$bundle.default");
    $form_display->setComponent($field_name, ['type' => $type]);
    $form_display->save();
  }

  /**
   * Checks if we are using Drupal 8 or 9.
   *
   * @return bool
   *   TRUE if Drupal 8, FALSE otherwise.
   */
  public function isDrupal8() {
    $version = (int) substr(\Drupal::VERSION, 0, 1);
    if ($version < 9) {
      return TRUE;
    }
    return FALSE;
  }

}
