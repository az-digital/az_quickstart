<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides specific linkit matchers for the user entity type.
 *
 * @Matcher(
 *   id = "entity:user",
 *   label = @Translation("User"),
 *   target_entity = "user",
 *   provider = "user"
 * )
 */
class UserMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summery = parent::getSummary();

    $roles = !empty($this->configuration['roles']) ? $this->configuration['roles'] : ['None'];
    $summery[] = $this->t('Role filter: @role_filter', [
      '@role_filter' => implode(', ', $roles),
    ]);

    $summery[] = $this->t('Include blocked users: @include_blocked', [
      '@include_blocked' => $this->configuration['include_blocked'] ? $this->t('Yes') : $this->t('No'),
    ]);

    return $summery;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'roles' => [],
      'include_blocked' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['user'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['role_restrictions'] = [
      '#type' => 'details',
      '#title' => $this->t('Role restrictions'),
      '#open' => TRUE,
      '#weight' => -90,
    ];

    $roles = Role::loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);

    $form['role_restrictions']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Restrict to the selected roles'),
      '#options' => array_map(fn(RoleInterface $role) => $role->label(), $roles),
      '#default_value' => $this->configuration['roles'],
      '#description' => $this->t('If none of the checkboxes is checked, all roles are allowed.'),
      '#element_validate' => [[get_class($this), 'elementValidateFilter']],
    ];

    $form['blocked_users'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocked users'),
      '#open' => TRUE,
    ];

    $form['blocked_users']['include_blocked'] = [
      '#title' => $this->t('Include blocked user'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['include_blocked'],
      '#description' => $this->t('In order to see blocked users, users must have permissions to do so.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['roles'] = $form_state->getValue('roles');
    $this->configuration['include_blocked'] = $form_state->getValue('include_blocked');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($search_string) {
    $query = parent::buildEntityQuery($search_string);

    $search_string = $this->database->escapeLike($search_string);
    // The user entity don't specify a label key so we have to do it instead.
    $query->condition('name', '%' . $search_string . '%', 'LIKE');

    // Filter by role.
    if (!empty($this->configuration['roles'])) {
      $query->condition('roles', $this->configuration['roles'], 'IN');
    }

    if ($this->configuration['include_blocked'] !== TRUE || !$this->currentUser->hasPermission('administer users')) {
      $query->condition('status', 1);
    }

    return $query;
  }

}
