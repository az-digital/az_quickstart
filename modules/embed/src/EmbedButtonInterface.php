<?php

namespace Drupal\embed;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a embed button entity.
 */
interface EmbedButtonInterface extends ConfigEntityInterface {

  /**
   * Returns the associated embed type.
   *
   * @return string
   *   Machine name of the embed type.
   */
  public function getTypeId();

  /**
   * Returns the label of the associated embed type.
   *
   * @return string
   *   Human readable label of the embed type.
   */
  public function getTypeLabel();

  /**
   * Returns the plugin of the associated embed type.
   *
   * @return \Drupal\embed\EmbedType\EmbedTypeInterface
   *   The plugin of the embed type.
   */
  public function getTypePlugin();

  /**
   * Gets the value of an embed type setting.
   *
   * @param string $key
   *   The setting name.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getTypeSetting($key, $default = NULL);

  /**
   * Gets all embed type settings.
   *
   * @return array
   *   An array of key-value pairs.
   */
  public function getTypeSettings();

  /**
   * Returns the button's icon file.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity of the button icon.
   *
   * @deprecated in embed:8.x-1.2 and is removed from embed:2.0.0. Use
   *   \Drupal\embed\EmbedButtonInterface::getIconUrl() instead.
   *
   * @see https://www.drupal.org/project/embed/issues/3039598
   */
  public function getIconFile();

  /**
   * Returns the URL of the button's icon.
   *
   * If no icon file is associated with this Embed Button entity, the embed type
   * plugin's default icon is used.
   *
   * @return string
   *   The URL of the button icon.
   */
  public function getIconUrl();

  /**
   * Convert a file on the filesystem to encoded data.
   *
   * @param string $uri
   *   An image file URI.
   *
   * @return array
   *   An array of data about the encoded image including:
   *     - uri: The URI of the file.
   *     - data: The base-64 encoded contents of the file.
   */
  public static function convertImageToEncodedData($uri);

  /**
   * Convert image encoded data to a file on the filesystem.
   *
   * @param array $data
   *   An array of data about the encoded image including:
   *     - uri: The URI of the file.
   *     - data: The base-64 encoded contents of the file.
   *
   * @return string
   *   An image file URI.
   */
  public static function convertEncodedDataToImage(array $data);

}
