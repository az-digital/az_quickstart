<?php

namespace Drupal\devel\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber for force the system to rebuild the theme registry.
 */
class ThemeInfoRebuildSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Internal flag for handle user notification.
   */
  protected string $notificationFlag = 'devel.rebuild_theme_warning';

  /**
   * The devel config.
   */
  protected Config $config;

  /**
   * The current user.
   */
  protected AccountProxyInterface $account;

  /**
   * The theme handler.
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The messenger.
   */
  protected MessengerInterface $messenger;

  /**
   * The theme registry.
   */
  protected Registry $themeRegistry;

  /**
   * Constructs a ThemeInfoRebuildSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    AccountProxyInterface $account,
    ThemeHandlerInterface $theme_handler,
    MessengerInterface $messenger,
    TranslationInterface $string_translation,
    Registry $theme_registry,
  ) {
    $this->config = $config->get('devel.settings');
    $this->account = $account;
    $this->themeHandler = $theme_handler;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->themeRegistry = $theme_registry;
  }

  /**
   * Forces the system to rebuild the theme registry.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function rebuildThemeInfo(RequestEvent $event): void {
    if ($this->config->get('rebuild_theme')) {
      // Update the theme registry.
      $this->themeRegistry->reset();

      // Refresh theme data.
      $this->themeHandler->refreshInfo();
      // Resets the internal state of the theme handler and clear the 'system
      // list' cache; this allow to properly register, if needed, PSR-4
      // namespaces for theme extensions after refreshing the info data.
      $this->themeHandler->reset();
      // Notify the user that the theme info are rebuilt on every request.
      $this->triggerWarningIfNeeded($event->getRequest());
    }
  }

  /**
   * Notifies the user that the theme info are rebuilt on every request.
   *
   * The warning message is shown only to users with adequate permissions and
   * only once per session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  protected function triggerWarningIfNeeded(Request $request) {
    if ($this->account->hasPermission('access devel information')) {
      $session = $request->getSession();
      if (!$session->has($this->notificationFlag)) {
        $session->set($this->notificationFlag, TRUE);
        $message = $this->t('The theme information is being rebuilt on every request. Remember to <a href=":url">turn off</a> this feature on production websites.', [':url' => Url::fromRoute('devel.admin_settings')->toString()]);
        $this->messenger->addWarning($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Set high priority value to start as early as possible.
    $events[KernelEvents::REQUEST][] = ['rebuildThemeInfo', 256];
    return $events;
  }

}
