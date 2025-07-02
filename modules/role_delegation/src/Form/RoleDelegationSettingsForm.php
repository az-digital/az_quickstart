<?php

namespace Drupal\role_delegation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\role_delegation\DelegatableRolesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure book settings for this site.
 */
class RoleDelegationSettingsForm extends FormBase {

  /**
   * The current user viewing the form.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The role delegation service.
   *
   * @var \Drupal\role_delegation\DelegatableRolesInterface
   */
  protected $delegatableRoles;

  /**
   * The roles page setting form.
   *
   * @param \Drupal\role_delegation\DelegatableRolesInterface $delegatable_roles
   *   The role delegation service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user viewing the form.
   */
  public function __construct(DelegatableRolesInterface $delegatable_roles, AccountInterface $current_user) {
    $this->delegatableRoles = $delegatable_roles;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('delegatable_roles'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'role_delegation_role_assign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL): array {
    if (!$user instanceof AccountInterface) {
      return $form;
    }

    $current_roles = $user->getRoles(TRUE);
    $current_roles = array_combine($current_roles, $current_roles);

    $form['account']['role_change'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $this->delegatableRoles->getAssignableRoles($this->currentUser),
      '#default_value' => $current_roles,
      '#description' => $this->t('Change roles assigned to user.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $form_state->getBuildInfo()['args'][0];

    // Make sure this functionality works when single_user_role is enabled.
    // This module can change the role_change form element to a select or
    // radio buttons, which will return an single value instead of the default
    // checkboxes.
    $assigned_roles = is_array($form_state->getValue('role_change')) ? $form_state->getValue('role_change') : [$form_state->getValue('role_change') => $form_state->getValue('role_change')];
    $assignable_roles = $this->delegatableRoles->getAssignableRoles($this->currentUser);

    $roles = [];
    foreach ($assignable_roles as $rid => $assignable_role) {
      $roles[$rid] = isset($assigned_roles[$rid]) && !empty($assigned_roles[$rid]) ? $rid : 0;
    }

    foreach ($roles as $rid => $value) {
      empty($value) === TRUE ? $account->removeRole($rid) : $account->addRole($rid);
    }

    $account->save();
    $this->messenger()->addStatus($this->t('The roles have been updated.'));
  }

}
