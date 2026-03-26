<?php

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
  public static function defaultSettings() {
    return ['foo' => 'bar'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    // @todo accordion style selection (based on custom config entities).
    $element['foo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foo'),
      '#default_value' => $settings['foo'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary[] = $this->t('Foo: @foo', ['@foo' => $settings['foo']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $entity = $items->getEntity();
    $accordion_container_id = HTML::getUniqueId('accordion-' . $entity->id());
    $faq_schema_enabled = FALSE;

    /** @var \Drupal\az_accordion\Plugin\Field\FieldType\AZAccordionItem $item */
    foreach ($items as $delta => $item) {
      // Format title.
      $title = $item->title ?? '';

      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
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

    }

    if (!empty($element)) {
      $element['#accordion_container_id'] = $accordion_container_id;
    }

    // Attach FAQ schema markup if enabled.
    if ($faq_schema_enabled && !empty($element)) {
      $this->attachFaqSchema($element, $items);
    }

    return $element;
  }

  /**
   * Attaches FAQPage JSON-LD structured data to the render array.
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

      // Strip HTML tags from the body for the schema markup.
      // Schema.org acceptedAnswer can contain HTML, but we use plain text
      // for maximum compatibility with search engines.
      $plain_body = strip_tags($body);

      $questions[] = [
        '@type' => 'Question',
        'name' => $title,
        'acceptedAnswer' => [
          '@type' => 'Answer',
          'text' => $plain_body,
        ],
      ];
    }

    if (!empty($questions)) {
      $faq_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $questions,
      ];

      $element['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => Json::encode($faq_schema),
        ],
        'faq_schema_' . $items->getEntity()->id(),
      ];
    }
  }

}
