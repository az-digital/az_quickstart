<?php

namespace Drupal\flag\Plugin\ActionLink;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagInterface;

/**
 * Provides the Confirm Form link type.
 *
 * @ActionLinkType(
 *  id = "confirm",
 * label = @Translation("Confirm Form"),
 * description = @Translation("Redirects the user to a confirmation form.")
 * )
 */
class ConfirmForm extends FormEntryTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getUrl($action, FlagInterface $flag, EntityInterface $entity) {
    switch ($action) {
      case 'flag':
        return Url::fromRoute('flag.confirm_flag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);

      default:
        return Url::fromRoute('flag.confirm_unflag', [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);
    }
  }

}
