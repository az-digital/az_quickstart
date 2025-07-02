<?php

namespace Drupal\metatag;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a listing of Metatag defaults entities.
 */
class MetatagDefaultsListBuilder extends ConfigEntityListBuilder {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', 'global', '<>');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    $entity_ids = $query->execute();

    // Load global entity always.
    $parents = $this->getParentIds($entity_ids);
    if (!empty($parents)) {
      if (empty($entity_ids)) {
        $entity_ids = $parents;
      }
      else {
        $entity_ids = array_merge($entity_ids, $parents);
      }
    }
    return $entity_ids;
  }

  /**
   * Gets the parent entity ids for the list of entities to load.
   *
   * @param array $entity_ids
   *   The metatag entity ids.
   *
   * @return array
   *   The list of parents to load
   */
  protected function getParentIds(array $entity_ids): array {
    $parents = ['global' => 'global'];
    foreach ($entity_ids as $entity_id) {
      if (strpos($entity_id, '__') !== FALSE) {
        $entity_id_array = explode('__', $entity_id);
        $parent = reset($entity_id_array);
        $parents[$parent] = $parent;
      }
    }
    $parents_query = $this->getStorage()->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $parents, 'IN');
    return $parents_query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Type');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity */
    $row['label'] = $this->getLabelAndConfig($entity);
    $row['status'] = $entity->status() ? $this->t('Active') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    // Global and entity defaults can be reverted but not deleted.
    if (in_array($entity->id(), MetatagManager::protectedDefaults())) {
      unset($operations['delete']);
      $operations['revert'] = [
        'title' => $this->t('Revert'),
        'weight' => $operations['edit']['weight'] + 1,
        'url' => $entity->toUrl('revert-form'),
      ];
    }

    return $operations;
  }

  /**
   * Renders the Metatag defaults label plus its configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Metatag defaults entity.
   *
   * @return array
   *   Render array for a table cell.
   */
  public function getLabelAndConfig(EntityInterface $entity): array {
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity */
    $output = '<div>';
    $prefix = '';
    $inherits = '';
    if ($entity->id() != 'global') {
      $prefix = '<div class="indentation"></div>';
      $inherits .= 'Global';
    }
    if (strpos($entity->id(), '__') !== FALSE) {
      $prefix .= '<div class="indentation"></div>';
      $entity_label = explode(': ', $entity->get('label'));
      $inherits .= ', ' . $entity_label[0];
    }

    if (!empty($inherits)) {
      $output .= '<div><p>' . $this->t('Inherits meta tags from: @inherits', [
        '@inherits' => $inherits,
      ]) . '</p></div>';
    }
    $tags = $entity->get('tags');
    if (count($tags)) {
      $output .= '<table>
<tbody>';
      foreach ($tags as $tag_id => $tag_value) {
        if (is_array($tag_value)) {
          $tag_value = implode(', ', array_filter($tag_value));
        }
        $output .= '<tr><td>' . $tag_id . ':</td><td>' . $tag_value . '</td></tr>';
      }
      $output .= '</tbody></table>';
    }

    $output .= '</div>';

    return [
      'data' => [
        '#type' => 'details',
        '#prefix' => $prefix,
        '#title' => $entity->label(),
        'config' => [
          '#markup' => $output,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (\Drupal::state()->get('system.maintenance_mode')) {
      \Drupal::messenger()->addMessage($this->t('Please note that while the site is in maintenance mode none of the usual meta tags will be output.'));
    }
    return parent::render();
  }

}
