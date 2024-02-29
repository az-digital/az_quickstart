<?php

declare(strict_types=1);

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Provides a default formatter for az_accordion fields.
 *
 * @FieldFormatter(
 *   id = "az_accordion_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "az_accordion"
 *   }
 * )
 */
class AZAccordionDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

   /**
    * {@inheritdoc}
    */
  public static function create(
  ContainerInterface $container,
  array $configuration,
  $plugin_id,
  $plugin_definition
  ) {
    $instance = parent::create(
      $container,
      $configuration,
       $plugin_id,
       $plugin_definition,
     );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->renderer = $container->get('renderer');
    $instance->currentPath = $container->get('path.current');
    $instance->pathAliasManager = $container->get('path_alias.manager');
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
    /** @var \Drupal\az_accordion\Plugin\Field\FieldType\AZAccordionItem $item */
    foreach ($items as $delta => $item) {

      // Format title.
      $title = $item->title ?? '';

      $accordion_classes = 'accordion';
      $accordion_id = Html::getUniqueId('accordion-' . $item->getEntity()->id() . '-' . $title);
      $anchor_href = '#' . $accordion_id;
      $path = $this->currentPath->getPath();
      $path = $this->pathAliasManager->getAliasByPath($path);

      $path_with_anchor = $path . $anchor_href;
      // Create render array with click to copy link button for each item.
      $click_to_copy_link = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $this->t('Click to copy link to this accordion item.'),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-primary',
            'btn-sm',
          ],
          'href' => $path_with_anchor,
        ],
      ];
      $click_to_copy = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'class' => [
            'js-click2copy',
            'position-relative',
          ],
        ],
        'click_to_copy_link' => $click_to_copy_link,
        '#attached' => [
          'library' => [
            'az_core/click-to-copy',
            'az_accordion/formatter',
          ],

        ],

      ];

      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if ($parent instanceof ParagraphInterface) {
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();

        // See if the parent behavior defines some accordion-specific
        // settings.
        if (!empty($parent_config['az_accordion_paragraph_behavior'])) {
          // @todo implement az_accordion_paragraph_behavior handling.
        }
      }

      // Handle class keys that contained multiple classes.
      $column_classes = implode(' ', $column_classes);
      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';

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
          'click_to_copy' => $click_to_copy,
        ],
        '#attributes' => ['class' => $accordion_classes],
        '#accordion_item_id' => $accordion_id,
        '#collapsed' => $item->collapsed ? 'collapse' : 'collapse show',
        '#aria_expanded' => !$item->collapsed ? 'true' : 'false',
        '#aria_controls' => Html::getUniqueId('az_accordion_aria_controls'),
      ];

    }

    return $element;
  }

}
