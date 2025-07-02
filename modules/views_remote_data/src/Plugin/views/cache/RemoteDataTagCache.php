<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\cache;

use Drupal\views\Plugin\views\cache\Tag;

/**
 * Defines a tag-based cache plugin for use with remote data views.
 *
 * The base class only supports generating result keys based off of the
 * database query.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "views_remote_data_tag",
 *   title = @Translation("Tag-based caching (Remote data)"),
 *   help = @Translation("Cache results until the associated cache tags are invalidated.")
 * )
 */
final class RemoteDataTagCache extends Tag {

  use RemoteDataCachePluginTrait;

}
