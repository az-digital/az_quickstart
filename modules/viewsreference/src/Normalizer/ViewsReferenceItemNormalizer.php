<?php

namespace Drupal\viewsreference\Normalizer;

use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;
use Drupal\viewsreference\Plugin\Field\FieldType\ViewsReferenceItem;

if (class_exists('Drupal\hal\Normalizer\EntityReferenceItemNormalizer')) {

  /**
   * Defines a class for normalizing ViewsReferenceItems.
   */
  class ViewsReferenceItemNormalizer extends EntityReferenceItemNormalizer {

    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = ViewsReferenceItem::class;

    /**
     * {@inheritdoc}
     */
    protected function constructValue($data, $context) {
      return $data;
    }

  }

}
