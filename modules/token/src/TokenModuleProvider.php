<?php

namespace Drupal\token;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Token module provider.
 */
class TokenModuleProvider extends CacheCollector {

  /**
   * Separator for the cache key.
   *
   * @internal
   */
  const SEPARATOR = '::';

  /**
   * The token service.
   *
   * @var \Drupal\token\TokenInterface
   */
  protected TokenInterface $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache, LockBackendInterface $lock, TokenInterface $token) {
    parent::__construct('token_module', $cache, $lock, [Token::TOKEN_INFO_CACHE_TAG]);
    $this->token = $token;
  }

  /**
   * Return the module responsible for a token.
   *
   * @param string $type
   *   The token type.
   * @param string $name
   *   The token name.
   *
   * @return string|null
   *   The value of $info['tokens'][$type][$name]['module'] from token info, or
   *   NULL if the value does not exist.
   */
  public function getTokenModule(string $type, string $name): ?string {
    return $this->get($type . static::SEPARATOR . $name);
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    [$type, $name] = explode(static::SEPARATOR, $key, 2);
    $token_info = $this->token->getTokenInfo($type, $name);
    $this->storage[$key] = $token_info['module'] ?? NULL;
    $this->persist($key);
    return $this->storage[$key];
  }

}
