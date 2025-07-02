<?php

namespace Drupal\config_distro_ignore\Plugin\ConfigFilter;

use Drupal\config_distro\Plugin\ConfigDistroFilterBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a ignore filter that resets config to the active one.
 *
 * @ConfigFilter(
 *   id = "config_distro_ignore",
 *   label = "Config Distro Ignore",
 *   storages = {"config_distro.storage.distro"},
 *   weight = 10000
 * )
 */
class DistroIgnoreFilter extends ConfigDistroFilterBase implements ContainerFactoryPluginInterface {

  const HASH_SEPARATOR = '::';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $container->get('config.factory')->get('config_distro_ignore.settings');
    // Set the lists in the plugin configuration.
    $configuration['all_collections'] = $config->get('all_collections');
    $configuration['default_collection'] = $config->get('default_collection');
    $configuration['custom_collections'] = $config->get('custom_collections');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Hash the config to know if it changed.
   *
   * @param mixed $config
   *   The configuration array.
   *
   * @return string
   *   The hash.
   */
  public static function hashConfig($config) {
    return md5(serialize($config));
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    // Read from the active storage when the name is in the ignored list.
    if ($this->matchConfigName($name)) {
      return $this->activeRead($name, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // A name exists if it is ignored and exists in the active storage.
    return $exists || ($this->matchConfigName($name) && $this->getSourceStorage()->exists($name));
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    // Limit the names which are read from the active storage.
    $names = array_filter($names, [$this, 'matchConfigName']);
    foreach ($names as $name) {
      $data[$name] = $this->activeRead($name, $data[$name]);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $active_names = $this->getSourceStorage()->listAll($prefix);
    // Filter out only ignored config names.
    $active_names = array_filter($active_names, [$this, 'matchConfigName']);

    // Return the data with the active names which are ignored merged in.
    return array_unique(array_merge($data, $active_names));
  }

  /**
   * Match a config name against the list of ignored config.
   *
   * @param string $config_name
   *   The name of the config to match against all ignored config.
   *
   * @return bool
   *   True, if the config is to be ignored, false otherwise.
   */
  protected function matchConfigName($config_name) {

    if (in_array($config_name, $this->configuration['all_collections'])) {
      return TRUE;
    }

    if (count($this->getIgnoredHash($config_name))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks whether the config name should be ignored.
   *
   * @param string $pattern
   *   Shell wildcard pattern from ignore settings.
   * @param string $config_name
   *   Configuration name.
   *
   * @return bool
   *   Whether the config name should be ignored.
   */
  protected function shouldIgnore($pattern, $config_name) {
    // First convert shell wildcard pattern to regexp pattern.
    $expr = '/^' . strtr(preg_quote($pattern, '/'), ['\*' => '.*', '\?' => '.']) . '$/';

    // Check with new regexp.
    return (bool) preg_match($expr, $config_name);
  }

  /**
   * Get the hashes of the config to ignore for the current collection.
   *
   * @param string $config_name
   *   The config name.
   *
   * @return string[]
   *   The hashes to ignore, an empty string means unconditionally ignore.
   */
  protected function getIgnoredHash($config_name) {
    $hashes = [];
    $collection = $this->getSourceStorage()->getCollectionName();
    if ($collection == StorageInterface::DEFAULT_COLLECTION) {
      foreach ($this->configuration['default_collection'] as $name) {
        // Split the ignore settings so that we can ignore individual keys.
        $ignore = explode(self::HASH_SEPARATOR, $name);
        if ($this->shouldIgnore($ignore[0], $config_name)) {
          if (isset($ignore[1])) {
            $hashes[] = $ignore[1];
          }
          else {
            $hashes[] = '';
          }
        }
      }
    }
    else {
      $collection_parts = explode('.', $collection);
      $custom_config = $this->configuration['custom_collections'];
      foreach ($collection_parts as $part) {
        $custom_config = $custom_config[$part];
      }

      foreach ($custom_config as $name) {
        // Split the ignore settings so that we can ignore individual keys.
        $ignore = explode(self::HASH_SEPARATOR, $name);
        if ($this->shouldIgnore($ignore[0], $config_name)) {
          if (isset($ignore[1])) {
            $hashes[] = $ignore[1];
          }
          else {
            $hashes[] = '';
          }
        }
      }
    }

    return $hashes;
  }

  /**
   * Read from the active configuration.
   *
   * @param string $name
   *   The name of the configuration to read.
   * @param mixed $data
   *   The data to be filtered.
   *
   * @return mixed
   *   The data filtered or read from the active storage.
   */
  protected function activeRead($name, $data) {

    if (in_array($name, $this->configuration['all_collections'])) {
      return $this->getSourceStorage()->read($name);
    }

    $hash = self::hashConfig($data);
    $hashes = $this->getIgnoredHash($name);
    if (in_array('', $hashes) || in_array($hash, $hashes)) {
      return $this->getSourceStorage()->read($name);
    }

    return $data;
  }

}
