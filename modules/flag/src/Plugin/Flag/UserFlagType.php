<?php

namespace Drupal\flag\Plugin\Flag;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;

/**
 * Provides a flag type for user entities.
 *
 * @FlagType(
 *   id = "entity:user",
 *   title = @Translation("User"),
 *   entity_type = "user",
 *   provider = "user"
 * )
 */
class UserFlagType extends EntityFlagType {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = parent::defaultConfiguration();
    $options += [
      'show_on_profile' => TRUE,
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /* Options form extras for user flags */

    $form['access']['bundles'] = [
      // A user flag doesn't support node types.
      // @todo Maybe support roles instead of node types.
      '#type' => 'value',
      '#value' => [0 => 0],
    ];
    $form['display']['show_on_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link on user profile page'),
      '#description' => $this->t('Show the link formatted as a user profile element.'),
      '#default_value' => $this->showOnProfile(),
      // Put this above 'show on entity'.
      '#weight' => -1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['show_on_profile'] = $form_state->getValue(['show_on_profile']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtraPermissionsOptions() {
    $options = parent::getExtraPermissionsOptions();

    // Tweak the UI label from the parent class.
    $options['owner'] = $this->t('Permissions for users to flag themselves.');

    return $options;
  }

  /**
   * Specifies if the flag link should appear on the user profile.
   *
   * @return bool
   *   TRUE if the flag link appears on the user profile, FALSE otherwise.
   */
  public function showOnProfile() {
    return $this->configuration['show_on_profile'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtraPermissionsOwner(FlagInterface $flag) {
    $permissions['flag ' . $flag->id() . ' own user account'] = [
      'title' => $this->t('Flag %flag_title own profile', [
        '%flag_title' => $flag->label(),
      ]),
    ];

    $permissions['unflag ' . $flag->id() . ' own user account'] = [
      'title' => $this->t('Unflag %flag_title own profile', [
        '%flag_title' => $flag->label(),
      ]),
    ];

    $permissions['flag ' . $flag->id() . ' other user accounts'] = [
      'title' => $this->t("Flag %flag_title others' profiles", [
        '%flag_title' => $flag->label(),
      ]),
    ];

    $permissions['unflag ' . $flag->id() . ' other user accounts'] = [
      'title' => $this->t("Unflag %flag_title others' profiles", [
        '%flag_title' => $flag->label(),
      ]),
    ];

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function isFlaggableOwnable() {
    // The User entity doesn't implement EntityOwnerInterface, but technically
    // a user 'owns' themselves. Moreover, the 'owner' permissions are about
    // whether the uid property of the flaggable matches the current user, which
    // applies to User flaggables too.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAddEditForm($operation) {
    // The user profile form uses 'default' as the operation for editing, and
    // 'register' for adding.
    return in_array($operation, ['register', 'default']);
  }

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL) {
    $access = parent::actionAccess($action, $flag, $account, $flaggable);

    if ($flaggable && $this->hasExtraPermission('owner')) {
      // Permit selfies.
      $permission = $action . ' ' . $flag->id() . ' own user account';
      $selfies_permission_access = AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheContexts(['user']);
      $account_match_access = AccessResult::allowedIf($account->id() == $flaggable->id());
      $own_access = $selfies_permission_access->andIf($account_match_access);
      $access = $access->orIf($own_access);

      // Act on others' profiles.
      $permission = $action . ' ' . $flag->id() . ' other user accounts';
      $others_permission_access = AccessResult::allowedIfHasPermission($account, $permission)
        ->addCacheContexts(['user']);
      $account_mismatch_access = AccessResult::allowedIf($account->id() != $flaggable->id());
      $others_access = $others_permission_access->andIf($account_mismatch_access);
      $access = $access->orIf($others_access);
    }

    return $access;
  }

}
