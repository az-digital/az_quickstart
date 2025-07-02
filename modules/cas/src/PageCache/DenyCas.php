<?php

namespace Drupal\cas\PageCache;

use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures pages configured with gateway authentication are not cached.
 *
 * The logic we use to determine if a user should be redirected to gateway auth
 * is currently not compatible with page caching.
 */
class DenyCas implements ResponsePolicyInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Condition manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionManager;

  /**
   * Constructs a response policy for disabling cache on specific CAS paths.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The current route match.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExecutableManagerInterface $condition_manager) {
    $this->configFactory = $config_factory;
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    $config = $this->configFactory->get('cas.settings');
    if ($config->get('gateway.enabled') && $config->get('gateway.method') === CasHelper::GATEWAY_SERVER_SIDE) {
      // User can indicate specific paths to enable (or disable) gateway mode.
      $condition = $this->conditionManager->createInstance('request_path');
      $condition->setConfiguration($config->get('gateway.paths'));
      if ($this->conditionManager->execute($condition)) {
        return static::DENY;
      }
    }
    return NULL;
  }

}
