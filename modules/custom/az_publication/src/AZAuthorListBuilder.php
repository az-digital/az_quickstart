<?php

namespace Drupal\az_publication;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Author entities.
 *
 * @ingroup az_publication
 */
class AZAuthorListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Author ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityListQuery() : QueryInterface {
    // @todo In Drupal 11, replace this with usage of SORT_KEY.
    $query = $this->getStorage()
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType
        ->getKey('label'));
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\az_publication\Entity\AZAuthor $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.az_author.edit_form',
      ['az_author' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
