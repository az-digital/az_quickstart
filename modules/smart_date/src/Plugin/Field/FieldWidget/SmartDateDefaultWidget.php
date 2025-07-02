<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\smart_date\SmartDateDurationConfigTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'smartdate_default' widget.
 *
 * @FieldWidget(
 *   id = "smartdate_default",
 *   label = @Translation("Smart Date | Classic"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SmartDateDefaultWidget extends SmartDateWidgetBase implements ContainerFactoryPluginInterface {

  use SmartDateDurationConfigTrait;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'modal' => FALSE,
      'default_duration' => 60,
      'default_duration_increments' => "30\n60|1 hour\n90\n120|2 hours\ncustom",
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $field_def = $this->fieldDefinition;
    $field_type = $field_def->getType();
    if ($field_type == 'daterange') {
      // For core fields, add the option to configure allowed durations.
      $defaults = [];
      $default_duration = $this->getSetting('default_duration');
      if ($default_duration || $default_duration === 0 || $default_duration === '0') {
        $defaults['default_duration'] = $default_duration;
      }
      $default_duration_increments = $this->getSetting('default_duration_increments');
      if ($default_duration_increments) {
        $defaults['default_duration_increments'] = $default_duration_increments;
      }
      $this->addDurationConfig($element, $defaults);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->fieldDefinition->getType() == 'daterange' && $this->getSetting('default_duration')) {
      $summary[] = $this->t('The default duration is @def_dur minutes.', ['@def_dur' => $this->getSetting('default_duration')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateStorage = $date_storage;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!isset($element['value']) || (isset($element['#access']) && $element['#access'] === FALSE)) {
      return $element;
    }

    // Identify the type of date and time elements to use.
    $date_type = 'date';
    $time_type = 'time';
    $date_format = $this->dateStorage->load('html_date')->getPattern();
    $time_format = $this->dateStorage->load('html_time')->getPattern();

    $element['value'] += [
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    $element['end_value'] += [
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    return $element;
  }

}
