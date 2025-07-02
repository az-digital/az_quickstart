<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\views\area;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\google_tag\EventCollectorInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View area plugin.
 *
 * @ViewsArea("commerce_product_view_item_list")
 */
final class CommerceProductViewItemList extends AreaPluginBase {

  /**
   * Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $eventCollector;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->eventCollector = $container->get('google_tag.event_collector');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function render($empty = FALSE): array {
    if (!$empty || !empty($this->options['empty'])) {
      $items = [];
      foreach ($this->view->result as $result) {
        if ($result->_entity instanceof ProductVariationInterface) {
          $items[] = $result->_entity;
        }
        elseif ($result->_entity instanceof ProductInterface) {
          $items[] = $result->_entity->getDefaultVariation();
        }
      }
      $this->eventCollector->addEvent('commerce_view_item_list', [
        'item_list_id' => $this->view->id(),
        'item_list_name' => $this->view->getTitle(),
        'items' => $items,
      ]);
    }

    return [];
  }

}
