<?php

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'az_accordion_default' formatter.
 *
 * @FieldFormatter(
 *   id = "az_accordion_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "az_accordion"
 *   }
 * )
 */
class AZaccordionDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('renderer')
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

    // TODO: accordion style selection (based on custom config entities).
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

      // Format title.
      $title = $item->title ?? '';

      $accordion_classes = 'accordion';
      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if (!empty($parent)) {
        if ($parent instanceof ParagraphInterface) {
          // Get the behavior settings for the parent.
          $parent_config = $parent->getAllBehaviorSettings();

          // See if the parent behavior defines some accordion-specific settings.
          if (!empty($parent_config['az_accordion_paragraph_behavior'])) {
            // TODO: implement az_accordion_paragraph_behavior handling
            //   $accordion_defaults = $parent_config['az_accordion_paragraph_behavior'];.
            // // Set accordion classes according to behavior settings.
            //   $column_classes = [];
            //   if (!empty($accordion_defaults['az_display_settings'])) {
            //     $column_classes[] = $accordion_defaults['az_display_settings']['accordion_width_xs'] ?? 'col-12';
            //     $column_classes[] = $accordion_defaults['az_display_settings']['accordion_width_sm'] ?? 'col-sm-6';
            //   }
            //   $column_classes[] = $accordion_defaults['accordion_width'] ?? 'col-md-4 col-lg-4';
            //   $accordion_classes = $accordion_defaults['accordion_style'] ?? 'accordion';.
          }

        }
      }

      // Handle class keys that contained multiple classes.
      $column_classes = implode(' ', $column_classes);
      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';

      $element[] = [
        '#theme' => 'az_accordion',
        '#title' => $title,
        '#body' => check_markup($item->body, $item->body_format),
        '#attributes' => ['class' => $accordion_classes],
        '#accordion_item_id' => Html::getUniqueId('az_accordion'),
        '#collapsed' => $item->collapsed ? 'collapse' : 'collapse show',
        '#aria_expanded' => !$item->collapsed ? 'true' : 'false',
        '#aria_controls' => Html::getUniqueId('az_accordion_aria_controls'),
      ];

      // $element['#items'][$delta] = new \stdClass();
      // $element['#items'][$delta]->_attributes = [
      //   'class' => $column_classes,
      // ];.
      // $element['#attributes']['class'][] = 'content';
      // $element['#attributes']['class'][] = 'h-100';
      // $element['#attributes']['class'][] = 'row';
      // $element['#attributes']['class'][] = 'd-flex';
      // $element['#attributes']['class'][] = 'flex-wrap';.
      // New code ==============================================================
      // $element['#items'][$delta] = new \stdClass();
      // $element['#items'][$delta]->_attributes = [
      //   'class' => $item->collapse ? 'collapse' : 'collapse show'
      // ];
      // $element['#items'][$delta]->accordion_item_id = Html::getUniqueId('az_acordion');.
    }

    return $element;
  }

}
