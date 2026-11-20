<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag\MetatagManager;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Skip indexing content flagged as noindex.
 */
#[SearchApiProcessor(
  id: 'az_robots_noindex',
  label: new TranslatableMarkup('Robots noindex metatag'),
  description: new TranslatableMarkup('Respect robots noindex metatag settings for a given entity.'),
  stages: [
    'alter_items' => 0,
  ],
)]
class AZRobotsNoIndex extends ProcessorPluginBase {

  /**
   * The MetatagManager. MetatagManagerInterface does not have APIs we need.
   */
  protected ?MetatagManager $metatagManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->metatagManager = $container->get('metatag.manager');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()->getValue();
      if (!empty($entity)) {
        // Render the metatag tokens for the provided entity.
        $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
        $tokens = $this->metatagManager->generateTokenValues($tags, $entity);
        $robots = $tokens['robots'] ?? '';
        // Do not index the content if it contains a noindex robots tag.
        if (is_string($robots) && str_contains($robots, 'noindex')) {
          unset($items[$item_id]);
        }
      }
    }
  }

}
