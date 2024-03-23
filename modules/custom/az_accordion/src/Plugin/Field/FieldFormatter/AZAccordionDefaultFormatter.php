<?php

declare(strict_types=1);

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Constructs a new AZAccordionDefaultFormatter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path service.
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   The path alias manager service.
   */
  public function __construct(
  array $configuration,
  $plugin_id,
  $plugin_definition,
  EntityTypeManagerInterface $entity_type_manager,
  RendererInterface $renderer,
  CurrentPathStack $current_path,
  ?AliasManagerInterface $path_alias_manager = NULL
  ) {
    $field_definition = $configuration['field_definition'];
    parent::__construct(
    $plugin_id,
    $plugin_definition,
    $field_definition,
    $configuration['settings'],
    $configuration['label'],
    $configuration['view_mode'],
    $configuration['third_party_settings']
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->currentPath = $current_path;
    $this->pathAliasManager = $path_alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
  ContainerInterface $container,
  array $configuration,
  $plugin_id,
  $plugin_definition
  ) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('entity_type.manager'),
    $container->get('renderer'),
    $container->get('path.current'),
    $container->has('path_alias.manager') ? $container->get('path_alias.manager') : NULL
    );
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
    foreach ($items as $delta => $item) {
      $title = $item->title ?? '';
      $accordion_classes = 'accordion';
      $entity_id = $item->getEntity()->id();
      $accordion_id = Html::getUniqueId('accordion-' . $entity_id . '-' . $delta . '-' . $title);
      $anchor_href = '#' . $accordion_id;
      $path = $this->currentPath->getPath();
      $path_with_anchor = $this->pathAliasManager->getAliasByPath($path) . $anchor_href;
      $click_to_copy_link = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $this->t('Click to copy link to this accordion item.'),
        '#attributes' => [
          'class' => ['btn', 'btn-primary', 'btn-sm'],
          'href' => $path_with_anchor,
        ],
      ];
      $click_to_copy = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'click_to_copy_link' => $click_to_copy_link,
        '#attached' => [
          'library' => [
            'az_core/click-to-copy',
            'az_accordion/formatter',
          ],
        ],
        '#attributes' => [
          'class' => [
            'js-click2copy',
            'position-relative',
          ],
        ],
      ];

      $element[$delta] = [
        '#theme' => 'az_accordion',
        '#title' => $title,
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
