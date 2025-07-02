<?php

namespace Drupal\smart_date\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\smart_date\SmartDateTrait;

/**
 * Defines a class to build a listing of Smart date formats.
 *
 * @ingroup smart_date
 */
class SmartDateFormatListBuilder extends EntityListBuilder {

  use SmartDateTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['preview'] = $this->t('Preview');
    $header['date_format'] = $this->t('Date Format');
    $header['time_format'] = $this->t('Time Format');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['query'] = Link::createFromRoute(
      $entity->label(),
      'entity.smart_date_format.edit_form',
      ['smart_date_format' => $entity->id()]
    );
    // Show a preview and list the primary PHP codes.
    $formatted = $this->formatSmartDate(time(), time() + 3600, $entity->getOptions(), NULL, 'string');
    $row['preview']['data'] = $formatted;
    $row['date_format']['data'] = $entity->get('date_format');
    $row['time_format']['data'] = $entity->get('time_format');
    return $row + parent::buildRow($entity);
  }

  /**
   * Turn a EntityReferenceFieldItemList into a render array of links.
   */
  protected function makeLinksFromRef($ref) {
    // No value means nothing to do.
    if (!$ref) {
      return NULL;
    }
    $entities = $ref->referencedEntities();
    $content = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    $links = [];
    foreach ($entities as $ref_entity) {
      $links[] = Link::fromTextAndUrl(
        $ref_entity->getTitle(),
        $ref_entity->toUrl()
      );
    }
    $content['#items'] = $links;
    return $content;
  }

}
