<?php

namespace Drupal\blazy\internals;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides internal content utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Content extends Multimedia {

  /**
   * Returns a message if access to view the entity is denied.
   */
  public static function denied($entity): array {
    if (!$entity instanceof EntityInterface) {
      return [];
    }

    if (!$entity->access('view')) {
      $parameters = [
        '@label' => $entity->getEntityType()->getSingularLabel(),
        '@id' => $entity->id(),
        '@langcode' => $entity->language()->getId(),
        '@title' => $entity->label(),
      ];
      $restricted_access_label = $entity->access('view label')
       ? new FormattableMarkup('@label @id (@title)', $parameters)
       : new FormattableMarkup('@label @id', $parameters);
      return ['#markup' => $restricted_access_label];
    }
    return [];
  }

  /**
   * Returns a formatted title.
   */
  public static function formatTitle($value, $url, array $settings): array {
    $delimiter = $settings['delimiter'] ?? NULL;
    $tag       = $settings['tag'] ?? NULL;
    $break     = $settings['break'] ?? FALSE;
    $title     = $value;
    $subtitle  = NULL;

    if ($delimiter) {
      if ($found = self::getDelimiter($value, $delimiter)) {
        [$title, $subtitle] = array_pad(array_map('trim', explode($found, $value, 2)), 2, NULL);
      }

      if ($subtitle) {
        if ($tag) {
          $linebreak = $break ? '<br />' : ' ';
          $title .= $linebreak . '<' . $tag . '>' . $subtitle . '</' . $tag . '>';
        }
        else {
          $title .= '<br />' . $subtitle;
        }
      }
    }

    $tags = ['span', 'em', 'b', 'i', 'strong', 'br', 'small'];
    $view_value = [
      '#markup' => $title,
      '#allowed_tags' => array_merge($tags, [$tag]),
    ];

    if ($url) {
      return [
        '#type'  => 'link',
        '#title' => $view_value,
        '#url'   => $url,
      ];
    }
    return $view_value;
  }

  /**
   * A helper to gradually migrate sub-modules content into theme_blazy().
   */
  public static function toContent(
    array &$data,
    $unset = FALSE,
    array $keys = ['content', 'box', 'slide'],
  ): array {
    $result = [];
    foreach ($keys as $key) {
      $value = $data[$key] ?? $data["#$key"] ?? [];
      if ($value) {
        $result = $value;
        break;
      }
      if ($unset) {
        unset($data[$key]);
      }
    }
    return $result;
  }

  /**
   * Returns the common content item.
   */
  public static function toHtml($content, $tag = 'div', $class = NULL): array {
    if ($class) {
      $attributes = is_array($class) ? $class : ['class' => [$class]];
      $output = [
        '#type' => 'html_tag',
        '#tag' => $tag,
        '#attributes' => $attributes,
      ];

      // Allows empty IFRAME, etc. tags.
      if (!is_null($content)) {
        $content = is_string($content) ? ['#markup' => $content] : $content;
        $output['content'] = $content;
      }

      return $output;
    }
    return $content ?: [];
  }

  /**
   * Returns one of the found configurable delimiter in the title.
   */
  private static function getDelimiter($title, $delimiter = ''): ?string {
    $delimiter = empty($delimiter) ? '|,:,/,- , —' : $delimiter;
    $limits = array_map('trim', explode(',', $delimiter));

    foreach ($limits as $limit) {
      if (stripos($title, $limit) === FALSE) {
        continue;
      }

      return $limit;
    }
    return NULL;
  }

}
