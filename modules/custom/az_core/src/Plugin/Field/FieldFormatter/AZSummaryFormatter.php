<?php

namespace Drupal\az_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field formatter which produces minimal HTML for summaries.
 */
#[FieldFormatter(
  id: 'az_text_summary',
  label: new TranslatableMarkup('Quickstart Summary'),
  field_types: [
    'text',
    'text_long',
    'text_with_summary',
  ],
)]
class AZSummaryFormatter extends TextDefaultFormatter implements TrustedCallbackInterface {

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
    return ['transformStripTags'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Add final step to strip tags.
    foreach ($elements as $delta => $element) {
      // Make sure to add the default pre_render steps.
      $info = $this->elementInfo->getInfo($element['#type']);
      $elements[$delta]['#pre_render'] = $info['#pre_render'] ?? [];
      // Add additional render step to run last.
      $elements[$delta]['#pre_render'][] = [
        AZSummaryFormatter::class,
        'transformStripTags',
      ];
      // Add cache tag.
      $element[$delta]['#cache']['tags'][] = 'az-text-summary';
    }

    return $elements;
  }

  /**
   * Prepares a processed_text element by removing most markup.
   *
   * @param array $element
   *   A structured array with the #markup key containing prepared markup.
   *
   * @return array
   *   The passed-in element with non-spacing tags removed from '#markup'
   *
   * @ingroup sanitization
   */
  public static function transformStripTags(array $element) {

    $markup = (string) $element['#markup'];
    // Strip tags from output, except for minimal spacing html.
    // Paragraphs and br are allowed to remain.
    $element['#markup'] = strip_tags($markup, '<p><br>');

    return $element;
  }

}
