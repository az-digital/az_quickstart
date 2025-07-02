<?php

namespace Drupal\masquerade\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for switch and back to masquerade as user.
 */
class SwitchController extends ControllerBase {

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  private $destination;

  /**
   * Constructs a new SwitchController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The redirect destination helper.
   */
  public function __construct(AccountInterface $current_user, Masquerade $masquerade, RedirectDestinationInterface $destination) {
    $this->currentUser = $current_user;
    $this->masquerade = $masquerade;
    $this->destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('masquerade'),
      $container->get('redirect.destination')
    );
  }

  /**
   * Masquerades the current user as a given user.
   *
   * Access to masquerade as the target user account has to checked by
   * all callers via masquerade_target_user_access() already.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account object to masquerade as.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to previous page.
   *
   * @see this::getRedirectResponse()
   */
  public function switchTo(UserInterface $user, Request $request) {
    // Store current user for messages.
    $account = $this->currentUser;
    $error = masquerade_switch_user_validate($user);
    if (empty($error)) {
      if ($this->masquerade->switchTo($user)) {
        $this->messenger()->addStatus($this->t('You are now masquerading as @user.', [
          '@user' => $account->getDisplayName(),
        ]));
      }
    }
    else {
      $this->messenger()->addError($error);
    }
    return $this->getRedirectResponse($request);
  }

  /**
   * Allows a user who is currently masquerading to become a new user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to previous page.
   *
   * @see this::getRedirectResponse()
   */
  public function switchBack(Request $request) {
    // Store current user name for messages.
    $account_name = $this->currentUser->getDisplayName();
    if ($this->masquerade->switchBack()) {
      $this->messenger()->addStatus($this->t('You are no longer masquerading as @user.', [
        '@user' => $account_name,
      ]));
    }
    else {
      $this->messenger()->addError($this->t('Error trying unmasquerading as @user.', [
        '@user' => $account_name,
      ]));
    }
    return $this->getRedirectResponse($request);
  }

  /**
   * Returns redirect response to previous page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect.
   *
   * @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
   */
  protected function getRedirectResponse(Request $request) {
    if ($destination_path = $this->destination->get()) {
      // When Drupal is installed in a sub-directory, destination path have to
      // cut off the baseUrl part.
      $destination_path = preg_replace('/^' . preg_quote($request->getBaseUrl(), '/') . '/', '', $destination_path);
      // Try destination first.
      $url = Url::createFromRequest(Request::create($destination_path));
    }
    elseif ($redirect_path = $request->server->get('HTTP_REFERER')) {
      // Parse referer to get route name if any.
      $url = Url::createFromRequest(Request::create($redirect_path));
    }
    else {
      // Fallback to front page if no referrer.
      $url = Url::fromRoute('<front>');
    }
    // Check access for redirected url.
    if (!$url->access($this->currentUser)) {
      // Fallback to front page redirect.
      $url = Url::fromRoute('<front>');
    }
    $url = $url->setAbsolute()->toString();
    if ($destination_path) {
      // Override destination because it will take over response.
      $request->query->set('destination', $url);
      $this->destination->set($url);
    }
    return new RedirectResponse($url);
  }

}
