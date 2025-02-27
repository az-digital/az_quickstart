<?php

namespace Drupal\az_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Plugin\Exception\ContextException;

/**
 * Provides a 'AZEventCalendarAddBlock' block.
 *
 * @Block(
 *  id = "az_event_calendar_add_block",
 *  admin_label = @Translation("Add to Calendar Block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class AZEventCalendarAddBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    try {
      $node = $this->getContextValue('node');
      if (!empty($node)) {
        if ($node->getType() === 'az_event') {
          $build['az_event_calendar_add'] = [
            '#theme' => 'az_event_calendar_add_block',
            '#title' => $node->getTitle(),
            '#start_date' => $node->field_az_event_date->value ?? '',
            '#end_date' => $node->field_az_event_date->end_value ?? '',
            '#description' => $node->field_az_body->value ?? '',
            '#location' => $node->field_az_location->title ?? '',
            '#modal' => Html::getUniqueId('calendar-link-modal'),
          ];
        }
      }
    }
    catch (ContextException $e) {
    }

    return $build;
  }

}
