<?php

namespace Drupal\webform\Element;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\OptGroup;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Trait for entity reference elements.
 */
trait WebformEntityTrait {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#target_type'] = NULL;
    $info['#selection_handler'] = 'default';
    $info['#selection_settings'] = [];
    return $info;
  }

  /**
   * Set referenceable entities as options for an element.
   *
   * @param array $element
   *   An element.
   * @param array $settings
   *   An array of settings used to limit and randomize options.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specified entity.
   *
   * @see \Drupal\system\Controller\EntityAutocompleteController
   */
  public static function setOptions(array &$element, array $settings = []) {
    if (!empty($element['#options'])) {
      return;
    }

    // Make sure #target_type is not empty.
    if (empty($element['#target_type'])) {
      $element['#options'] = [];
      return;
    }

    $selection_settings = $element['#selection_settings'] ?? [];
    $selection_handler_options = [
      'target_type' => $element['#target_type'],
      'handler' => $element['#selection_handler'],
      // Set '_webform_settings' used to limit and randomize options.
      // @see webform_query_entity_reference_alter()
      '_webform_settings' => $settings,
    ] + $selection_settings;

    // Make sure settings has a limit.
    $settings += ['limit' => 0];

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $handler = $selection_manager->getInstance($selection_handler_options);
    $referenceable_entities = $handler->getReferenceableEntities(NULL, 'CONTAINS', $settings['limit']);

    // Flatten all bundle grouping since they are not applicable to
    // WebformEntity elements.
    $options = [];
    foreach ($referenceable_entities as $bundle_options) {
      $options += $bundle_options;
    }

    if ($element['#type'] === 'webform_entity_select') {
      // Strip tags from options since <option> element does
      // not support HTML tags.
      $options = WebformOptionsHelper::stripTagsOptions($options);
    }
    else {
      // Only select menu can support optgroups.
      $options = OptGroup::flattenOptions($options);
    }

    // Issue #2826451: TermSelection returning HTML characters in select list.
    $options = WebformOptionsHelper::decodeOptions($options);

    $element['#options'] = $options;

    static::setCacheTags($element, $element['#target_type'], $selection_settings['target_bundles'] ?? []);
  }

  /**
   * Set the corresponding entity cache tags on the element.
   *
   * @param array $element
   *   An element.
   * @param string $target_type
   *   The target type id.
   * @param array $target_bundles
   *   The target bundle ids.
   */
  protected static function setCacheTags(array &$element, $target_type, array $target_bundles = []) {
    $list_cache_tag = sprintf('%s_list', $target_type);

    if (empty($target_bundles)) {
      $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'] ?? [], [$list_cache_tag]);
      return;
    }

    $tags = Cache::buildTags($list_cache_tag, $target_bundles);
    $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'] ?? [], $tags);
  }

}
