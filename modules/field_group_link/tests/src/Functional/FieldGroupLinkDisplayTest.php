<?php

namespace Drupal\Tests\field_group_link\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests for displaying entities.
 *
 * @group field_group_link
 */
class FieldGroupLinkDisplayTest extends BrowserTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_test',
    'field_ui',
    'field_group',
    'field_group_test',
    'field_group_link',
    'link',
  ];

  /**
   * The node type id.
   *
   * @var string
   */
  protected $type;

  /**
   * A node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * A referenced node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $referencedNode;

  /**
   * The test field value.
   *
   * @var string
   */
  protected $testFieldValue;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->id();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node' . '.' . $type_name . '.' . 'default');

    // Create a node.
    $node_values = array('type' => $type_name);

    $this->setupFields();

    // Assign a test value for the field.
    $this->testFieldValue = mt_rand(1, 127);
    $node_values['field_test'][0]['value'] = $this->testFieldValue;

    // Set the field visible on the display object.
    $display_options = array(
      'label' => 'above',
      'type' => 'field_test_default',
      'settings' => array(
        'test_formatter_setting' => $this->randomMachineName(),
      ),
    );
    $display->setComponent('field_test', $display_options);

    $this->referencedNode = $this->drupalCreateNode(['type' => $type_name]);
    $node_values['field_test_entity_reference'][0]['target_id'] = $this->referencedNode->id();

    $node_values['field_test_link'][0] = [
      'uri' => 'internal:/node/' . $this->referencedNode->id(),
    ];

    // Save display + create node.
    $display->save();
    $this->node = $this->drupalCreateNode($node_values);

  }

  /**
   * Add fields to our content type.
   */
  private function setupFields() {
    $fields = [
      [
        'field_name' => 'field_test',
        'type' => 'test_field',
      ],
      [
        'field_name' => 'field_test_entity_reference',
        'type' => 'entity_reference',
        'settings' => [
          'target_type' => 'node',
        ],
      ],
      [
        'field_name' => 'field_test_link',
        'type' => 'link',
      ],
    ];

    foreach ($fields as $field_config) {
      $field_config_default = [
        'entity_type' => 'node',
        'translatable' => FALSE,
        'cardinality' => 1,
      ];

      $field_storage = FieldStorageConfig::create($field_config + $field_config_default);
      $field_storage->save();

      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $this->type,
        'label' => $this->randomMachineName(),
      ]);
      $instance->save();
    }
  }

  /**
   * Test the custom Uri link type.
   */
  public function testCustomUri() {
    $data = array(
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
        1 => 'body',
      ),
      'label' => 'Test Link',
      'format_type' => 'link',
      'format_settings' => array(
        'target' => 'custom_uri',
        'custom_uri' => '[site:url]',
        'classes' => 'test-link-class',
      ),
    );
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->elementContains('css', '.test-link-class', $this->testFieldValue);
    $this->click('.test-link-class');
    $this->assertSession()->addressEquals(Url::fromRoute('<front>', [], ['absolute' => TRUE]));
  }

  /**
   * Test the entity reference link type.
   */
  public function testEntityReference() {
    $data = array(
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
        1 => 'body',
      ),
      'label' => 'Test Link',
      'format_type' => 'link',
      'format_settings' => array(
        'target' => 'field_test_entity_reference',
        'classes' => 'test-link-class',
      ),
    );
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->elementContains('css', '.test-link-class', $this->testFieldValue);

    // Test group ids and classes.
    $this->click('.test-link-class');
    $this->assertSession()->addressEquals($this->referencedNode->toUrl('canonical', ['absolute' => TRUE]));
  }

  /**
   * Test the link module field type.
   */
  public function testLinkField() {
    $data = array(
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
        1 => 'body',
      ),
      'label' => 'Test Link',
      'format_type' => 'link',
      'format_settings' => array(
        'target' => 'field_test_link',
        'classes' => 'test-link-class',
      ),
    );
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->click('.test-link-class');
    $this->assertSession()->addressEquals($this->referencedNode->toUrl('canonical', ['absolute' => TRUE]));
  }

  /**
   * Test linking to the entity itself.
   */
  public function testEntityLink() {
    $data = array(
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
        1 => 'body',
      ),
      'label' => 'Test Link',
      'format_type' => 'link',
      'format_settings' => array(
        'target' => 'entity',
        'classes' => 'test-link-class',
      ),
    );
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->click('.test-link-class');
    $this->assertSession()->addressEquals($this->node->toUrl('canonical', ['absolute' => TRUE]));
  }

}
