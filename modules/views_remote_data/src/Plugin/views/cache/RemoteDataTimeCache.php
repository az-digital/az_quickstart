<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\cache;

use Drupal\views\Plugin\views\cache\Time;

/**
 * Defines a time-based cache plugin for use with remote data views.
 *
 * The base class only supports generating result keys based off of the
 * database query.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "views_remote_data_time",
 *   title = @Translation("Time-based caching (Remote data)"),
 *   help = @Translation("Cache results for a predefined time period.")
 * )
 */
final class RemoteDataTimeCache extends Time {

  use RemoteDataCachePluginTrait;

}
