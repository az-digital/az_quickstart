<?php

/**
 * @file
 * Webform install helper functions.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Render\Element;
use Drupal\Core\Serialization\Yaml;
use Drupal\system\Entity\Action;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Update admin settings to reflect changes in the default settings.
 *
 * If you are moving or updating any admin settings this must be explicitly
 * done via an update hook.
 *
 * @param bool $reset
 *   If set TRUE old admin settings will be completely deleted.
 *
 * @see drush_webform_repair()
 */
function _webform_update_admin_settings($reset = FALSE) {
  // Make sure to purge the config cache before updating any config.
  // This ensure that any config schema changes are loaded.
  \Drupal::service('cache.config')->deleteAll();

  $admin_config = \Drupal::configFactory()->getEditable('webform.settings');

  $current_settings = $admin_config->getRawData();
  $admin_settings = Yaml::decode(file_get_contents(__DIR__ . '/../config/install/webform.settings.yml'));
  // Note, admin settings are always grouped into associative array,
  // except for the langcode.
  foreach ($admin_settings as $group => $settings) {
    // Handle the rare case the we are adding a new group the admin settings.
    if (!isset($current_settings[$group])) {
      continue;
    }

    // Completely copy the format, langcode, and third_party_settings.
    if (in_array($group, ['format', 'langcode', 'third_party_settings'])) {
      if (isset($current_settings[$group])) {
        $admin_settings[$group] = $current_settings[$group];
      }
    }
    elseif ($reset) {
      // Copy only group's settings that are defined in admin settings.
      // This will cause old settings to be completely deleted.
      foreach ($settings as $name => $value) {
        if (isset($current_settings[$group][$name])) {
          $admin_settings[$group][$name] = $current_settings[$group][$name];
        }
      }
    }
    else {
      // Loop through the group's settings and apply all existing settings to
      // the default admin settings.
      foreach ($current_settings[$group] as $name => $value) {
        $admin_settings[$group][$name] = $value;
      }
    }
  }

  // If not reset, make sure all the current settings are preserved.
  if (!$reset) {
    $admin_settings += $current_settings;
  }

  $admin_config->setData($admin_settings)->save();
}

/**
 * Update webform settings to reflect changes in the default settings.
 *
 * This function can be used to apply new webform settings to all existing
 * webforms.
 *
 * @see \Drupal\webform\Entity\Webform::setSettings
 */
function _webform_update_webform_settings() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();
    $data = _webform_update_webform_setting($data);
    $webform_config->setData($data)->save();
  }
}

/**
 * Update webform setting to reflect changes in the default settings.
 *
 * @param array $data
 *   A webform's raw configuration data from webform.webform.*.yml.
 *
 * @return array
 *   Updated raw configuration data.
 */
function _webform_update_webform_setting(array $data) {
  $default_properties = [
    'uuid' => NULL,
    'langcode' => 'en',
    'status' => WebformInterface::STATUS_OPEN,
    'dependencies' => [],
    'third_party_settings' => [],
    'open' => NULL,
    'close' => NULL,
    'weight' => 0,
    'uid' => '',
    'template' => FALSE,
    'archive' => FALSE,
    'id' => '',
    'title' => '',
    'description' => '',
    'categories' => [],
    'elements' => '',
    'css' => '',
    'javascript' => '',
    'settings' => [],
    'access' => [],
    'handlers' => [],
    'variants' => [],
  ];

  $default_settings = Webform::getDefaultSettings();

  // Always apply the default properties.
  $properties = $default_properties;
  // Now apply defined properties.
  foreach ($data as $name => $value) {
    $properties[$name] = $value;
  }
  // Empty settings.
  if (empty($properties['third_party_settings'])) {
    unset($properties['third_party_settings']);
  }

  // Set properties.
  $data = _webform_update_webform_setting_properties($properties);

  // Always apply the default settings.
  $settings = $default_settings;
  // Now apply custom settings.
  foreach ($data['settings'] as $name => $value) {
    $settings[$name] = $value;
  }
  // Set settings.
  $data['settings'] = _webform_update_webform_setting_settings($settings);

  // Set access.
  /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
  $access_rules_manager = \Drupal::service('webform.access_rules_manager');
  $data['access'] += $access_rules_manager->getDefaultAccessRules();

  return $data;
}

