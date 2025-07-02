<?php

namespace Drupal\config_filter;

/**
 * Interface for a ConfigFilterManager.
 */
interface ConfigFilterManagerInterface {

  /**
   * Get the applicable filters for given storage names.
   *
   * @param string[] $storage_names
   *   The names of the storage plugins apply to.
   * @param string[] $excluded
   *   The ids of filters to exclude.
   *
   * @return \Drupal\config_filter\Config\StorageFilterInterface[]
   *   The configured filter instances, keyed by filter id.
   */
  public function getFiltersForStorages(array $storage_names, array $excluded = []);

  /**
   * Get a configured filter instance by (plugin) id.
   *
   * @param string $id
   *   The plugin id of the filter to load.
   *
   * @return \Drupal\config_filter\Config\StorageFilterInterface|null
   *   The ConfigFilter.
   */
  public function getFilterInstance($id);

}
