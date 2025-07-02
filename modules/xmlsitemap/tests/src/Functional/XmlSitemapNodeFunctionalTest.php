<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of node links.
 *
 * @group xmlsitemap
 */
class XmlSitemapNodeFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * Nodes created during the test for testCron() method.
   *
   * @var array
   */
  protected $nodes = [];

  /**
   * Entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->admin_user = $this->drupalCreateUser([
      'administer nodes',
      'bypass node access',
      'administer content types',
      'administer xmlsitemap',
      'administer taxonomy',
    ]);
    $this->normal_user = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'access content',
      'view own unpublished content',
    ]);
    $this->override_user = $this->drupalCreateUser([
      'create page content',
      'edit any page content',
      'access content',
      'view own unpublished content',
      'override xmlsitemap link settings',
    ]);

    // Allow anonymous user to view user profiles.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('access content');
    $user_role->save();

    xmlsitemap_link_bundle_enable('node', 'article');
    xmlsitemap_link_bundle_enable('node', 'page');
    xmlsitemap_link_bundle_settings_save('node', 'page', [
      'status' => 1,
      'priority' => 0.6,
      'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY,
    ]);

    // Add a vocabulary so we can test different view modes.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'description' => $this->randomMachineName(),
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
    ]);
    $vocabulary->save();

    xmlsitemap_link_bundle_enable('taxonomy_term', 'tags');
    // Set up a field and instance.
    $field_name = 'tags';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    EntityFormDisplay::load('node.page.default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();

    // Show on default display and teaser.
    EntityViewDisplay::load('node.page.default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ])
      ->save();
    EntityViewDisplay::load('node.page.teaser')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Test Tags Field.
   */
  public function testTagsField() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('node/add/page');
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';

    // Fill in node creation form and preview node.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit['tags[target_id]'] = 'tag1, tag2, tag3';
    $edit['status[value]'] = TRUE;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, t('Save'));

    $tags = Term::loadMultiple();
    foreach ($tags as $tag) {
      $this->assertSitemapLinkValues('taxonomy_term', $tag->id(), [
        'status' => 0,
        'priority' => 0.5,
        'changefreq' => 0,
      ]);
      $tag->delete();
    }

    xmlsitemap_link_bundle_settings_save('taxonomy_term', 'tags', [
      'status' => 1,
      'priority' => 0.2,
      'changefreq' => XMLSITEMAP_FREQUENCY_HOURLY,
    ]);
    $this->drupalGet('node/add/page');

    $this->submitForm($edit, t('Save'));

    $tags = Term::loadMultiple();
    foreach ($tags as $tag) {
      $this->assertSitemapLinkValues('taxonomy_term', $tag->id(), [
        'status' => 1,
        'priority' => 0.2,
        'changefreq' => XMLSITEMAP_FREQUENCY_HOURLY,
      ]);
      $tag->delete();
    }
  }

  /**
   * Test Node Settings.
   */
  public function testNodeSettings() {
    $node = $this->drupalCreateNode(['publish' => 0, 'uid' => $this->normal_user->id()]);
    $this->assertSitemapLinkValues('node', $node->id(), [
      'access' => 1,
      'status' => 1,
      'priority' => 0.6,
      'status_override' => 0,
      'priority_override' => 0,
      'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY,
    ]);

    $this->drupalLogin($this->normal_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldNotExists('xmlsitemap[status]');
    $this->assertSession()->fieldNotExists('xmlsitemap[priority]');

    $edit = [
      'title[0][value]' => 'Test node title',
      'body[0][value]' => 'Test node body',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), [
      'access' => 1,
      'status' => 1,
      'priority' => 0.6,
      'status_override' => 0,
      'priority_override' => 0,
      'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY,
    ]);

    $this->drupalLogin($this->override_user);

    // Test fields are visible on the node add form.
    $this->drupalGet('node/add/page');
    $this->assertSession()->fieldExists('xmlsitemap[status]');
    $this->assertSession()->fieldExists('xmlsitemap[priority]');
    $this->assertSession()->fieldExists('xmlsitemap[changefreq]');

    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.9,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_ALWAYS,
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), [
      'access' => 1,
      'status' => 1,
      'priority' => 0.9,
      'status_override' => 1,
      'priority_override' => 1,
      'changefreq' => XMLSITEMAP_FREQUENCY_ALWAYS,
    ]);

    $edit = [
      'xmlsitemap[status]' => 'default',
      'xmlsitemap[priority]' => 'default',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('Basic page Test node title has been updated.');
    $this->assertSitemapLinkValues('node', $node->id(), [
      'access' => 1,
      'status' => 1,
      'priority' => 0.6,
      'status_override' => 0,
      'priority_override' => 0,
    ]);
  }

  /**
   * Test the content type settings.
   */
  public function testTypeSettings() {
    $this->drupalLogin($this->admin_user);

    $node_old = $this->drupalCreateNode();
    $this->assertSitemapLinkValues('node', $node_old->id(), [
      'status' => 1,
      'priority' => 0.6,
      'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY,
    ]);

    $edit = [
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => '0.0',
    ];
    $this->drupalGet('admin/config/search/xmlsitemap/settings/node/page');
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $node = $this->drupalCreateNode();
    $this->assertSitemapLinkValues('node', $node->id(), ['status' => 0, 'priority' => 0.0]);
    $this->assertSitemapLinkValues('node', $node_old->id(), ['status' => 0, 'priority' => 0.0]);

    // Delete all pages in order to allow content type deletion.
    $node->delete();
    $node_old->delete();
    $this->drupalGet('admin/structure/types/manage/page/delete');

    $this->submitForm([], t('Delete'));
    $this->assertSession()->pageTextContains('The content type Basic page has been deleted.');
    $this->assertEmpty($this->linkStorage->loadMultiple(['type' => 'node', 'subtype' => 'page']), 'Nodes with deleted node type removed from {xmlsitemap}.');
  }

  /**
   * Test the import of old nodes via cron.
   */
  public function testCron() {
    $limit = 5;
    $this->config->set('batch_limit', $limit)->save();

    $nodes = [];
    for ($i = 1; $i <= ($limit + 1); $i++) {
      $node = $this->drupalCreateNode();
      array_push($nodes, $node);
      // Need to delay by one second so the nodes don't all have the same
      // timestamp.
      sleep(1);
    }

    // Clear all the node link data so we can emulate 'old' nodes.
    \Drupal::database()->delete('xmlsitemap')
      ->condition('type', 'node')
      ->execute();

    // Run cron to import old nodes.
    xmlsitemap_cron();

    for ($i = 1; $i <= ($limit + 1); $i++) {
      $node = array_pop($nodes);
      if ($i != 1) {
        // The first $limit nodes should be inserted.
        $this->assertSitemapLinkValues('node', $node->id(), ['access' => 1, 'status' => 1]);
      }
      else {
        // Any beyond $limit should not be in the sitemap.
        $this->assertNoSitemapLink(['type' => 'node', 'id' => $node->id()]);
      }
    }
  }

}
