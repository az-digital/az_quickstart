<?php

namespace Drupal\date_ap_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\date_ap_style\ApStyleDateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base formatter class for common elements of the AP date field formatter.
 */
abstract class ApSelectFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter.
   */
  protected ApStyleDateFormatter $apStyleDateFormatter;

  /**
   * Constructs a TimestampAgoFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param string[] $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param string[] $third_party_settings
   *   Any third party settings.
   * @param \Drupal\date_ap_style\ApStyleDateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct(string $plugin_id, mixed $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, string $label, string $view_mode, array $third_party_settings, ApStyleDateFormatter $date_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->apStyleDateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date_ap_style.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $config = \Drupal::config('date_ap_style.dateapstylesettings');
    $base_defaults = [
      'always_display_year' => $config->get('always_display_year'),
      'display_day' => $config->get('display_day'),
      'use_today' => $config->get('use_today'),
      'cap_today' => $config->get('cap_today'),
      'month_only' => $config->get('month_only'),
      'display_time' => $config->get('display_time'),
      'hide_date' => $config->get('hide_date'),
      'time_before_date' => $config->get('time_before_date'),
      'use_all_day' => $config->get('use_all_day'),
      'display_noon_and_midnight' => $config->get('display_noon_and_midnight'),
      'capitalize_noon_and_midnight' => $config->get('capitalize_noon_and_midnight'),
      'timezone' => $config->get('timezone'),
    ];
    return $base_defaults + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['always_display_year'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always display year'),
      '#description' => $this->t('When unchecked, the year will not be displayed if the date is in the same year as the current date.'),
      '#default_value' => $this->getSetting('always_display_year'),
    ];

    $elements['use_today'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use today'),
      '#default_value' => $this->getSetting('use_today'),
    ];

    $elements['cap_today'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize today'),
      '#default_value' => $this->getSetting('cap_today'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][use_today]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['display_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display day of the week'),
      '#default_value' => $this->getSetting('display_day'),
      '#description' => $this->t('Displays the day of the week (e.g., Monday) if the date falls within the same week as the current date.'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][month_only]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $elements['month_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show month only for date'),
      '#default_value' => $this->getSetting('month_only'),
      '#description' => $this->t('Shows only the month (e.g., Aug.) for the date, excluding the day and year.'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][always_display_year]"]' => ['checked' => FALSE],
          ':input[name$="[settings][use_today]"]' => ['checked' => FALSE],
          ':input[name$="[settings][display_day]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $elements['display_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time'),
      '#default_value' => $this->getSetting('display_time'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][month_only]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $elements['hide_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide date'),
      '#description' => $this->t('When checked, the date will not be displayed.'),
      '#default_value' => $this->getSetting('hide_date'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
          ':input[name$="[settings][always_display_year]"]' => ['checked' => FALSE],
          ':input[name$="[settings][month_only]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $elements['time_before_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time before date'),
      '#description' => $this->t('When checked, the time will be displayed before the date. Otherwise it will be displayed after the date.'),
      '#default_value' => $this->getSetting('time_before_date'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
          ':input[name$="[settings][hide_date]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $elements['display_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display noon and midnight'),
      '#default_value' => $this->getSetting('display_noon_and_midnight'),
      '#description' => $this->t('Converts 12:00 p.m. to "noon" and 12:00 a.m. to "midnight".'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['capitalize_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize noon and midnight'),
      '#default_value' => $this->getSetting('capitalize_noon_and_midnight'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
          ':input[name$="[settings][display_noon_and_midnight]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['use_all_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "All Day" instead of midnight'),
      '#default_value' => $this->getSetting('use_all_day'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][display_time]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => ['' => $this->t('- Default site/user time zone -')] + TimeZoneFormHelper::getOptionsList(),
      '#default_value' => $this->getSetting('timezone'),
      '#weight' => 999,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    if ($this->getSetting('month_only')) {
      $summary[] = $this->t('Show only the month for date');
    }
    else {
      if ($this->getSetting('always_display_year')) {
        $summary[] = $this->t('Always displaying year');
      }

      if ($this->getSetting('display_day')) {
        $summary[] = $this->t('Displaying day of the week');
      }

      if ($this->getSetting('use_today')) {
        $suffix = '';
        if ($this->getSetting('cap_today')) {
          $suffix = $this->t('(capitalized)');
        }
        $summary[] = $this->t('Displaying today @suffix', ['@suffix' => $suffix]);
      }
    }

    if ($this->getSetting('display_time')) {
      if ($this->getSetting('time_before_date')) {
        $suffix = $this->t('(before date)');
      }
      else {
        $suffix = $this->t('(after date)');
      }
      $summary[] = $this->t('Displaying time @suffix', ['@suffix' => $suffix]);
      if ($this->getSetting('hide_date')) {
        $summary[] = $this->t('Hiding the date');
      }
      if ($this->getSetting('display_noon_and_midnight')) {
        $suffix = '';
        if ($this->getSetting('capitalize_noon_and_midnight')) {
          $suffix = $this->t('(capitalized)');
        }
        $summary[] = $this->t('Displaying noon and midnight @suffix', ['@suffix' => $suffix]);
      }
    }

    if ($this->getSetting('use_all_day')) {
      $summary[] = $this->t('Show "All Day" instead of midnight');
    }

    if ($timezone = $this->getSetting('timezone')) {
      $summary[] = $this->t('Time zone: @timezone', ['@timezone' => $timezone]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    return $this->buildViewElements($items, $langcode);
  }

  /**
   * Common method for building the view elements.
   */
  protected function buildViewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    $opts = [
      'always_display_year',
      'display_day',
      'use_today',
      'cap_today',
      'display_time',
      'hide_date',
      'time_before_date',
      'use_all_day',
      'month_only',
      'display_noon_and_midnight',
      'capitalize_noon_and_midnight',
      'separator',
    ];

    $options = [];

    foreach ($opts as $opt) {
      if ($this->getSetting($opt)) {
        $options[$opt] = $this->getSetting($opt);
      }
    }
    $timezone = $this->getSetting('timezone') ?: NULL;
    $field_type = $items->getFieldDefinition()->getType();

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->processItem($item, $options, $timezone, $langcode, $field_type);
    }

    return $elements;
  }

  /**
   * Process element items for markup output.
   */
  abstract protected function processItem($item, $options, $timezone, $langcode, $field_type);

}
