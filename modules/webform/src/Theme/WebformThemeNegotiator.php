<?php

namespace Drupal\webform\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\webform\WebformRequestInterface;

/**
 * Sets the admin theme on a webform that does not have a public canonical URL.
 */
class WebformThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Creates a new WebformThemeNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory, WebformRequestInterface $request_handler) {
    $this->user = $user;
    $this->configFactory = $config_factory;
    $this->requestHandler = $request_handler;

  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->getActiveTheme($route_match) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->getActiveTheme($route_match);
  }

  /**
   * Determine the active theme for the current route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The active theme or an empty string.
   */
  protected function getActiveTheme(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    if (empty($route_name)) {
      return '';
    }

    if (strpos($route_name, 'webform') === FALSE) {
      return '';
    }

    $webform = $this->requestHandler->getCurrentWebform();
    if (empty($webform)) {
      return '';
    }

    $is_webform_route = in_array($route_name, [
      'entity.webform.canonical',
      'entity.webform.test_form',
      'entity.webform.confirmation',
      'entity.node.webform.test_form',
    ]);
    $is_user_submission_route = (strpos($route_name, 'entity.webform.user.') === 0);

    // If webform route and page is disabled, apply admin theme to
    // the webform routes.
    if ($is_webform_route && !$webform->hasPage()) {
      return ($this->user->hasPermission('view the administration theme'))
        ? $this->configFactory->get('system.theme')->get('admin')
        : '';
    }

    // If webform and user submission routes apply custom page theme to
    // the webform routes.
    if (($is_webform_route || $is_user_submission_route)
      && $webform->getSetting('page_theme_name')) {
      return $webform->getSetting('page_theme_name');
    }

    return '';
  }

}
