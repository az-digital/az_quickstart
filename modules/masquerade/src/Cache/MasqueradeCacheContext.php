<?php

namespace Drupal\masquerade\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the MasqueradeCacheContext service, for "masquerade" caching.
 *
 * Cache context ID: 'session.is_masquerading'.
 */
class MasqueradeCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return new TranslatableMarkup('User is masquerading');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($request = $this->requestStack->getCurrentRequest()) {
      if ($request->hasSession() && ($bag = $request->getSession()->getMetadataBag())) {
        /** @var \Drupal\masquerade\Session\MetadataBag $bag */
        if ($bag->getMasquerade()) {
          // Previous account supposed to be Authenticated.
          return '1';
        }
      }
    }
    return '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
