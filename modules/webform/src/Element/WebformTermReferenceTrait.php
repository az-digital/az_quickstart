<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;

/**
 * Trait for term reference elements.
 */
trait WebformTermReferenceTrait {

  /**
   * Set referenceable term entities as options for an element.
   *
   * @param array $element
   *   An element.
   */
  public static function setOptions(array &$element) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $vocabulary_id = $element['#vocabulary'];
    if (empty($vocabulary_id) || !\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $element['#options'] = [];
      return;
    }

    $vocabulary_list_cache_tag = "taxonomy_term_list:{$vocabulary_id}";
    // Only initialize the term options once by checking the cache tags.
    $cache_tags = NestedArray::getValue($element, ['#cache', 'tags']) ?? [];
    if (in_array($vocabulary_list_cache_tag, $cache_tags)) {
      return;
    }

    $element['#options'] = $element['#options'] ?? [];

    if (!empty($element['#breadcrumb'])) {
      $element['#options'] = static::getOptionsBreadcrumb($element, $language) + $element['#options'];
    }
    else {
      $element['#options'] = static::getOptionsTree($element, $language) + $element['#options'];
    }

    // Add vocabulary-specific cache tag for targeted cache invalidation.
    $element['#cache']['tags'][] = $vocabulary_list_cache_tag;
  }

  /**
   * Get options to term breadcrumb.
   *
   * @param array $element
   *   The term reference element.
   * @param string $language
   *   The language to be displayed.
   *
   * @return array
   *   An associative array of term options formatted as a breadcrumbs.
   */
  protected static function getOptionsBreadcrumb(array $element, $language) {
    $element += ['#breadcrumb_delimiter' => ' › '];

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $tree = static::loadTree($element['#vocabulary']);

    $options = [];
    $breadcrumb = [];
    foreach ($tree as $item) {
      // Set the item in the correct language for display.
      $item = $entity_repository->getTranslationFromContext($item);
      if (!$item->access('view')) {
        continue;
      }

      // Check depth.
      if (!empty($element['#depth']) && $item->depth >= $element['#depth']) {
        continue;
      }

      $breadcrumb[$item->depth] = $item->getName();
      $breadcrumb = array_slice($breadcrumb, 0, $item->depth + 1);
      $options[$item->id()] = implode($element['#breadcrumb_delimiter'], $breadcrumb);
    }
    return $options;
  }

  /**
   * Get options to term tree.
   *
   * @param array $element
   *   The term reference element.
   * @param string $language
   *   The language to be displayed.
   *
   * @return array
   *   An associative array of term options formatted as a tree.
   */
  protected static function getOptionsTree(array $element, $language) {
    $element += ['#tree_delimiter' => '-'];

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $tree = static::loadTree($element['#vocabulary']);

    $options = [];
    foreach ($tree as $item) {
      // Set the item in the correct language for display.
      $item = $entity_repository->getTranslationFromContext($item);
      if (!$item->access('view')) {
        continue;
      }

      // Check depth.
      if (!empty($element['#depth']) && $item->depth >= $element['#depth']) {
        continue;
      }

      $options[$item->id()] = str_repeat($element['#tree_delimiter'], $item->depth) . $item->getName();
    }
    return $options;
  }

  /**
   * Finds all terms in a given vocabulary ID.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   *
   * @return object[]|\Drupal\taxonomy\TermInterface[]
   *   An array of term objects that are the children of the vocabulary $vid.
   */
  protected static function loadTree($vid) {
    /** @var \Drupal\taxonomy\TermStorageInterface $taxonomy_storage */
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    return $taxonomy_storage->loadTree($vid, 0, NULL, TRUE);
  }

}
