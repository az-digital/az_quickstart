<?php

namespace Drupal\az_accordion\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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

          // See if the parent behavior defines some accordion-specific
          // settings.
          if (!empty($parent_config['az_accordion_paragraph_behavior'])) {
            // TODO: implement az_accordion_paragraph_behavior handling.
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

    }

    return $element;
  }

}
