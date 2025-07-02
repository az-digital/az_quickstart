<?php

namespace Drupal\linkit\Utility;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides helper to operate on URIs.
 */
class LinkitHelper {

  /**
   * Load the entity referenced by an entity scheme uri.
   *
   * @param string $uri
   *   An internal uri string representing an entity path, such as
   *   "entity:node/23".
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The most appropriate translation of the entity that matches the given
   *   uri, or NULL if could not match any entity.
   */
  public static function getEntityFromUri($uri) {
    // Strip out potential query and fragment from the uri.
    $uri = strtok(strtok($uri, "?"), "#");
    // Remove the schema, if any. Otherwise, remove the forwarding "/".
    if (strpos($uri, 'entity:') !== FALSE) {
      $uri_parts = explode(':', $uri);
      $uri = $uri_parts[1] ?? $uri;
    }
    else {
      $uri = trim($uri, '/');
    }

    if ($uri) {
      $parts = explode('/', $uri, 2);
      if (count($parts) === 2) {
        [$entity_type, $entity_id] = $parts;
        $entity_manager = \Drupal::entityTypeManager();
        if ($entity_manager->hasDefinition($entity_type)) {
          if ($entity = $entity_manager->getStorage($entity_type)->load($entity_id)) {
            return \Drupal::service('entity.repository')->getTranslationFromContext($entity);
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Returns a processed uri with a proper scheme (if applicable).
   *
   * Turns the internal links into uri strings.
   *
   * @param string $input
   *   The raw (or processed) input.
   *
   * @return string|null
   *   The uri string or null if the input is empty.
   */
  public static function uriFromUserInput($input) {
    if (empty($input)) {
      return NULL;
    }

    $host = parse_url($input, PHP_URL_HOST);
    $scheme = parse_url($input, PHP_URL_SCHEME);

    if ($scheme == 'mailto') {
      return $input;
    }

    if ($host && UrlHelper::isExternal($input)) {
      if (UrlHelper::externalIsLocal($input, \Drupal::request()->getSchemeAndHttpHost())) {
        // The link points to this domain. Make it relative to perform an entity
        // lookup.
        $host_end = strpos($input, $host) + strlen($host);
        $input = substr($input, $host_end);
      }
      else {
        // This link is really external.
        return $input;
      }
    }

    // Make sure the URI starts with a slash, otherwise the Url's factory
    // methods will throw exceptions.
    $starts_with_hash = strpos($input, '#') === 0;
    $starts_with_a_slash = strpos($input, '/') === 0;
    $is_front = substr($input, 0, 7) === '<front>';
    $is_nolink = substr($input, 0, 14) === 'route:<nolink>';
    if (!$scheme && !$is_front && !$is_nolink && !$starts_with_a_slash && !$starts_with_hash) {
      $input = "/$input";
    }
    // - '<front>' -> '/'
    // - '<front>#foo' -> '/#foo'
    if ($is_front) {
      $input = '/' . substr($input, strlen('<front>'));
    }

    $entity = self::getEntityFromUserInput($input);
    if ($entity) {
      return 'entity:' . $entity->getEntityTypeId() . '/' . $entity->id() . static::getQueryAndFragment($input);
    }

    // It's a relative link. If it's a file, store it as `base:`. Otherwise it's
    // most likely internal.
    $public_files_dir = \Drupal::service('stream_wrapper_manager')
      ->getViaScheme('public')
      ->getDirectoryPath();

    if (!empty($public_files_dir) && strpos($input, "/$public_files_dir") === 0) {
      return "base:$input";
    }
    $scheme = parse_url($input, PHP_URL_SCHEME);
    // Check if the input already contains a scheme.
    if (!empty($scheme)) {
      return $input;
    }

    return "internal:$input";
  }

  /**
   * Tries to convert an uri into an entity in multiple ways.
   *
   * @param string $input
   *   A uri or a path.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if found, null otherwise.
   */
  public static function getEntityFromUserInput($input) {
    $scheme = parse_url($input, PHP_URL_SCHEME);

    // Check if it's an entity URI (e.g. entity:node/1).
    if (($scheme === 'entity' || !$scheme) && ($entity = static::getEntityFromUri($input))) {
      return $entity;
    }

    // If not, it can be a path pointing to an entity.
    if (!$scheme) {
      // Which can be hidden behind an alias in any of the site's languages.
      $input = 'internal:' . static::getPathByAlias($input);
    }

    try {
      $route_name = Url::fromUri($input)->getRouteName();
      $params = array_filter(Url::fromUri($input)->getRouteParameters());
      foreach ($params as $possibly_an_entity_type => $possibly_an_entity_id) {
        // Return only the entity, if this is a canonical route.
        if ($route_name === 'entity.' . $possibly_an_entity_type . '.canonical') {
          $entity = \Drupal::entityTypeManager()
            ->getStorage($possibly_an_entity_type)
            ->load($possibly_an_entity_id);
          if (!($entity instanceof EntityInterface)) {
            return NULL;
          }
          return \Drupal::service('entity.repository')
            ->getTranslationFromContext($entity);
        }
      }
    }
    catch (\Exception $e) {
      // Or not.
    }

    return NULL;
  }

  /**
   * Tries to translate the given raw url path into an internal one.
   *
   * @param string $input
   *   Raw URL string consisting of a path and, optionally, query and fragment.
   *
   * @return string
   *   The internal path if any matched. The input string otherwise.
   */
  public static function getPathByAlias($input) {
    $config = \Drupal::config('language.negotiation');
    /** @var \Drupal\path_alias\AliasManagerInterface $path_alias_manager */
    $path_alias_manager = \Drupal::service('path_alias.manager');
    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = \Drupal::service('language_manager');

    $input_path = parse_url($input, PHP_URL_PATH);
    foreach ($language_manager->getLanguages() as $language) {
      if ($prefix = $config->get('url.prefixes.' . $language->getId())) {
        // Strip the language prefix.
        $input_path = preg_replace("/^\/$prefix\//", '/', $input_path);
      }
      $path_resolved = $path_alias_manager->getPathByAlias($input_path, $language->getId());
      if ($path_resolved !== $input_path) {
        return $path_resolved . static::getQueryAndFragment($input);
      }
    }

    return $input;
  }

  /**
   * Returns the query and fragment part of a given URL string.
   *
   * @param string $input
   *   An arbitrary URL.
   *
   * @return string
   *   The query and fragment parts or an empty string.
   */
  public static function getQueryAndFragment($input) {
    $result = '';
    if ($query = parse_url($input, PHP_URL_QUERY)) {
      $result .= "?$query";
    }
    if ($fragment = parse_url($input, PHP_URL_FRAGMENT)) {
      $result .= "#$fragment";
    }
    return $result;
  }

}
