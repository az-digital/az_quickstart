<?php

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
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
    $faq_schema_enabled = FALSE;

    $accordion_items = [];
    foreach ($items as $delta => $item) {
      assert($item instanceof AZAccordionItem);
      // Format title.
      $title = $item->title ?? '';

      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();
      if ($parent instanceof ParagraphInterface) {
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();

        // Check if FAQ schema markup is enabled.
        if (!empty($parent_config['az_accordion_paragraph_behavior']['faq_schema'])) {
          $faq_schema_enabled = TRUE;
        }
      }

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

      // Collect accordion item data for drupalSettings.
      $accordion_items[] = [
        'weight' => $delta,
        'parent_id' => $items->getEntity()->id(),
        'field_name' => $items->getName(),
        'title' => $title,
        'body' => $item->body ?? '',
        'body_format' => $item->body_format,
        'langcode' => $item->getLangcode(),
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

    // Attach consolidated drupalSettings output, including FAQ flag.
    if (!empty($accordion_items)) {
      $settings_entry = [
        'entity_id' => $items->getEntity()->id(),
        'field_name' => $items->getName(),
        'faq' => $faq_schema_enabled,
        'items' => $accordion_items,
      ];
      $unique_id = $items->getEntity()->id() . '__' . $items->getName();
      $element['#attached']['drupalSettings']['az_accordion'][$unique_id] = $settings_entry;
    }


    return $element;
  }

  /**
   * Attaches FAQ question data to the render array for later aggregation.
   *
   * Each FAQ-enabled accordion attaches its questions as a separate html_head
   * entry with a unique key (faq_questions_ENTITY_ID). The
   * AZFaqAggregatorSubscriber merges all such entries into a single
   * FAQPage JSON-LD block before the response is sent. This approach is
   * compatible with Drupal's render caching because #attached metadata
   * survives caching.
   *
   * @param array &$element
   *   The render array to attach the schema to.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The accordion field items.
   */
  protected function attachFaqSchema(array &$element, FieldItemListInterface $items) {
    $questions = [];

    foreach ($items as $item) {
      $title = $item->title ?? '';
      $body = $item->body ?? '';

      if (empty($title) || empty($body)) {
        continue;
      }

      // Keep only the HTML tags that Google displays in FAQ rich results.
      // @see https://developers.google.com/search/docs/appearance/structured-data/faqpage
      $allowed_tags = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'br', 'ol', 'ul', 'li', 'a', 'p', 'div',
        'b', 'strong', 'i', 'em',
      ];
      $clean_body = Xss::filter($body, $allowed_tags);

      $questions[] = [
        '@type' => 'Question',
        'name' => strip_tags($title),
        'acceptedAnswer' => [
          '@type' => 'Answer',
          'text' => $clean_body,
        ],
      ];
    }
    if (!empty($questions)) {
      $data = ['questions' => $questions];
      $element['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($data),
        ],
        'faq_questions_' . $items->getEntity()->id(),
      ];
    }
  }

}
