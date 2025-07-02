<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for 'Webform Entity Reference formatter' plugin implementations.
 */
abstract class WebformEntityReferenceFormatterBase extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
    $instance->configFactory = $container->get('config.factory');
    $instance->renderer = $container->get('renderer');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = parent::getEntitiesToView($items, $langcode);
    foreach ($entities as $entity) {
      /** @var \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $item */
      $item = $entity->_referringItem;

      // Only override an open webform.
      if ($entity->isOpen()) {
        if (isset($item->open)) {
          $entity->set('open', $item->open);
        }
        if (isset($item->close)) {
          $entity->set('close', $item->close);
        }
        if (isset($item->status)) {
          $entity->setStatus($item->status);
        }
        // Directly call set override to prevent the altered webform from being
        // saved.
        if (isset($item->open) || isset($item->close) || isset($item->status)) {
          $entity->setOverride();
        }
      }
    }
    return $entities;
  }

  /**
   * Set cache context.
   *
   * @param array $elements
   *   The elements that need cache context.
   * @param \Drupal\webform\WebformInterface|null $webform
   *   The webform entity reference webform.
   * @param \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $item
   *   The webform entity reference item.
   */
  protected function setCacheContext(array &$elements, WebformInterface $webform, WebformEntityReferenceItem $item) {
    // Track if webform.settings is updated.
    $config = $this->configFactory->get('webform.settings');
    $this->renderer->addCacheableDependency($elements, $config);

    // Track if the webform is updated.
    $this->renderer->addCacheableDependency($elements, $webform);

    // Calculate the max-age based on the open/close data/time for the item
    // and webform.
    $max_age = 0;
    $states = ['open', 'close'];
    foreach ($states as $state) {
      if ($item->status === WebformInterface::STATUS_SCHEDULED) {
        $item_state = $item->$state;
        if ($item_state && strtotime($item_state) > $this->time->getRequestTime()) {
          $item_seconds = strtotime($item_state) - $this->time->getRequestTime();
          if (!$max_age && $item_seconds > $max_age) {
            $max_age = $item_seconds;
          }
        }
      }
      if ($webform->status() === WebformInterface::STATUS_SCHEDULED) {
        $webform_state = $webform->get($state);
        if ($webform_state && strtotime($webform_state) > $this->time->getRequestTime()) {
          $webform_seconds = strtotime($webform_state) - $this->time->getRequestTime();
          if (!$max_age && $webform_seconds > $max_age) {
            $max_age = $webform_seconds;
          }
        }
      }
    }

    if ($max_age) {
      $elements['#cache']['max-age'] = $max_age;
    }
  }

}
