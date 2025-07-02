<?php

namespace Drupal\Tests\chosen\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests that multivalue select fields are handled properly.
 *
 * @group chosen
 */
class MultivalueTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['chosen', 'options', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Enable chosen for all multiselect fields.
    $this->container->get('config.factory')
      ->getEditable('chosen.settings')
      ->set('minimum_multiple', 0)
      ->save();

    // Add an 'article' content type.
    $this->createContentType(['type' => 'article']);

    // Login an admin user.
    $user = $this->drupalCreateUser(['access content', 'bypass node access']);
    $this->drupalLogin($user);

    // Add a multiple select field.
    $storage = FieldStorageConfig::create([
      'type' => 'list_string',
      'entity_type' => 'node',
      'field_name' => 'test_multiselect',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $storage->setSetting('allowed_values', [
      'one' => 'One',
      'two' => 'Two',
    ]);
    $storage->save();
    $field = FieldConfig::create([
      'field_name' => 'test_multiselect',
      'bundle' => 'article',
      'entity_type' => 'node',
    ]);
    $field->save();

    // Try loading the entity from configuration.
    $entity_form_display = EntityFormDisplay::load('node' . '.' . 'article' . '.' . 'default');

    // If not found, create a fresh entity object. We do not preemptively create
    // new entity form display configuration entries for each existing entity type
    // and bundle whenever a new form mode becomes available. Instead,
    // configuration entries are only created when an entity form display is
    // explicitly configured and saved.
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $entity_form_display->setComponent('test_multiselect', ['type' => 'options_select'])
      ->save();
  }

  /**
   * Tests that the _none option is removed.
   */
  public function testNoneOption() {
    $this->drupalGet('node/add/article');
    $this->assertSession()->responseNotContains('_none');
  }

}
