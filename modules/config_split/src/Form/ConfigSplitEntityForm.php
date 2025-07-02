<?php

namespace Drupal\config_split\Form;

use Drupal\config_split\Config\StatusOverride;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The entity form.
 */
class ConfigSplitEntityForm extends EntityForm {

  /**
   * The split status override service.
   *
   * @var \Drupal\config_split\Config\StatusOverride
   */
  protected $statusOverride;

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\config_split\Entity\ConfigSplitEntityInterface
   */
  protected $entity;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\config_split\Config\StatusOverride $statusOverride
   *   The split status override service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme list.
   */
  public function __construct(
    StatusOverride $statusOverride,
    StateInterface $state,
    ModuleExtensionList $moduleExtensionList,
    ThemeExtensionList $themeExtensionList,
  ) {
    $this->statusOverride = $statusOverride;
    $this->state = $state;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_split.status_override'),
      $container->get('state'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\config_split\Entity\ConfigSplitEntityInterface $config */
    $config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the Configuration Split setting."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\config_split\Entity\ConfigSplitEntity::load',
      ],
    ];

    $form['static_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Static Settings'),
      '#description' => $this->t("These settings can be overridden in settings.php"),
    ];
    $form['static_fieldset']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Describe this config split setting. The text will be displayed on the <em>Configuration Split setting</em> list page.'),
      '#default_value' => $config->get('description'),
    ];
    $form['static_fieldset']['storage'] = [
      '#type' => 'radios',
      '#title' => $this->t('Storage'),
      '#description' => $this->t('Select where you would like the split to be stored.<br /><em>Folder:</em> A specified directory on its own. Select this option if you want to decide the placement of your configuration directories.<br /><em>Collection:</em> A collection inside of the sync storage. Select this option if you want splits to be part of the main config, including in zip archives.<br /><em>Database:</em> A dedicated table in the database. Select this option if the split should not be shared (it will be included in database dumps).'),
      '#default_value' => $config->get('storage') ?? 'folder',
      '#options' => [
        'folder' => $this->t('Folder'),
        'collection' => $this->t('Collection'),
        'database' => $this->t('Database'),
      ],
    ];
    $form['static_fieldset']['folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder'),
      '#description' => $this->t('The directory, relative to the Drupal root, in which to save the filtered config. This is typically a sibling directory of what you defined as <code>$settings["config_sync_directory"]</code> in settings.php, for more information consult the README.<br/>Configuration related to the "filtered" items below will be split from the main configuration and exported to this folder.'),
      '#default_value' => $config->get('folder'),
      '#states' => [
        'visible' => [
          ':input[name="storage"]' => ['value' => 'folder'],
        ],
        'required' => [
          ':input[name="storage"]' => ['value' => 'folder'],
        ],
      ],
    ];
    $form['static_fieldset']['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('The weight to order the splits.'),
      '#default_value' => $config->get('weight'),
    ];

    $form['static_fieldset']['status_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Status'),
      '#description' => $this->t('Changing the status does not affect the other active config. You need to activate or deactivate the split for that.'),
    ];
    $overrideExample = '$config["config_split.config_split.' . ($config->get('id') ?? 'example') . '"]["status"] = ' . ($config->get('status') ? 'FALSE' : 'TRUE') . ';';
    $form['static_fieldset']['status_fieldset']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#description' => $this->t('Active splits get used to split and merge when importing and exporting config, this property is likely what you want to override in settings.php, for example: <code>@example</code>', ['@example' => $overrideExample]),
      '#default_value' => ($config->get('status') ? TRUE : FALSE),
    ];
    $overrideDefault = $this->statusOverride->getSplitOverride((string) $config->id());
    if ($overrideDefault === NULL) {
      $overrideDefault = 'none';
    }
    else {
      $overrideDefault = $overrideDefault ? 'active' : 'inactive';
    }
    $form['static_fieldset']['status_fieldset']['status_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Status override'),
      '#default_value' => $overrideDefault,
      '#options' => [
        'none' => $this->t('None'),
        'active' => $this->t('Active'),
        'inactive' => $this->t('Inactive'),
      ],
      '#description' => $this->t('This setting will override the status of the split with a config override saved in the database (state). The config override from settings.php will override this and take precedence.'),
    ];

    $form['complete_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Complete Split'),
      '#description' => $this->t("<em>Complete Split:</em>
       Configuration listed here will be removed from the sync directory and
       saved in the split storage instead. Modules will be removed from
       core.extension when exporting (and added back when importing with the
       split enabled.). Config dependencies are updated and their changes are
       recorded in a config patch saved in in the split storage."),
    ];

    $module_handler = $this->moduleExtensionList;
    $modules = array_map(function ($module) use ($module_handler) {
      return $module_handler->getName($module->getName());
    }, $module_handler->getList());
    // Add the existing ones with the machine name, so they do not get lost.
    foreach (array_diff_key($config->get('module'), $modules) as $missing => $weight) {
      $modules[$missing] = $missing;
    }

    // Sorting module list by name for making selection easier.
    asort($modules, SORT_NATURAL | SORT_FLAG_CASE);

    $multiselect_type = 'select';
    if (!$this->useSelectList()) {
      $multiselect_type = 'checkboxes';
      // Add the css library if we use checkboxes.
      $form['#attached']['library'][] = 'config_split/config-split-form';
    }

    $form['complete_fieldset']['module'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Modules'),
      '#description' => $this->t('Select modules to split. Configuration depending on the modules is changed as if the module would be uninstalled or automatically split off completely as well.'),
      '#options' => $modules,
      '#size' => 20,
      '#multiple' => TRUE,
      '#default_value' => array_keys($config->get('module')),
    ];

    // We should probably find a better way for this.
    $theme_handler = $this->themeExtensionList;
    $themes = array_map(function ($theme) use ($theme_handler) {
      return $theme_handler->getName($theme->getName());
    }, $theme_handler->getList());
    $form['complete_fieldset']['theme'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Themes'),
      '#description' => $this->t('Select themes to split.'),
      '#options' => $themes,
      '#size' => 5,
      '#multiple' => TRUE,
      '#default_value' => array_keys($config->get('theme')),
    ];
    // At this stage we do not support themes. @TODO: support themes.
    $form['complete_fieldset']['theme']['#access'] = FALSE;

    $options = array_combine($this->configFactory()->listAll(), $this->configFactory()->listAll());

    $form['complete_fieldset']['complete_picker'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Configuration items'),
      '#description' => $this->t('Select configuration to split. Configuration depending on split modules does not need to be selected here specifically.'),
      '#options' => $options,
      '#size' => 20,
      '#multiple' => TRUE,
      '#default_value' => array_intersect($config->get('complete_list'), array_keys($options)),
    ];
    $form['complete_fieldset']['complete_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional configuration'),
      '#description' => $this->t('Select additional configuration to split. One configuration key per line. You can use wildcards.'),
      '#size' => 5,
      '#default_value' => implode("\n", array_diff($config->get('complete_list'), array_keys($options))),
    ];

    $form['partial_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Partial Split'),
      '#description' => $this->t("<em>Partial Split:</em>
       Configuration listed here will be left untouched in the main sync
       directory. The <em>currently active</em> version will be compared to the
       config in the sync directory and what is different is saved to the split
       storage as a config patch.<br />
       Use this for configuration that is different on your site but which
       should also remain in the main sync directory."),
    ];

    $form['partial_fieldset']['partial_picker'] = [
      '#type' => $multiselect_type,
      '#title' => $this->t('Configuration items'),
      '#description' => $this->t('Select configuration to split partially.'),
      '#options' => $options,
      '#size' => 20,
      '#multiple' => TRUE,
      '#default_value' => array_intersect($config->get('partial_list'), array_keys($options)),
    ];
    $form['partial_fieldset']['partial_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional configuration'),
      '#description' => $this->t('Select additional configuration to partially split. One configuration key per line. You can use wildcards.'),
      '#size' => 5,
      '#default_value' => implode("\n", array_diff($config->get('partial_list'), array_keys($options))),
    ];

    $form['advanced_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#description' => $this->t("Advanced settings"),
    ];
    $form['advanced_fieldset']['stackable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stackable'),
      '#description' => $this->t('By default, splits are independent,
       but with this setting a split can become "stackable".
       This means that when completely splitting config, the data written to the split storage can be modified by splits with a lower weight.
       During the partial split the config is compared to the sync storage with all higher weight splits merged into it. <br />
       Use this if you are "extending" splits you create first. This has a performance impact.
       '),
      '#default_value' => (bool) $config->get('stackable'),
    ];
    $form['advanced_fieldset']['no_patching'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not patch dependents'),
      '#description' => $this->t('By default, dependents will be patched, by
      checking this option, splits will behave as they did in 1.x in which all
      dependencies will also fully split.
       '),
      '#default_value' => (bool) $config->get('no_patching'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $folder = $form_state->getValue('folder');
    if (static::isConflicting($folder)) {
      $form_state->setErrorByName('folder', $this->t('The split folder can not be in the sync folder.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Transform the values from the form to correctly save the entity.
    $extensions = $this->config('core.extension');
    // Add the configs modules so we can save inactive splits.
    $module_list = $extensions->get('module') + $this->entity->get('module');

    $moduleSelection = $this->readValuesFromPicker($form_state->getValue('module'));
    $form_state->setValue('module', array_intersect_key($module_list, $moduleSelection));

    $themeSelection = $this->readValuesFromPicker($form_state->getValue('theme'));
    $form_state->setValue('theme', array_intersect_key($extensions->get('theme'), $themeSelection));

    $selection = $this->readValuesFromPicker($form_state->getValue('complete_picker'));
    $form_state->setValue('complete_list', array_merge(
      array_keys($selection),
      $this->filterConfigNames($form_state->getValue('complete_text'))
    ));

    $selection = $this->readValuesFromPicker($form_state->getValue('partial_picker'));
    $form_state->setValue('partial_list', array_merge(
      array_keys($selection),
      $this->filterConfigNames($form_state->getValue('partial_text'))
    ));

    parent::submitForm($form, $form_state);

    $statusOverride = $form_state->getValue('status_override');
    $map = [
      'none' => NULL,
      'active' => TRUE,
      'inactive' => FALSE,
    ];
    if (!array_key_exists($statusOverride, $map)) {
      return;
    }
    $statusOverride = $map[$statusOverride];
    if ($statusOverride !== $this->statusOverride->getSplitOverride($this->entity->id())) {
      // Only update the override if it changed.
      $this->statusOverride->setSplitOverride($this->entity->id(), $statusOverride);
    }
  }

  /**
   * If the chosen or select2 module is active, the form must use select field.
   *
   * @return bool
   *   True if the form must use a select field
   */
  protected function useSelectList() {
    // Allow the setting to be overwritten with the drupal state.
    $stateOverride = $this->state->get('config_split_use_select');
    if ($stateOverride !== NULL) {
      // Honestly this is probably only useful in tests or if another module
      // comes along and does what chosen or select2 do.
      return (bool) $stateOverride;
    }

    // Modules make the select widget useful.
    foreach (['chosen', 'select2_all'] as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        return TRUE;
      }
    }

    // Fall back to checkboxes.
    return FALSE;
  }

  /**
   * Read values selected depending on widget used: select or checkbox.
   *
   * @param array $pickerSelection
   *   The form value array.
   *
   * @return array
   *   Array of selected values
   */
  protected function readValuesFromPicker(array $pickerSelection) {
    if ($this->useSelectList()) {
      $moduleSelection = $pickerSelection;
    }
    else {
      // Checkboxes return a value for each item. We only keep the selected one.
      $moduleSelection = array_filter($pickerSelection, function ($value) {
        return $value;
      });
    }

    return $moduleSelection;
  }

  /**
   * Filter text input for valid configuration names (including wildcards).
   *
   * @param string|string[] $text
   *   The configuration names, one name per line.
   *
   * @return string[]
   *   The array of configuration names.
   */
  protected function filterConfigNames($text) {
    if (!is_array($text)) {
      $text = explode("\n", $text);
    }

    foreach ($text as &$config_entry) {
      $config_entry = strtolower($config_entry);
    }

    // Filter out illegal characters.
    return array_filter(preg_replace('/[^a-z0-9_\.\-\*]+/', '', $text));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config_split = $this->entity;
    $status = $config_split->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Configuration Split setting.', [
          '%label' => $config_split->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Configuration Split setting.', [
          '%label' => $config_split->label(),
        ]));
    }
    $folder = $form_state->getValue('folder');
    if ($form_state->getValue('storage') === 'folder' && !empty($folder) && !file_exists($folder)) {
      $this->messenger()->addWarning(
        $this->t('The storage path "%path" for %label Configuration Split setting does not exist. Make sure it exists and is writable.',
          [
            '%label' => $config_split->label(),
            '%path' => $folder,
          ]
        ));
    }
    $form_state->setRedirectUrl($config_split->toUrl('collection'));

    return $status;
  }

  /**
   * Check whether the folder name conflicts with the default sync directory.
   *
   * @param string $folder
   *   The split folder name to check.
   *
   * @return bool
   *   True if the folder is inside the sync directory.
   */
  protected static function isConflicting($folder) {
    return strpos(rtrim($folder, '/') . '/', rtrim(Settings::get('config_sync_directory'), '/') . '/') !== FALSE;
  }

}
