<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity;

/**
 * Plugin implementation of the 'Webform url' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_entity_reference_url",
 *   label = @Translation("URL"),
 *   description = @Translation("Display URL to the referenced webform."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceUrlFormatter extends WebformEntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $source_entity = $items->getEntity();

    $elements = [];

    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = $this->getEntitiesToView($items, $langcode);

    foreach ($entities as $delta => $entity) {
      $link_options = QueryStringWebformSourceEntity::getRouteOptionsQuery($source_entity);

      $link = [
        '#plain_text' => $entity->toUrl('canonical', $link_options)->toString(),
      ];

      $elements[$delta] = $link;

      $this->setCacheContext($elements[$delta], $entity, $items[$delta]);
    }

    return $elements;
  }

}
