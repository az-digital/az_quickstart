<?php

namespace Drupal\config_sync\Form;

use Drupal\config_distro\Form\ConfigDistroImportForm;
use Drupal\config_sync\ConfigSyncListerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Customized Config Distro import form.
 */
class ConfigSyncImportForm extends ConfigDistroImportForm {

  /**
   * The configuration synchronizer lister.
   *
   * @var \Drupal\config_sync\configSyncListerInterface
   */
  protected $configSyncLister;

  /**
   * The configuration update lister.
   *
   * @var \Drupal\config_update\ConfigListInterface
   */
  protected $configUpdateLister;

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
   * The filter manager.
   *
   * @var \Drupal\config_filter\ConfigFilterManagerInterface
   */
  protected $configFilterManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $class = parent::create($container);
    $class->configSyncLister = $container->get('config_sync.lister');
    $class->configUpdateLister = $container->get('config_update.config_list');
    $class->moduleList = $container->get('extension.list.module');
    $class->themeList = $container->get('extension.list.theme');
    $class->state = $container->get('state');
    $class->configFilterManager = $container->get('plugin.manager.config_filter');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Ensure the filters reflect the current state of the file system.
    $this->configFilterManager->clearCachedDefinitions();
    $form = parent::buildForm($form, $form_state);

    $form['config_sync'] = [
      '#type' => 'container',
    ];

    // Transform the UI to one based on our changelists.
    if ($changelists = $this->configSyncLister->getExtensionChangelists()) {
      // Create a wrapper for the classic UI provided by core.
      $form['classic'] = [
        '#type' => 'details',
        '#title' => $this->t('Classic version of configuration change listings'),
        '#weight' => 10,
      ];

      // For each collection, move the original listings coming from core's
      // form.
      foreach ($this->getAllChangelistCollections($changelists) as $collection) {
        if (isset($form[$collection])) {
          $form['classic'][$collection] = $form[$collection];
          unset($form[$collection]);
        }
      }

      foreach ($changelists as $type => $extension_changelists) {
        $form['config_sync'][$type] = $this->buildUpdatesListing($type, $extension_changelists);
      }

      if (!empty($form['actions']['submit'])) {
        $form['actions']['submit']['#value'] = $this->t('Import');
      }
    }

    $update_mode = $this->state->get('config_sync.update_mode', ConfigSyncListerInterface::DEFAULT_UPDATE_MODE);

