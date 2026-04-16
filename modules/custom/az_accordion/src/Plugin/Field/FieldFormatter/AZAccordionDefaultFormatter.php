<?php

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\az_accordion\Plugin\Field\FieldType\AZAccordionItem;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_accordion_default' formatter.
 */
#[FieldFormatter(
  id: 'az_accordion_default',
  label: new TranslatableMarkup('Default'),
  field_types: [
    'az_accordion',
  ],
)]
class AZAccordionDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $entity = $items->getEntity();
    $accordion_container_id = HTML::getUniqueId('accordion-' . $entity->id());

    foreach ($items as $delta => $item) {
      assert($item instanceof AZAccordionItem);
      // Format title.
      $title = $item->title ?? '';

      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';

      // Handle class keys that contained multiple classes.
      $column_classes = implode(' ', $column_classes);
      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';
      $accordion_id = Html::getUniqueId('az_accordion');

      $element[$delta] = [
        '#theme' => 'az_accordion',
        '#title' => $title,
        // The ProcessedText element handles cache context & tag bubbling.
        // @see \Drupal\filter\Element\ProcessedText::preRenderText()
        '#body' => [
          '#type' => 'processed_text',
          '#text' => $item->body ?? '',
          '#format' => $item->body_format,
          '#langcode' => $item->getLangcode(),
        ],
        '#accordion_item_id' => $accordion_id,
        '#accordion_container_id' => $accordion_container_id,
        '#collapsed' => $item->collapsed ? '' : 'show',
        '#aria_expanded' => !$item->collapsed ? 'true' : 'false',
      ];
    }

    $show_expand_all = FALSE;
    if ($entity instanceof ParagraphInterface && method_exists($entity, 'getAllBehaviorSettings')) {
      $parent_config = $entity->getAllBehaviorSettings();
      if (!empty($parent_config['az_accordion_paragraph_behavior']) && !empty($parent_config['az_accordion_paragraph_behavior']['expand_all'])) {
        $show_expand_all = TRUE;
      }
    }

    if ($show_expand_all) {
      $any_collapsed = FALSE;
      foreach ($items as $item_check) {
        if (!empty($item_check->collapsed)) {
          $any_collapsed = TRUE;
          break;
        }
      }

      $button_text = $any_collapsed ? 'Expand all' : 'Collapse all';

      $toggle_id = 'accordion-toggle-' . $accordion_container_id;
      $element['#prefix'] = Markup::create('<div class="text-end"><button type="button" id="' . $toggle_id . '" class="btn btn-link btn-sm p-0" data-target="#' . $accordion_container_id . '">' . $button_text . '</button></div>');
    }

    if (!empty($element)) {
      $element['#accordion_container_id'] = $accordion_container_id;
    }

    return $element;
  }

}
