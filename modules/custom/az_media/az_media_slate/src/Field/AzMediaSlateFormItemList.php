<?php

declare(strict_types=1);

namespace Drupal\az_media_slate\Field;

use Drupal\az_media_slate\Plugin\Field\FieldType\AzMediaSlateFormItem;
use Drupal\az_media_slate\SlateUrl;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\media\MediaInterface;

/**
 * Computes a Slate Form media item's embed values from its stored URL.
 *
 * The URL goes through SlateUrl::parse() here, so anything that reads this
 * field gets URLs that already passed its checks. That's what lets the Slate
 * Form component take them as plain props and call no module code. A URL
 * that fails leaves the field empty, and the component renders nothing.
 *
 * Nothing is logged here. The Slate formatter logs a rejected URL, with the
 * reason, when it renders one.
 */
class AzMediaSlateFormItemList extends FieldItemList {

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
    $slate_url = SlateUrl::parse($url);
    if ($slate_url === NULL) {
      return;
    }
    $this->list[0] = $this->createItem(0, AzMediaSlateFormItem::valuesFromUrl($slate_url, (string) $media->label()));
  }

}
