<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\migmag_rollbackable\Traits\RollbackableConnectionTrait;
use Drupal\migmag_rollbackable\Traits\RollbackableDataTrait;
use Drupal\migrate\Row;
use Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers;
use Drupal\user\Entity\User;

/**
 * Rollbackable user shortcut set destination plugin.
 *
 * @see \Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_shortcut_set_users",
 *   provider = "shortcut"
 * )
 */
final class RollbackableShortcutSetUsers extends ShortcutSetUsers {

  use RollbackableConnectionTrait;
  use RollbackableDataTrait;

  /**
   * Prefix for the target object ID (which isn't a target object).
   *
   * @const string
   */
  const TARGET_ID_PREFIX = 'user-shortcut-set-';

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $account = User::load($row->getDestinationProperty('uid'));
    $previous_value = $this->getShortcutSetIdDisplayedToUser($account);

    $destination_ids = parent::import($row, $old_destination_id_values);

    $target_id = implode(self::DERIVATIVE_SEPARATOR, [
      self::TARGET_ID_PREFIX,
      $destination_ids[1],
    ]);

    $this->saveTargetRollbackData($target_id, $previous_value, '', '');

    return $destination_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $account = User::load($destination_identifier['uid']);
    $target_id = implode(self::DERIVATIVE_SEPARATOR, [
      self::TARGET_ID_PREFIX,
      $destination_identifier['uid'],
    ]);
    $data = $this->getTargetRollbackData($target_id, '', '');

    if (
      $data &&
      $shortcut_set = $this->shortcutSetStorage->load($data)
    ) {
      $this->shortcutSetStorage->assignUser($shortcut_set, $account);
    }
    else {
      $this->shortcutSetStorage->unassignUser($account);
    }

    $this->deleteTargetRollbackData($target_id, '', '');
  }

  /**
   * Returns the ID of the shortcut set displayed to the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return string
   *   The ID of the shortcut set displayed to the given user.
   */
  protected function getShortcutSetIdDisplayedToUser(AccountInterface $account): string {
    return DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.3',
      function () use ($account) {
        return $this->shortcutSetStorage->getDisplayedToUser($account)->id();
      },
      function () use ($account) {
        // @phpstan-ignore-next-line
        return shortcut_current_displayed_set($account)->id();
      }
    );
  }

}
