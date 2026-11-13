<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\media\MediaInterface;

/**
 * Works out the Form embed component's values from a media item's link.
 *
 * It asks the turned-on vendors to check the stored link. A link no vendor
 * accepts gives an empty field, and the component then renders nothing.
 *
 * @see \Drupal\az_form_embed\FormEmbedVendorManager::resolve()
 */
class FormEmbedFormItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $media = $this->getEntity();
    if (!$media instanceof MediaInterface) {
      return;
    }
    $url = $media->getSource()->getSourceFieldValue($media);
    if (!is_string($url) || $url === '') {
      return;
    }
    // A computed field can't have services injected, so it asks the
    // container.
    $values = \Drupal::service('plugin.manager.az_form_embed_vendor')->resolve($url);
    if ($values === NULL) {
      return;
    }
    $this->list[0] = $this->createItem(0, $values + ['label' => (string) $media->label()]);
  }

}