/**
 * Make sure outdated webform properties are up-to-date.
 *
 * @param array $properties
 *   An associative array of webform properties.
 *
 * @return array
 *   Updated webform properties.
 */
function _webform_update_webform_setting_properties(array $properties) {
  // Issue #3339769: Improve Webform categorization to support assigning multiple categories and default categories.
  if (isset($properties['category'])) {
    $properties['categories'] = [];
    if (!empty($properties['category'])) {
      $properties['categories'] = array_values(array_filter(array_merge(
        $properties['categories'],
        (array) $properties['category']
      )));
    }
    unset($properties['category']);
  }

  return $properties;
}

/**
 * Make sure outdated webform settings are up-to-date.
 *
 * @param array $settings
 *   An associative array of webform settings.
 *
 * @return array
 *   Updated webform settings.
 */
function _webform_update_webform_setting_settings(array $settings) {
  // Issue #3153184: Allow a webform displayed as a page to have a custom theme.
  // Convert 'page_admin_theme' setting to 'page_theme_name' setting.
  // @see webform_update_8196()
  if (isset($settings['page_admin_theme'])) {
    if (empty($settings['page_theme_name'])) {
      $settings['page_theme_name'] = ($settings['page_admin_theme'])
        ? \Drupal::configFactory()->get('system.theme')->get('admin')
        : '';
    }
    unset($settings['page_admin_theme']);
  }

  // Issue #3088780: Add the ability to disable user IP tracking for all forms.
  // Convert 'form_remote_addr' to 'form_disable_remote_addr'.
  if (isset($settings['form_remote_addr'])) {
    $settings['form_disable_remote_addr'] = $settings['form_remote_addr'] ? FALSE : TRUE;
    unset($settings['form_remote_addr']);
  }

  // Issue #3151506: Remove .webform-elements wrapper around elements.
  if (isset($settings['form_elements_attributes'])) {
    unset($settings['form_elements_attributes']);
  }

  return $settings;
}

/**
 * Update webform handler settings to reflect changes in the handler's settings.
 *
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 */
function _webform_update_webform_handler_settings() {
  // Issue #2863986: Allow updating modules with new service dependencies.
  \Drupal::service('kernel')->rebuildContainer();

  // Get the default configuration (aka settings) for all handlers provided
  // by the Webform module.
  /** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
  $handler_manager = \Drupal::service('plugin.manager.webform.handler');
  $definitions = $handler_manager->getDefinitions();
  $default_handler_settings = [];
  foreach ($definitions as $plugin_id => $definition) {
    if (strpos($definition['provider'], 'webform_test_') === 0 || in_array($definition['provider'], ['webform', 'webform_scheduled_email'])) {
      $default_handler_settings[$plugin_id] = $handler_manager->createInstance($plugin_id)->defaultConfiguration();
    }
  }

  $config_factory = \Drupal::configFactory();
  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);

    // Get data.
    $data = $webform_config->getRawData();

    // Apply the default handler settings.
    $has_handler = FALSE;
    if (!empty($data['handlers'])) {
      foreach ($data['handlers'] as &$handler) {
        if (!isset($default_handler_settings[$handler['id']])) {
          continue;
        }

        $settings = $default_handler_settings[$handler['id']];
        foreach ($handler['settings'] as $settings_key => $setting_value) {
          $settings[$settings_key] = $setting_value;
        }

        if ($handler['settings'] !== $settings) {
          $has_handler = TRUE;
          $handler['settings'] = $settings;
        }
      }

      if ($has_handler) {
        $webform_config->setData($data)->save();
      }
    }
  }
}

/**
 * Update webform options setting to reflect changes in the default settings.
 *
 * This function can be used to apply new webform options configuration to
 * all existing webforms options.
 *
 * @see \Drupal\webform\Entity\WebformOptions
 */
function _webform_update_options_settings() {
  $default_properties = [
    'langcode' => 'en',
    'status' => TRUE,
    'dependencies' => [],
    'id' => '',
    'label' => '',
    'category' => '',
    'options' => '',
  ];

  // Update 'webform.webform_options.*' configuration.
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform_options.') as $webform_config_name) {
    $webform_options_config = $config_factory->getEditable($webform_config_name);

    // Get data.
    $data = $webform_options_config->getRawData();

    // Always apply the default properties.
    $properties = $default_properties;
    // Now apply defined properties.
    foreach ($data as $name => $value) {
      $properties[$name] = $value;
    }

    // Set properties.
    $data = $properties;

    // Save data.
    $webform_options_config->setData($data)->save();
  }
}

