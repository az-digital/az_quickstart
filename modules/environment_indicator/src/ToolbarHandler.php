<?php

namespace Drupal\environment_indicator;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\environment_indicator\Entity\EnvironmentIndicator;

/**
 * Toolbar integration handler.
 */
class ToolbarHandler {

  use StringTranslationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The environment indicator config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The active environment.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $activeEnvironment;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $account;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * Drupal settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state system.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $account,
    StateInterface $state,
    Settings $settings,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->moduleHandler = $module_handler;
    $this->config = $config_factory->get('environment_indicator.settings');
    $this->activeEnvironment = $config_factory->get('environment_indicator.indicator');
    $this->account = $account;
    $this->state = $state;
    $this->settings = $settings;
    $this->entityTypeManager = $entity_type_manager;
  }


  /**
   * User can access all indicators.
   *
   * @return bool
   */
  public function hasAccessAll(): bool {
    return $this->account->hasPermission('access environment indicator');
  }

  /**
   * User can access a specific indicator.
   *
   * @param $environment
   *   The environment identifier.
   *
   * @return bool
   */
  public function hasAccessEnvironment($environment): bool {
    return $this->hasAccessAll() || $this->account->hasPermission('access environment indicator ' . $environment);
  }

  /**
   * User can access the indicator for the active environment.
   *
   * @return bool
   */
  public function hasAccessActiveEnvironment(): bool {
    return $this->hasAccessEnvironment($this->activeEnvironment->get('machine'));
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The environment indicator toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar(): array {
    $items['environment_indicator'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if ($this->hasAccessActiveEnvironment() && $this->externalIntegration('toolbar')) {

      $title = $this->getTitle();

      $items['environment_indicator'] += [
        '#type' => 'toolbar_item',
        '#weight' => 125,
        'tab' => [
          '#type' => 'link',
          '#title' => $title,
          '#url' => Url::fromRoute('environment_indicator.settings'),
          '#attributes' => [
            'title' => $this->t('Environments'),
            'class' => ['toolbar-icon', 'toolbar-icon-environment'],
          ],
          '#access' => !empty($title),
        ],
        'tray' => [
          '#heading' => $this->t('Environments menu'),
        ],
        '#attached' => [
          'library' => ['environment_indicator/drupal.environment_indicator'],
          'drupalSettings' => [
            'environmentIndicator' => [
              'name' => $this->activeEnvironment->get('name') ?: ' ',
              'fgColor' => $this->activeEnvironment->get('fg_color'),
              'bgColor' => $this->activeEnvironment->get('bg_color'),
              'addFavicon' => $this->config->get('favicon'),
            ],
          ],
        ],
      ];

      // Add cache tags to the toolbar item while preserving context.
      $items['environment_indicator']['#cache']['tags'] = Cache::mergeTags(
        [
          'config:environment_indicator.settings',
          'config:environment_indicator.indicator',
        ],
        $this->getCacheTags()
      );
      if ($this->account->hasPermission('administer environment indicator settings')) {
        $items['environment_indicator']['tray']['configuration'] = [
          '#type' => 'link',
          '#title' => $this->t('Configure'),
          '#url' => Url::fromRoute('environment_indicator.settings'),
          '#options' => [
            'attributes' => ['class' => ['edit-environments']],
          ],
        ];
      }

      if ($links = $this->getLinks()) {
        $items['environment_indicator']['tray']['environment_links'] = [
          '#theme' => 'links__toolbar_shortcuts',
          '#links' => $links,
          '#attributes' => [
            'class' => ['toolbar-menu'],
          ],
        ];
      }
    }

    return $items;
  }

  /**
   * Retrieve value from the selected version identifier source.
   *
   * @return string|null
   */
  public function getCurrentRelease(): ?string {
    $version_identifier = $this->config->get('version_identifier') ?? 'environment_indicator_current_release';
    $version_identifier_fallback = $this->config->get('version_identifier_fallback') ?? 'deployment_identifier';

    $release = $this->getVersionIdentifier($version_identifier);
    if ($release !== NULL) {
      return $release;
    }

    if ($version_identifier !== $version_identifier_fallback) {
      return $this->getVersionIdentifier($version_identifier_fallback);
    }

    return NULL;
  }

  /**
   * Helper function to get version identifier based on the type.
   *
   * @param string $type
   *   The type of version identifier.
   *
   * @return string|null
   */
  protected function getVersionIdentifier(string $type): ?string {
    switch ($type) {
      case 'environment_indicator_current_release':
        $current_release = $this->state->get('environment_indicator.current_release');
        return $current_release !== NULL ? (string) $current_release : NULL;

      case 'deployment_identifier':
        $deployment_identifier = $this->settings->get('deployment_identifier');
        return $deployment_identifier !== NULL ? (string) $deployment_identifier : NULL;

      case 'drupal_version':
        return \Drupal::VERSION;

      case 'none':
      default:
        return NULL;
    }
  }

  /**
   * Construct the title for the active environment.
   *
   * @return string|null
   */
  public function getTitle(): ?string {
    $environment = $this->activeEnvironment->get('name');
    $release = $this->getCurrentRelease();
    return ($release) ? '(' . $release . ') ' . $environment : $environment;
  }

  /**
   * Helper function that checks if there is external integration.
   *
   * @param $integration
   *   Name of the integration: toolbar, admin_menu, ...
   *
   * @return bool
   *   TRUE if integration is enabled. FALSE otherwise.
   */
  public function externalIntegration($integration): bool {
    if ($integration == 'toolbar') {
      if ($this->moduleHandler->moduleExists('toolbar')) {
        $toolbar_integration = $this->config->get('toolbar_integration') ?? [];
        if (in_array('toolbar', $toolbar_integration)) {
          if ($this->account->hasPermission('access toolbar')) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Get the cache tags for the environment indicator switcher.
   *
   * @return array
   *   The cache tags.
   */
  public function getCacheTags(): array {
    return $this->entityTypeManager->getDefinition('environment_indicator')->getListCacheTags();
  }

  /**
   * Get all the links for the switcher.
   *
   * @return array
   */
  public function getLinks(): array {
    if (!$environment_entities = EnvironmentIndicator::loadMultiple()) {
      return [];
    }

    $current = Url::fromRoute('<current>');
    $current_path = $current->toString();
    $environment_entities = array_filter(
      $environment_entities,
      function (EnvironmentIndicator $entity) {
        return $entity->status()
          && !empty($entity->getUrl())
          && $this->hasAccessEnvironment($entity->id());
      }
    );

    $links = array_map(
      function (EnvironmentIndicator $entity) use ($current_path) {
        return [
          'attributes' => [
            'style' => 'color: ' . $entity->getFgColor() . '; background-color: ' . $entity->getBgColor() . ';',
            'title' => $this->t('Opens the current page in the selected environment.'),
          ],
          'title' => $this->t('Open on @label', ['@label' => $entity->label()]),
          'url' => Url::fromUri($entity->getUrl() . $current_path),
          'type' => 'link',
          'weight' => $entity->getWeight(),
        ];
      },
      $environment_entities
    );

    if (!$links) {
      return [];
    }

    uasort($links, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $links;
  }

}

