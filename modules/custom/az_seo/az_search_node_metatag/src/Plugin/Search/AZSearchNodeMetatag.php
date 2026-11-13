<?php

namespace Drupal\az_search_node_metatag\Plugin\Search;

use Drupal\search_node\Plugin\Search\SearchNode;
use Drupal\metatag\MetatagManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\search\Attribute\Search;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities while respecting metatags.
 */
#[Search(
  id: 'az_search_node_metatag',
  title: new TranslatableMarkup('Quickstart Content with Metatags'),
)]
class AZSearchNodeMetatag extends SearchNode {

  /**
   * The MetatagManager. MetatagManagerInterface does not have APIs we need.
   */
  protected ?MetatagManager $metatagManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->metatagManager = $container->get('metatag.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function indexNode(NodeInterface $node): array {
    // Render the metatag tokens for the provided entity.
    $tags = $this->metatagManager->tagsFromEntityWithDefaults($node);
    $tokens = $this->metatagManager->generateTokenValues($tags, $node);
    $robots = $tokens['robots'] ?? '';
    // Do not index the content if it contains a robots noindex tag.
    if (is_string($robots) && str_contains($robots, 'noindex')) {
      // Index no words for something tagged robots noindex.
      return [];
    }

    // Otherwise, return parent implementation.
    return parent::indexNode($node);
  }

}
