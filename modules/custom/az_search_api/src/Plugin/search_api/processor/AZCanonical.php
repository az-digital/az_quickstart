<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag\MetatagManager;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Skip indexing content that is noncanonical.
 */
#[SearchApiProcessor(
  id: 'az_canonical',
  label: new TranslatableMarkup('Include only canonical items'),
  description: new TranslatableMarkup('Include only items for which the content is the canonical version.'),
  stages: [
    'alter_items' => 0,
  ],
)]
class AZCanonical extends ProcessorPluginBase {

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
        // Get the canonical url from the metatag.
        $canonical_url = $tokens['canonical_url'] ?? '';
        // Compute the entity's actual url.
        $url = $entity->toUrl();
        $uri = $url->setOption('absolute', TRUE)->toString();
        // Do not index the content if the canonical url does not match.
        if (!empty($canonical_url) && ($uri !== $canonical_url)) {
          // @todo this logging message is only for testing. Remove before merge.
          \Drupal::logger('az_search_api')->notice(t("Not indexing @uri because of canonical @canonical_url", [
            '@uri' => $uri,
            '@canonical' => $canonical_url,
          ]));
          unset($items[$item_id]);
        }
      }
    }
  }

}
