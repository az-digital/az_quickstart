<?php

namespace Drupal\devel_generate\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a DevelGenerate annotation object.
 *
 * DevelGenerate handle the bulk creation of entites.
 *
 * Additional annotation keys for DevelGenerate can be defined in
 * hook_devel_generate_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\devel_generate\DevelGeneratePluginManager
 * @see \Drupal\devel_generate\DevelGenerateBaseInterface
 */
class DevelGenerate extends Plugin {
  /**
   * The human-readable name of the DevelGenerate type.
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * A short description of the DevelGenerate type.
   *
   * @ingroup plugin_translatable
   */
  public Translation $description;

  /**
   * A url to access the plugin settings form.
   */
  public string $url;

  /**
   * The permission required to access the plugin settings form.
   */
  public string $permission;

  /**
   * The name of the DevelGenerate class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   */
  public string $class;

  /**
   * An array of settings passed to the DevelGenerate settingsForm.
   *
   * The keys are the names of the settings and the values are the default
   * values for those settings.
   */
  public array $settings = [];

  /**
   * Modules that should be enabled in order to make the plugin discoverable.
   */
  public array $dependencies = [];

}
