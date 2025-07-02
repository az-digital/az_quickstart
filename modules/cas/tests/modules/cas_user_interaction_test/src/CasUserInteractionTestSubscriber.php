<?php

namespace Drupal\cas_user_interaction_test;

use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CasTestSubscriber.
 */
class CasUserInteractionTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CasHelper::EVENT_PRE_USER_LOAD_REDIRECT => 'onPreUserLoadRedirect',
    ];
  }

  /**
   * Redirects to a form that asks user to accept the site's 'Legal Notice'.
   *
   * @param \Drupal\cas\Event\CasPreUserLoadRedirectEvent $event
   *   The event.
   */
  public function onPreUserLoadRedirect(CasPreUserLoadRedirectEvent $event) {
    $is_legal_notice_changed = \Drupal::state()->get('cas_user_interaction_test.changed', FALSE);
    $local_account = \Drupal::service('externalauth.externalauth')->load($event->getPropertyBag()->getUsername(), 'cas');
    // Add a redirect only if a local account exists (i.e. it's a login
    // operation) and the site's 'Legal Notice' has changed.
    if ($local_account && $is_legal_notice_changed) {
      /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstore */
      $tempstore = \Drupal::service('tempstore.private')->get('cas_user_interaction_test');
      $tempstore->set('ticket', $event->getTicket());
      $tempstore->set('property_bag', $event->getPropertyBag());
      $event->setRedirectResponse(new RedirectResponse(Url::fromRoute('cas_user_interaction_test.form')->toString()));
    }
  }

}
