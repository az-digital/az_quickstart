<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for switch to another user account.
 */
class SwitchUserController extends ControllerBase {

  /**
   * The current user.
   */
  protected AccountProxyInterface $account;

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * The session manager service.
   */
  protected SessionManagerInterface $sessionManager;

  /**
   * The session.
   */
  protected Session $session;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->userStorage = $container->get('entity_type.manager')->getStorage('user');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->sessionManager = $container->get('session_manager');
    $instance->session = $container->get('session');

    return $instance;
  }

  /**
   * Switches to a different user.
   *
   * We don't call session_save_session() because we really want to change
   * users. Usually unsafe!
   *
   * @param string $name
   *   The username to switch to, or NULL to log out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function switchUser($name = NULL) {
    if (empty($name) || !($account = $this->userStorage->loadByProperties(['name' => $name]))) {
      throw new AccessDeniedHttpException();
    }

    $account = reset($account);

    // Call logout hooks when switching from original user.
    $this->moduleHandler->invokeAll('user_logout', [$this->account]);

    // Regenerate the session ID to prevent against session fixation attacks.
    $this->sessionManager->regenerate();

    // Based off masquarade module as:
    // https://www.drupal.org/node/218104 doesn't stick and instead only
    // keeps context until redirect.
    $this->account->setAccount($account);
    $this->session->set('uid', $account->id());

    // Call all login hooks when switching to masquerading user.
    $this->moduleHandler->invokeAll('user_login', [$account]);

    return $this->redirect('<front>');
  }

}
