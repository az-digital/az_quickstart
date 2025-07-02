<?php

namespace Drupal\google_tag\Migration;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\google_tag\GoogleTagEventManager;

/**
 * Base class for migrating google analytics 4.x/google tag 1.x configuration.
 */
class GoogleTagMigrateBase {

  /**
   * Google Tag Event Manager.
   *
   * @var \Drupal\google_tag\GoogleTagEventManager
   */
  protected GoogleTagEventManager $eventManager;

  /**
   * Condition Manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected ConditionManager $conditionManager;

  /**
   * Config Factory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * GoogleTagMigrateBase constructor.
   *
   * @param \Drupal\google_tag\GoogleTagEventManager $eventManager
   *   Event Manager.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   Condition Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(GoogleTagEventManager $eventManager, ConditionManager $conditionManager, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->eventManager = $eventManager;
    $this->conditionManager = $conditionManager;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Instantiates all the events and returns their default configurations.
   *
   * @return array
   *   Events configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getDefaultEventData(): array {
    $event_data = [];
    $event_definitions = $this->eventManager->getDefinitions();
    foreach (array_keys($event_definitions) as $event_id) {
      /** @var \Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface $event_plugin */
      $event_plugin = $this->eventManager->createInstance($event_id, []);
      $event_data[$event_id] = $event_plugin->getConfiguration();
    }
    return $event_data;
  }

  /**
   * Configures request path plugin config.
   *
   * @param string $request_paths
   *   Pages to configure.
   * @param bool $request_negate
   *   Negation flag.
   *
   * @return array
   *   Configuration.
   */
  protected static function getRequestPathCondition(string $request_paths, bool $request_negate): array {
    return [
      'id' => 'request_path',
      'pages' => $request_paths,
      'negate' => $request_negate,
    ];
  }

  /**
   * Configures user role plugin config.
   *
   * @param array $roles
   *   User roles to configure.
   * @param bool $roles_negate
   *   Negation flag.
   *
   * @return array
   *   Configuration.
   */
  protected static function getUserRoleCondition(array $roles, bool $roles_negate): array {
    $user_role_definition = \Drupal::service('plugin.manager.condition')->getDefinition('user_role');
    $context_definition = $user_role_definition['context_definitions']['user'];
    $user_contexts = \Drupal::service('context.handler')->getMatchingContexts(
      \Drupal::service('context.repository')->getAvailableContexts(),
      $context_definition
    );
    return [
      'id' => 'user_role',
      'roles' => $roles,
      'negate' => $roles_negate,
      'context_mapping' => [
        'user' => array_key_first($user_contexts),
      ],
    ];
  }

}
