<?php

namespace Drupal\blazy\Plugin\views\field;

use Drupal\media\Entity\Media;
use Drupal\views\ResultRow;

/**
 * Defines a custom field that renders a preview of a media.
 *
 * @ViewsField("blazy_media")
 */
class BlazyViewsFieldMedia extends BlazyViewsFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\media\Entity\Media $entity */
    // @todo recheck relationship and remove this $entity = $values->_entity;
    $entity = $this->getEntity($values);

    if ($entity instanceof Media) {
      $options['defer'] = TRUE;
      $settings = $this->mergedViewsSettings($options, $entity);

      // Due to minimal settings, assumed core fields are in use.
      $settings['image'] = 'field_media_image';
      $data['#entity']   = $entity;
      $data['#settings'] = $settings;
      $data['#delta']    = $values->index;
      $data['#view']     = $this->view;

      // Populate media metadata earlier for their relevant libraries.
      // Need field.target_bundles, since this views field has none.
      // @todo remove once formatters and views fields are synced downstream.
      $this->blazyMedia->prepare($data);

      // Be sure after item setup.
      $this->blazyManager->preSettings($data['#settings']);
      $data['fallback'] = $entity->label();

      // Merge settings.
      $this->mergedSettings = $data['#settings'];

      // Pass results to \Drupal\blazy\BlazyEntity.
      // @todo phpstan bug only undertands the doc return types, not dynamic.
      /* @phpstan-ignore-next-line */
      return $this->blazyEntity->build($data);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'multimedia' => TRUE,
      'view_mode' => 'default',
    ] + parent::getPluginScopes();
  }

}
