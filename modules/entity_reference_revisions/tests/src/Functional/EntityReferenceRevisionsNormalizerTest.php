<?php

namespace Drupal\Tests\entity_reference_revisions\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the entity_reference_revisions configuration.
 *
 * @group entity_reference_revisions
 * @requires module hal
 */
class EntityReferenceRevisionsNormalizerTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'block',
    'hal',
    'serialization',
    'rest',
  );

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    if (version_compare(\Drupal::VERSION, '10', '>=')) {
      $this->markTestSkipped('HAL support has been moved to hal module');
    }

    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(array('type' => 'entity_revisions', 'name' => 'Entity revisions'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the entity reference revisions configuration.
   */
  public function testEntityReferenceRevisions() {

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create article content',
      'create entity_revisions content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'edit any article content',
    ));
    $this->drupalLogin($admin_user);
    // Create entity reference revisions field.
    static::fieldUIAddNewField('admin/structure/types/manage/entity_revisions', 'entity_reference_revisions', 'Entity reference revisions', 'entity_reference_revisions', array('settings[target_type]' => 'node', 'cardinality' => '-1'), array('settings[handler_settings][target_bundles][article]' => TRUE));
    $this->assertSession()->pageTextContains('Saved Entity reference revisions configuration.');

    // Create an article.
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => 'Revision 1',
    );
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');
    $node = $this->drupalGetNodeByTitle($title);

    // Create entity revisions content that includes the above article.
    $err_title = 'Entity reference revision content';
    $edit = array(
      'title[0][value]' => $err_title,
      'field_entity_reference_revisions[0][target_id]' => $node->label() . ' (' . $node->id() . ')',
    );
    $this->drupalGet('node/add/entity_revisions');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Entity revisions Entity reference revision content has been created.');
    $err_node = $this->drupalGetNodeByTitle($err_title);

    $this->assertSession()->pageTextContains($err_title);
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');

    // Create 2nd revision of the article.
    $edit = array(
      'body[0][value]' => 'Revision 2',
      'revision' => TRUE,
    );
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $serializer = $this->container->get('serializer');
    $normalized = $serializer->normalize($err_node, 'hal_json');
    $request = \Drupal::request();
    $link_domain = $request->getSchemeAndHttpHost() . $request->getBasePath();
    $this->assertEquals($err_node->field_entity_reference_revisions->target_revision_id, $normalized['_embedded'][$link_domain . '/rest/relation/node/entity_revisions/field_entity_reference_revisions'][0]['target_revision_id']);
    $new_err_node = $serializer->denormalize($normalized, Node::class, 'hal_json');
    $this->assertEquals($err_node->field_entity_reference_revisions->target_revision_id, $new_err_node->field_entity_reference_revisions->target_revision_id);
  }

}
