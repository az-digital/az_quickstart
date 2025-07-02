<?php

namespace Drupal\asset_injector;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Asset Injector entities.
 */
class AssetInjectorListBuilder extends ConfigEntityListBuilder {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RendererInterface $renderer) {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Injector');
    $header['conditions'] = $this->t('Conditions');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $data['label'] = $entity->label();

    $data['conditions'] = [];

    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    foreach ($entity->getConditionsCollection() as $condition_id => $condition) {
      if ($condition_id == 'current_theme') {
        $config = $condition->getConfiguration();
        if (isset($config['theme']) && is_array($config['theme'])) {
          $condition->setConfiguration(['theme' => implode(', ', $config['theme'] ?: [])] + $config);
        }
      }

      $data['conditions'][$condition_id] = $this->t('%plugin is configured.', ['%plugin' => $condition->getPluginDefinition()['label']]);
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $summary */
      if ($summary = $condition->summary()) {
        $data['conditions'][$condition_id] = is_string($summary) ? Html::decodeEntities($summary) : Html::decodeEntities($summary->render());

      }
    }

    $data['conditions'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => empty($data['conditions']) ? [$this->t('Global')] : $data['conditions'],
    ];
    $data['conditions'] = $this->renderer->render($data['conditions']);

    $data['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    $row = [
      'class' => $entity->status() ? 'enabled' : 'disabled',
      'data' => $data + parent::buildRow($entity),
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('duplicate-form')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 15,
        'url' => $entity->toUrl('duplicate-form'),
      ];
    }

    // Only alter edit link if we have access.
    if (isset($operations['edit']['url'])) {
      // Remove the query option to allow the "save and continue" functionality
      // to work correctly.
      $options = $operations['edit']['url']->getOptions();
      unset($options['query']);
      $operations['edit']['url']->setOptions($options);
    }
    return $operations;
  }

}
