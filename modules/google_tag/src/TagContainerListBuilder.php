<?php

namespace Drupal\google_tag;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\google_tag\Entity\TagContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a listing of tag container configuration entities.
 *
 * @see \Drupal\google_tag\Entity\TagContainer
 */
class TagContainerListBuilder extends ConfigEntityListBuilder {

  /**
   * The config factory that knows what is overwritten.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['id'] = t('Machine name');
    $header['container_ids'] = t('Container ID(s)');
    $header['weight'] = t('Weight');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof TagContainer);
    // @todo Add JS for drag handle on weight.
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['container_ids'] = implode(', ', $entity->get('tag_container_ids'));
    $row['weight'] = $entity->get('weight');
    $config = $this->configFactory->get('google_tag.container.' . $entity->id());
    $row['status'] = $config->get('status') ? 'active' : 'inactive';
    if ($config->get('status') != $entity->status()) {
      $row['status'] .= ' (overwritten)';
    }
    return $row + parent::buildRow($entity);
  }

}