/**
 * Update or install any new system.actions.* config entities.
 */
function _webform_update_actions() {
  $files = \Drupal::service('file_system')->scanDirectory(__DIR__ . '/../config', '/^system.action..*\.yml$/');
  foreach ($files as $path => $file) {
    $action_id = str_replace('system.action.', '', $file->name);
    $action = Action::load($action_id);
    if (!$action) {
      // Install new action.
      $data = Yaml::decode(file_get_contents($path));
      $action = Action::create($data);
      $action->trustData()->save();
    }
  }
}

/**
 * Update webform field storage definitions.
 *
 * @see \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem::schema
 */
function _webform_update_field_storage_definitions() {
  $manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\field\FieldStorageConfigInterface[] $fields */
  $fields = \Drupal::entityTypeManager()
    ->getStorage('field_storage_config')
    ->loadByProperties(['type' => 'webform']);
  foreach ($fields as $field) {
    $field_name = $field->getName();
    $entity_type = $field->getTargetEntityTypeId();
    $manager->updateFieldStorageDefinition($manager->getFieldStorageDefinition($field_name, $entity_type));
  }
}

/**
 * Update webform submission storage schema.
 *
 * @see \Drupal\webform\WebformSubmissionStorageSchema
 */
function _webform_update_webform_submission_storage_schema() {
  $manager = \Drupal::entityDefinitionUpdateManager();
  if (!$manager->getEntityType('webform_submission')) {
    $type = \Drupal::entityTypeManager()->getDefinition('webform_submission');
    $manager->installEntityType($type);
  }
  $manager->updateEntityType($manager->getEntityType('webform_submission'));
}

/**
 * Update webform submission translations.
 */
function _webform_update_webform_submission_translation() {
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('language.content_settings.webform_submission.') as $config_name) {
    $config = $config_factory->getEditable($config_name);
    // Make sure 'Default language' (default_langcode) and
    // 'Show language selector on create and edit pages' (language_alterable)
    // have not been customized.
    if ($config->get('default_langcode') === 'site_default'
      && $config->get('language_alterable') === FALSE) {
      $config_factory->getEditable($config_name)->delete();
    }
  }
}

/**
 * Replace string in webform.settings.yml and webform.webform.*.yml.
 *
 * @param string $search
 *   String to be search for.
 * @param string $replace
 *   String to be replace with.
 */
function _webform_update_string_replace($search, $replace) {
  $config_factory = \Drupal::configFactory();

  // Update 'webform.settings' configuration.
  $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
  $yaml = Yaml::encode($settings_config->getRawData());
  if (strpos($yaml, $search) !== FALSE) {
    $yaml = str_replace($search, $replace, $yaml);
    $settings_config->setData(Yaml::decode($yaml));
    $settings_config->save();
  }

  // Update 'webform.webform.*' configuration.
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $yaml = Yaml::encode($webform_config->getRawData());
    if (strpos($yaml, $search) !== FALSE) {
      $yaml = str_replace($search, $replace, $yaml);
      $webform_config->setData(Yaml::decode($yaml));
      $webform_config->save();
    }
  }
}