    $form['config_sync']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#description' => $this->t('Select the mode used to merge in updates. Choose "Merge" to merge updates into the site\'s active configuration while retaining any customizations made since the configuration was originally installed. "Merge" is the default and is recommended for most sites. Choose "Partial reset" if you wish available configuration updates to override any customizations you\'ve made to those items. Choose "Full reset" if you wish to discard all customizations made on your site and revert configuration to the state currently provided by installed modules, themes, and the install profile.'),
      '#open' => ($update_mode !== ConfigSyncListerInterface::DEFAULT_UPDATE_MODE),
    ];

    $update_options = [
      ConfigSyncListerInterface::UPDATE_MODE_MERGE => $this->t('Merge'),
      ConfigSyncListerInterface::UPDATE_MODE_PARTIAL_RESET => $this->t('Partial reset'),
      ConfigSyncListerInterface::UPDATE_MODE_FULL_RESET => $this->t('Full reset'),
    ];

    $form['config_sync']['advanced']['update_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Update mode'),
      '#options' => $update_options,
      '#default_value' => $update_mode,
    ];

    $form['config_sync']['advanced']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change update mode'),
      '#submit' => [[$this, 'updateModeChange']],
    ];

    return $form;
  }

  /**
   * Form submission callback for changing the update mode.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateModeChange(array &$form, FormStateInterface $form_state) {
    // Save the selected update_mode.
    $this->state->set('config_sync.update_mode', (int) $form_state->getValue('update_mode'));
  }

  /**
   * Returns a list of collections for a given set of changelists.
   *
   * @param array $changelists
   *   A set of changelists.
   *
   * @return array
   *   An array of collection names.
   */
  protected function getAllChangelistCollections(array $changelists) {
    $collections = [];

    foreach ($changelists as $extension_changelists) {
      foreach ($extension_changelists as $collection_changelists) {
        $collections = array_unique(array_merge($collections, array_keys($collection_changelists)));
      }
    }

    return $collections;
  }

  /**
   * Builds the portion of the form showing a listing of updates.
   *
   * @param string $type
   *   The type of extension (module or theme).
   * @param array $extension_changelists
   *   Associative array of configuration changes keyed by extension name.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildUpdatesListing($type, array $extension_changelists) {
    $plugin_data = $this->state->get('config_sync.plugins_previous', []);

    $type_labels = [
      'module' => $this->t('Module'),
      'theme' => $this->t('Theme'),
    ];
    $header = [
      'name' => [
        'data' => $this->t('@type name', ['@type' => $type_labels[$type]]),
      ],
      'details' => [
        'data' => $this->t('Available configuration changes'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $options = [];
    $default_value = [];

    foreach ($extension_changelists as $name => $collection_changelists) {
      $options[$name] = $this->buildExtensionDetail($type, $name, $collection_changelists);
      // Status can be overridden in the state.
      $default_value[$name] = !isset($plugin_data[$type][$name]['status']) || ($plugin_data[$type][$name]['status'] === TRUE);
    }
    $element = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => TRUE,
      '#attributes' => ['class' => ['config-sync-listing']],
      '#default_value' => $default_value,
    ];

    return $element;
  }

  /**
   * Builds the details of a package.
   *
   * @param string $type
   *   The type of extension (module or theme).
   * @param string $name
   *   The machine name of the extension.
   * @param array $collection_changelists
   *   Associative array of configuration changes keyed by the type of change.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildExtensionDetail($type, $name, array $collection_changelists) {
    $label = '';
    switch ($type) {
      case 'module':
        $label = $this->moduleList->getName($name);
        break;

      case 'theme':
        $label = $this->themeList->getName($name);
        break;
    }

    $element['name'] = [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $label,
      ],
      'class' => ['config-sync-extension-name'],
    ];

    $rows = [];
    foreach ($collection_changelists as $collection => $changelist) {
      $extension_config = [];
      foreach (['create', 'update'] as $change_type) {
        if (isset($changelist[$change_type])) {
          $extension_config[$change_type] = [];
          foreach ($changelist[$change_type] as $item_name => $item_label) {
            $config_type = $this->configUpdateLister->getTypeNameByConfigName($item_name);
            if (!$config_type) {
              $config_type = 'system_simple';
            }
            $route_options = ['source_name' => $item_name];
            if ($collection != StorageInterface::DEFAULT_COLLECTION) {
              $route_name = 'config_distro.diff_collection';
              $route_options['collection'] = $collection;
            }
            else {
              $route_name = 'config_distro.diff';
            }
            if (!isset($extension_config[$change_type][$config_type])) {
              $extension_config[$change_type][$config_type] = [];
            }
            $extension_config[$change_type][$config_type][$item_name] = [
              '#type' => 'link',
              '#title' => $item_label,
              '#url' => Url::fromRoute($route_name, $route_options),
              '#options' => [
                'attributes' => [
                  'class' => ['use-ajax'],
                  'data-dialog-type' => 'modal',
                  'data-dialog-options' => json_encode([
                    'width' => 700,
                  ]),
                ],
              ],
            ];
          }
        }
      }

      if ($collection !== StorageInterface::DEFAULT_COLLECTION) {
        $rows[] = [
          [
            'data' => [
              '#type' => 'html_tag',
              '#tag' => 'h2',
              '#value' => $this->t('@collection configuration collection', ['@collection' => $collection]),
              '#colspan' => 2,
            ],
          ],
        ];
      }

      $change_type_labels = [
        // Match the labels used by core.
        // @see ConfigSync::buildForm().
        'create' => $this->t('New'),
        'update' => $this->t('Changed'),
      ];

      // List config types for order.
      $config_types = $this->configSyncLister->listConfigTypes();

      foreach ($extension_config as $change_type => $change_type_data) {
        $rows[] = [
          [
            'data' => [
              '#type' => 'html_tag',
              '#tag' => 'strong',
              '#value' => $change_type_labels[$change_type],
            ],
          ],
          [
            'data' => [
              '#type' => 'html_tag',
              '#tag' => 'strong',
              '#value' => $this->t('View differences'),
            ],
          ],
        ];

        foreach ($config_types as $config_type => $config_type_label) {
          if (isset($change_type_data[$config_type])) {
            $row = [];
            $row[] = [
              'data' => [
                '#type' => 'html_tag',
                '#tag' => 'span',
                '#value' => $config_type_label,
                '#attributes' => [
                  'title' => $config_type,
                  'class' => ['config-sync-item-type-label'],
                ],
              ],
            ];
            $row[] = [
              'data' => [
                '#theme' => 'item_list',
                '#items' => $change_type_data[$config_type],
                '#context' => [
                  'list_style' => 'comma-list',
                ],
              ],
              'class' => ['item'],
            ];
            $rows[] = $row;
          }
        }
      }

    }

    $element['details'] = [
      'data' => [
        '#type' => 'table',
        '#rows' => $rows,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the selected update_mode.
    $this->state->set('config_sync.update_mode', (int) $form_state->getValue('update_mode'));

    // Save data on selected modules and themes to the system state.
    $plugin_data = [];
    foreach (['module', 'theme'] as $type) {
      if ($names = $form_state->getValue($type)) {
        // Convert data to boolean values.
        array_walk($names, function ($value, $key) use ($type, &$plugin_data) {
          $value = (bool) $value;
          $plugin_data[$type][$key]['status'] = $value;
        });
      }
    }

    $this->state->set('config_sync.plugins', $plugin_data);
    $this->state->set('config_sync.plugins_previous', $plugin_data);
    $this->configFilterManager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
