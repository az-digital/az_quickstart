<?php

namespace Drupal\environment_indicator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines a Environment configuration entity.
 *
 * @ConfigEntityType(
 *   id = "environment_indicator",
 *   label = @Translation("Environment Switcher"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "access" = "Drupal\environment_indicator\EnvironmentIndicatorAccessControlHandler",
 *     "list_builder" = "Drupal\environment_indicator\EnvironmentIndicatorListBuilder",
 *     "form" = {
 *       "default" = "Drupal\environment_indicator\EnvironmentIndicatorForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer environment indicator settings",
 *   config_prefix = "switcher",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "machine",
 *     "label" = "name",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/development/environment-indicator/manage/{environment_indicator}",
 *     "edit-permissions-form" = "/admin/people/permissions/{user_role}",
 *     "delete-form" = "/admin/config/development/environment-indicator/manage/{environment_indicator}/delete",
 *     "collection" = "/admin/config/development/environment-indicator"
 *   },
 *   config_export = {
 *     "machine",
 *     "description",
 *     "name",
 *     "url",
 *     "weight",
 *     "fg_color",
 *     "bg_color",
 *   }
 * )
 */
class EnvironmentIndicator extends ConfigEntityBase implements ConfigEntityInterface {

  /**
   * The machine-readable ID for the configurable.
   *
   * @var string
   */
  public $machine;

  /**
   * The human-readable label for the configurable.
   *
   * @var string
   */
  public $name;

  /**
   * The URL to switch to.
   *
   * @var string
   */
  public $url;

  /**
   * The switchers link weight.
   *
   * @var string
   */
  public $weight;

  /**
   * The color code for the indicator.
   *
   * @var string
   */
  public $fg_color;

  /**
   * The color code for the indicator.
   *
   * @var string
   */
  public $bg_color;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('machine');
  }

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return $this->get('name');
  }

  /**
   * Gets the URL.
   *
   * @return string
   *   The URL to switch to.
   */
  public function getUrl() {
    return $this->get('url');
  }

  /**
   * Gets the weight.
   *
   * @return string
   *   The weight of switcher environment.
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * Gets the foreground color.
   *
   * @return string
   *   The color code for the indicator.
   */
  public function getFgColor() {
    return $this->get('fg_color');
  }

  /**
   * Gets the background color.
   *
   * @return string
   *   The color code for the indicator.
   */
  public function getBgColor() {
    return $this->get('bg_color');
  }

  /**
   * Gets the machine name.
   *
   * @param string $machine
   *   The machine-readable ID for the configurable.
   */
  public function setMachine($machine) {
    $this->set('machine', $machine);
  }

  /**
   * Sets the name.
   *
   * @param string $name
   *   The environment name.
   */
  public function setName($name) {
    $this->set('name', $name);
  }

  /**
   * Sets the weight.
   *
   * @param string $weight
   *   The environment link weight.
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
  }

  /**
   * Sets the URL.
   *
   * @param string $url
   *   The environment url.
   */
  public function setUrl($url) {
    $this->set('url', $url);
  }

  /**
   * Sets the foreground color.
   *
   * @param string $fg_color
   *   The foreground color.
   */
  public function setFgColor($fg_color) {
    $this->set('fg_color', $fg_color);
  }

  /**
   * Sets the background color.
   *
   * @param string $bg_color
   *   The background color.
   */
  public function setBgColor($bg_color) {
    $this->set('bg_color', $bg_color);
  }

}
