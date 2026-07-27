<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\user\Entity\User;

/**
 * Skip indexing content that is not accessible to the anonymous user.
 */
#[SearchApiProcessor(
  id: 'az_anonymous_access',
  label: new TranslatableMarkup('Index only entities accessible to the anonymous user'),
  description: new TranslatableMarkup('This performs an anonymous access check with the entity before indexing.'),
  stages: [
    'alter_items' => 10,
  ],
)]
class AZAnonymousAccess extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    // Get the instance of the anonymous user account.
    $user = User::getAnonymousUser();
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()->getValue();
      if (!empty($entity) && ($entity instanceof AccessibleInterface)) {
        // Exclude an item if entity is not accessible by the anonymous user.
        if (!$entity->access('view', $user)) {
          unset($items[$item_id]);
        }
      }
    }
  }

}
