<?php

namespace Drupal\cas\Plugin\Validation\Constraint;

use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Decorates the ProtectedUserFieldConstraint constraint.
 */
class CasProtectedUserFieldConstraintValidator extends ProtectedUserFieldConstraintValidator {

  /**
   * The CasUserManager service.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * Whether or not restricted password managment is enabled.
   *
   * @var bool
   */
  protected $restrictedPasswordManagement = FALSE;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\cas\Service\CasUserManager $cas_user_manager
   *   The CAS user manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(UserStorageInterface $user_storage, AccountProxyInterface $current_user, CasUserManager $cas_user_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($user_storage, $current_user);
    $this->casUserManager = $cas_user_manager;
    $this->restrictedPasswordManagement = (bool) $config_factory->get('cas.settings')->get('user_accounts.restrict_password_management');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('current_user'),
      $container->get('cas.user_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // Skip the validator if CAS is configured with restricted password
    // management and if the user being validated is a CAS user.
    if (!empty($items)) {
      $account = $items->getEntity();
      if ($account->id() !== NULL && $this->restrictedPasswordManagement && !empty($this->casUserManager->getCasUsernameForAccount($account->id()))) {
        return;
      }
    }

    parent::validate($items, $constraint);
  }

}
