<?php

namespace Drupal\az_core\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_enterprise_text_export' formatter.
 *
 * @FieldFormatter(
 *   id = "az_enterprise_text_export",
 *   label = @Translation("Quickstart Enterprise Export"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class AZEnterpriseTextExportFormatter extends TextDefaultFormatter implements TrustedCallbackInterface {

  /**
   * The element_info service.
   *
   * @var \Drupal\Core\Render\ElementInfoManager
   */
  protected $elementInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->elementInfo = $container->get('plugin.manager.element_info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['transformForExport'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Add final step to transform markup for export, regardless of format.
    foreach ($elements as $delta => $element) {
      // Make sure to add the default pre_render steps.
      $info = $this->elementInfo->getInfo($element['#type']);
      $elements[$delta]['#pre_render'] = $info['#pre_render'] ?? [];
      // Add additional render step to run last.
      $elements[$delta]['#pre_render'][] = [
        AZEnterpriseTextExportFormatter::class,
        'transformForExport',
      ];
      // Add cache tag.
      $element[$delta]['#cache']['tags'][] = 'az-enterprise-text-export';
    }

    return $elements;
  }

  /**
   * Prepares a processed_text element for enterprise export.
   *
   * @param array $element
   *   A structured array with the #markup key containing prepared markup.
   *
   * @return array
   *   The passed-in element with relative URLs removed from '#markup'
   *
   * @ingroup sanitization
   */
  public static function transformForExport(array $element) {

    // Find the root URL.
    $base = \Drupal::request()->getSchemeAndHttpHost();

    // Transform relative links.
    $markup = (string) $element['#markup'];
    $element['#markup'] = Html::transformRootRelativeUrlsToAbsolute($markup, $base);

    return $element;
  }

}
