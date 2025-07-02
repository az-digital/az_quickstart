<?php

namespace Drupal\role_delegation\Plugin\Action;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\role_delegation\Access\RoleDelegationAccessCheck;
use Drupal\user\Plugin\Action\RemoveRoleUser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alternate action plugin for 'user_remove_role_action'.
 *
 * This plugin makes sure the remove role action also works without
 * the 'administer users' permission.
 *
 * @see \Drupal\user\Plugin\Action\RemoveRoleUser
 */
class RoleDelegationRemoveRoleUser extends RemoveRoleUser {

  /**
   * The role delegation access checker.
   *
   * @var \Drupal\role_delegation\Access\RoleDelegationAccessCheck
   */
  protected $roleDelegationAccessCheck;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type, RoleDelegationAccessCheck $roleDelegationAccessCheck) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type);

    $this->roleDelegationAccessCheck = $roleDelegationAccessCheck;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getDefinition('user_role'),
      $container->get('access_check.role_delegation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = parent::access($object, $account, $return_as_object);

    // If access == true, the user already has the administer users permission.
    if ($access === TRUE) {
      return $access;
    }

    // Check if the user has access to remove the role from the user.
    return $this->roleDelegationAccessCheck->access($account);
  }

}
