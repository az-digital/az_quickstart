<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the JSON output from core works as intended.
 *
 * @group metatag
 */
class NodeJsonOutputTest extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // The modules to test.
    'serialization',
    'hal',
    'rest',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Create an entity, view its JSON output, confirm Metatag data exists.
   */
  public function testNode() {
    $this->provisionResource();

    $title = 'Test JSON output';
    $body = 'Testing JSON output for a content type';
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->createContentTypeNode($title, $body);
    $url = $node->toUrl();

    // Load the node's page.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Load the JSON output.
    $url->setOption('query', ['_format' => 'json']);
    $response = $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Decode the JSON output.
    $response = $this->getSession()->getPage()->getContent();
    $this->assertNotEmpty($response);
    $json = json_decode($response);
    $this->assertNotEmpty($json);

    // Confirm the JSON object's values.
    $this->assertTrue(isset($json->nid));
    if (isset($json->nid)) {
      $this->assertTrue($json->nid[0]->value == $node->id());
    }
    $this->assertTrue(isset($json->metatag));
    if (isset($json->metatag)) {
      // It is not clear what order the meta tags will be in, so loop over them
      // and check each item.
      $meta_tags_found = FALSE;
      foreach ($json->metatag as $tag) {
        // Title.
        if (isset($tag->tag, $tag->attributes->name) && $tag->attributes->name == 'title') {
          $this->assertEquals($tag->attributes->content, $title . ' | Drupal');
          $this->assertEquals($tag->attributes->content, $node->label() . ' | Drupal');
          $meta_tags_found = TRUE;
        }
        // Canonical URL tag.
        if (isset($tag->tag, $tag->attributes->rel) && $tag->attributes->rel == 'canonical') {
          $this->assertEquals($tag->attributes->href, $node->toUrl('canonical', ['absolute' => TRUE])->toString());
          $meta_tags_found = TRUE;
        }
        // Description.
        if (isset($tag->tag, $tag->attributes->name) && $tag->attributes->name == 'description') {
          $this->assertEquals($tag->attributes->content, $body);
          $meta_tags_found = TRUE;
        }
      }
      $this->assertEquals($meta_tags_found, TRUE);
    }
  }

  /**
   * Provisions the REST resource under test.
   *
   * @param string $entity_type
   *   The entity type to be enabled; defaults to 'node'.
   * @param array $formats
   *   The allowed formats for this resource; defaults to ['json'].
   * @param array $authentication
   *   The allowed authentication providers for this resource; defaults to
   *   ['basic_auth'].
   */
  protected function provisionResource($entity_type = 'node', array $formats = [], array $authentication = []): void {
    /** @var \Drupal\Core\Entity\EntityStorageInterface */
    $esource_config_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('rest_resource_config');

    // Defaults.
    if (empty($formats)) {
      $formats[] = 'json';
    }
    if (empty($authentication)) {
      $authentication[] = 'basic_auth';
    }

    $esource_config_storage->create([
      'id' => 'entity.' . $entity_type,
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
        'formats' => $formats,
        'authentication' => $authentication,
      ],
      'status' => TRUE,
    ])->save();

    // Ensure that the cache tags invalidator has its internal values reset.
    // Otherwise the http_response cache tag invalidation won't work.
    // Clear the tag cache.
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
    foreach (Cache::getBins() as $backend) {
      if (is_callable([$backend, 'reset'])) {
        $backend->reset();
      }
    }
    $this->container->get('config.factory')->reset();
    $this->container->get('state')->resetCache();

    // Tests using this base class may trigger route rebuilds due to changes to
    // RestResourceConfig entities or 'rest.settings'. Ensure the test generates
    // routes using an up-to-date router.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

}
