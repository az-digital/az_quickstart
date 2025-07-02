<?php

namespace Drupal\Tests\webform\Functional\Paragraphs;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform states hidden.
 *
 * @group webform
 */
abstract class WebformParagraphsTestBase extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_test_paragraphs'];

  /**
   * Cache paragraphs nodes.
   *
   * @var array
   */
  protected $nodes;

  /**
   * Get a paragraph node by title.
   *
   * @param string $title
   *   A node title.
   *
   * @return Drupal\node\NodeInterface
   *   A paragraph node.
   */
  public function getNode($title) {
    if (!isset($this->nodes)) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      /** @var \Drupal\node\NodeInterface[] $nodes */
      $nodes = $node_storage->loadByProperties(['type' => 'webform_test_paragraphs']);
      foreach ($nodes as $entity) {
        $this->nodes[$entity->label()] = $entity;
      }
    }
    return $this->nodes[$title];
  }

}