/**
 * Clear/remove selected webform element properties.
 *
 * @param array $properties
 *   An associative array of webform element properties.
 */
function _webform_update_elements_clear_properties(array $properties) {
  $pattern = '/(?:' . implode('|', array_keys($properties)) . ')/';
  $config_factory = \Drupal::configFactory();
  foreach ($config_factory->listAll('webform.webform.') as $webform_config_name) {
    $webform_config = $config_factory->getEditable($webform_config_name);
    $data = $webform_config->getRawData();

    // Make sure elements contains the properties.
    if (!preg_match($pattern, $data['elements'])) {
      continue;
    }

    $elements = Yaml::decode($data['elements']);
    _webform_update_elements_clear_properties_recursive($elements, $properties);
    $data['elements'] = Yaml::encode($elements);

    $webform_config->setData($data);
    $webform_config->save();
  }
}

/**
 * Recursively clear/remove selected webform element properties.
 *
 * @param array $element
 *   An element.
 * @param array $properties
 *   An associative array of webform element properties.
 */
function _webform_update_elements_clear_properties_recursive(array &$element, array $properties) {
  foreach ($properties as $property_name => $property_value) {
    if (isset($element[$property_name]) && $element[$property_name] === $property_value) {
      unset($element[$property_name]);
    }
  }
  foreach (Element::children($element) as $key) {
    if (is_array($element[$key])) {
      _webform_update_elements_clear_properties_recursive($element[$key], $properties);
    }
  }
}

/**
 * Update the Webform module's default hidden text format and editor.
 *
 * Determines if the CKEditor 5 or 4 module installed and imports the
 * appropriate configuration file.
 */
function _webform_update_html_editor() {
  // Make sure the Webform module is installed, since we do call this function
  // via webform_requirements().
  if (!\Drupal::moduleHandler()->moduleExists('webform')) {
    return;
  }

  /** @var \Drupal\Core\Config\StorageInterface $config_storage */
  $config_storage = \Drupal::service('config.storage');

  $module_path = \Drupal::service('extension.list.module')->getPath('webform');
  $ckeditor_file_name = 'editor.editor.' . WebformHtmlEditor::DEFAULT_FILTER_FORMAT;

  $names = [];

  // Install the webform default filter format.
  $names['optional'] = 'filter.format.' . WebformHtmlEditor::DEFAULT_FILTER_FORMAT;

  // Get the current CKEditor version.
  $current_version = \Drupal::config($ckeditor_file_name)->get('editor') ?: NULL;
  if (\Drupal::moduleHandler()->moduleExists('ckeditor5')) {
    $ckeditor_version = 'ckeditor5';
  }
  elseif (\Drupal::moduleHandler()->moduleExists('ckeditor')) {
    $ckeditor_version = 'ckeditor';
  }
  else {
    $ckeditor_version = NULL;
  }

  // If the changing or deleting the CKEditor, we need delete the existing file.
  if ($current_version !== $ckeditor_version) {
    \Drupal::configFactory()
      ->getEditable($ckeditor_file_name)
      ->delete();
  }

  // Install new CKEditor version.
  if ($ckeditor_version) {
    $names["optional/$ckeditor_version"] = $ckeditor_file_name;
  }

  // Import config files but never overwrite the existing config file.
  foreach ($names as $directory => $name) {
    $source = new FileStorage("$module_path/config/$directory");
    if (!$config_storage->exists($name)) {
      // Use the entity API to create config entities.
      $entity_type_id = \Drupal::service('config.manager')->getEntityTypeIdByName($name);
      if ($entity_type_id) {
        \Drupal::entityTypeManager()
          ->getStorage($entity_type_id)
          ->createFromStorageRecord($source->read($name))
          ->save();
      }
      else {
        $config_storage->write($name, $source->read($name));
      }
    }
  }
}
