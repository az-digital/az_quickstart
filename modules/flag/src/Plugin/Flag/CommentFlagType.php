<?php

namespace Drupal\flag\Plugin\Flag;

use Drupal\flag\FlagInterface;

/**
 * Provides a flag type for comments.
 *
 * @FlagType(
 *   id = "entity:comment",
 *   title = @Translation("Comment"),
 *   entity_type = "comment",
 *   provider = "comment"
 * )
 */
class CommentFlagType extends EntityFlagType {

  /**
   * {@inheritdoc}
   */
  protected function getExtraPermissionsOptions() {
    $options = parent::getExtraPermissionsOptions();
    $options['parent_owner'] = $this->t("Permissions based on ownership of a comment's parent entity.");
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function actionPermissions(FlagInterface $flag) {
    $permissions = parent::actionPermissions($flag);

    if (!empty($this->configuration['extra_permissions'])) {
      foreach ($this->configuration['extra_permissions'] as $option) {
        switch ($option) {
          // The 'owner' case is handled by the parent method.
          case 'parent_owner':
            // Define additional permissions.
            $permissions['flag ' . $flag->id() . ' comments on own parent entities'] = [
              'title' => $this->t('Flag %flag_title comments on own parent entities', [
                '%flag_title' => $flag->label(),
              ]),
            ];

            $permissions['unflag ' . $flag->id() . ' comments on own parent entities'] = [
              'title' => $this->t('Unflag %flag_title on own parent entities', [
                '%flag_title' => $flag->label(),
              ]),
            ];

            $permissions['flag ' . $flag->id() . ' comments on other parent entities'] = [
              'title' => $this->t("Flag %flag_title on others' parent entities", [
                '%flag_title' => $flag->label(),
              ]),
            ];

            $permissions['unflag ' . $flag->id() . ' comments on other parent entities'] = [
              'title' => $this->t("Unflag %flag_title on others' parent entities", [
                '%flag_title' => $flag->label(),
              ]),
            ];
            break;
        }
      }
    }

    return $permissions;
  }

  // @todo actionAccess for parent_owner permissions.
}
