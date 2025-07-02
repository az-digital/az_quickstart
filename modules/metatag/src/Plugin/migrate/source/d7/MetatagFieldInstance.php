<?php

namespace Drupal\metatag\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 7 Metatag field instances.
 *
 * @MigrateSource(
 *   id = "d7_metatag_field_instance",
 *   source_module = "metatag"
 * )
 */
class MetatagFieldInstance extends DrupalSqlBase {

  /**
   * The entity type bundle service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    /** @var static $source */
    $source = parent::create($container, $configuration, $plugin_id, $plugin_definition, $migration);
    $source->setEntityTypeBundleInfo($container->get('entity_type.bundle.info'));
    return $source;
  }

  /**
   * Sets the entity type bundle info service.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $base_query = $this->select('metatag', 'm')
      ->fields('m', ['entity_type'])
      ->groupBy('entity_type');

    if (isset($this->configuration['entity_type_id'])) {
      $entity_type_id = $this->configuration['entity_type_id'];
      $base_query->condition('m.entity_type', $entity_type_id);

      if (isset($this->configuration['bundle'])) {
        $bundle = $this->configuration['bundle'];
        switch ($entity_type_id) {
          case 'node':
            // We want to get a per-node-type metatag migration. So we inner
            // join the base query on node table based on the parsed node ID.
            $base_query->join('node', 'n', "n.nid = m.entity_id");
            $base_query->condition('n.type', $bundle);
            $base_query->addField('n', 'type', 'bundle');
            $base_query->groupBy('bundle');
            break;

          case 'taxonomy_term':
            // Join the taxonomy term data table to the base query; based on
            // the parsed taxonomy term ID.
            $base_query->join('taxonomy_term_data', 'ttd', "ttd.tid = m.entity_id");
            $base_query->fields('ttd', ['vid']);
            // Since the "taxonomy_term_data" table contains only the taxonomy
            // vocabulary ID, but not the vocabulary name, we have to inner
            // join the "taxonomy_vocabulary" table as well.
            $base_query->join('taxonomy_vocabulary', 'tv', 'ttd.vid = tv.vid');
            $base_query->condition('tv.machine_name', $bundle);
            $base_query->addField('tv', 'machine_name', 'bundle');
            $base_query->groupBy('ttd.vid');
            $base_query->groupBy('bundle');
            break;
        }
      }
    }

    return $base_query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
    ];
  }

  /**
   * Returns each entity_type/bundle pair.
   *
   * @return \ArrayIterator
   *   An array iterator object containing the entity type and bundle.
   */
  public function initializeIterator(): \ArrayIterator {
    $bundles = [];
    foreach (parent::initializeIterator() as $instance) {
      // For entity types for which we support creating derivatives, do not
      // retrieve the bundles using the D8|9 entity type bundle info service,
      // because then we will end up creating meta tag fields even for bundles
      // that do not use meta tags.
      if (isset($instance['bundle'])) {
        $bundles[] = $instance;
        continue;
      }
      $bundle_info = $this->entityTypeBundleInfo
        ->getBundleInfo($instance['entity_type']);
      foreach (array_keys($bundle_info) as $bundle) {
        $bundles[] = [
          'entity_type' => $instance['entity_type'],
          'bundle' => $bundle,
        ];
      }
    }
    return new \ArrayIterator($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE): int {
    /** @var \ArrayIterator $iterator */
    $iterator = $this->initializeIterator();
    return $iterator->count();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    /** @var \ArrayIterator $iterator */
    $iterator = $this->initializeIterator();
    return $iterator->count();
  }

}
