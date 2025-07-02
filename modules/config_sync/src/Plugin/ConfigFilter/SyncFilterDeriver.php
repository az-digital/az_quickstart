<?php

namespace Drupal\config_sync\Plugin\ConfigFilter;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\config_sync\ConfigSyncListerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for SyncFilter filters.
 */
class SyncFilterDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The configuration synchronizer lister.
   *
   * @var \Drupal\config_sync\configSyncListerInterface
   */
  protected $configSyncLister;

  /**
   * The module list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleList;

  /**
   * The theme list service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

  /**
   * The state storage object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * SyncFilter constructor.
   *
   * @param \Drupal\config_sync\ConfigSyncListerInterface $config_sync_lister
   *   The configuration synchronizer lister.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme list service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage object.
   */
  public function __construct(ConfigSyncListerInterface $config_sync_lister, ModuleExtensionList $module_list, ThemeExtensionList $theme_list, StateInterface $state) {
    $this->configSyncLister = $config_sync_lister;
    $this->moduleList = $module_list;
    $this->themeList = $theme_list;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('config_sync.lister'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugin_data = $this->state->get('config_sync.plugins', []);
    foreach ($this->configSyncLister->getExtensionChangelists() as $type => $extension_changelists) {
      foreach (array_keys($extension_changelists) as $name) {
        $key = $type . '_' . $name;
        $this->derivatives[$key] = $base_plugin_definition;
        $this->derivatives[$key]['extension_type'] = $type;
        $this->derivatives[$key]['extension_name'] = $name;
        $label = '';
        $type_label = '';
        switch ($type) {
          case 'module':
            $label = $this->moduleList->getName($name);
            $type_label = $this->t('Module');
            break;

          case 'theme':
            $label = $this->themeList->getName($name);
            $type_label = $this->t('Theme');
            break;
        }
        $this->derivatives[$key]['label'] = $this->t('@type_label: @label', [
          '@type_label' => $type_label,
          '@label' => $label,
        ]);
        // Status can be overridden in the state.
        $this->derivatives[$key]['status'] = !isset($plugin_data[$type][$name]['status']) || ($plugin_data[$type][$name]['status'] === TRUE);
      }
    }

    return $this->derivatives;
  }

}
