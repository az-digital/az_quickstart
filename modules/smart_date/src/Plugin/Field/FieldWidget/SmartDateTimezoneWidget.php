<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'smartdate_timezone' widget.
 *
 * @FieldWidget(
 *   id = "smartdate_timezone",
 *   label = @Translation("Smart Date | Inline range with timezone"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateTimezoneWidget extends SmartDateInlineWidget implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_tz' => '',
      'custom_tz' => '',
      'allowed_timezones' => [],
      'add_abbreviations' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Set default, based on field config.
    $default_label = $this->t('- default: @tz_label -', ['@tz_label' => $this->getSiteTimezone()]);
    $default_timezone = '';
    switch ($this->getSetting('default_tz')) {
      case 'user':
        $default_timezone = date_default_timezone_get();
        break;

      case 'custom':
        $default_timezone = $this->getSetting('custom_tz');
        break;
    }

    if ($this->getSetting('allowed_timezones')) {
      $allowed_timezone_values = $this->getSetting('allowed_timezones');
      $allowed_timezone_options = array_combine($allowed_timezone_values, $allowed_timezone_values);
      $timezones = $this->formatTimezoneOptions($allowed_timezone_options);
    }
    elseif ($this->getSetting('add_abbreviations')) {
      $timezones = $this->formatTimezoneOptions($this->getTimezones(FALSE));
    }
    else {
      $timezones = $this->getTimezones();
    }

    $element['timezone']['#type'] = 'select';
    $element['timezone']['#options'] = ['' => $default_label] + $timezones;

    $element['timezone']['#default_value'] = $items[$delta]->timezone ? $items[$delta]->timezone : $default_timezone;

    $element['timezone']['#attributes']['class'][] = 'field-timezone';
    $element['timezone']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['default_tz'] = [
      '#type' => 'select',
      '#title' => $this->t('Default timezone'),
      '#default_value' => $this->getSetting('default_tz'),
      '#options' => [
        '' => $this->t('Site default (ignores any user override)'),
        'user' => $this->t("User's timezone, defaulting to site (always saved)"),
        'custom' => $this->t('A custom timezone (always saved)'),
      ],
    ];

    $element['add_abbreviations'] = [
      '#type' => 'select',
      '#title' => $this->t('Add abbreviations'),
      '#description' => $this->t('Optionally add the time abbreviations.'),
      '#default_value' => $this->getSetting('add_abbreviations'),
      '#options' => [
        '' => $this->t('Never'),
        'before' => $this->t('Before the name'),
        'after' => $this->t('After the name'),
      ],
    ];

    $custom_tz = $this->getSetting('custom_tz') ? $this->getSetting('custom_tz') : $this->getSiteTimezone();

    $element['custom_tz'] = [
      '#type' => 'select',
      '#title' => $this->t('Custom timezone'),
      '#default_value' => $custom_tz,
      '#options' => $this->getTimezones(),
      '#states' => [
        // Show this select only if the 'default_tz' select is set to custom.
        'visible' => [
          'select[name$="[settings][default_tz]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $element['allowed_timezones']['#type'] = 'select';
    $element['allowed_timezones']['#multiple'] = TRUE;
    $element['allowed_timezones']['#options'] = $this->getTimezones();

    $element['allowed_timezones']['#default_value'] = $this->getSetting('allowed_timezones');

    $element['allowed_timezones']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    switch ($this->getSetting('default_tz')) {
      case '':
        $summary[] = $this->t("The site's timezone will be used unless overridden");
        break;

      case 'user':
        $summary[] = $this->t("The user's timezone will be used by default");
        break;

      case 'custom':
        $summary[] = $this->t('Custom default timezone: @custom_tz', ['@custom_tz' => $this->getSetting('custom_tz')]);
        break;
    }

    if ($allowed_tz = $this->getSetting('allowed_timezones')) {
      $summary[] = $this->t('Allowed timezones: @timezones', ['@timezones' => implode(', ', $allowed_tz)]);
    }

    return $summary;
  }

  /**
   * Helper function to retrieve available timezones.
   */
  public function getTimezones($grouped = TRUE) {
    if (!class_exists(DeprecationHelper::class)) {
      // @phpstan-ignore-next-line
      return system_time_zones(FALSE, $grouped);
    }
    return DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.1',
      static function () use ($grouped) {
        return $grouped ? TimeZoneFormHelper::getOptionsListByRegion() : TimeZoneFormHelper::getOptionsList();
      },
      // @phpstan-ignore-next-line
      static fn () => system_time_zones(FALSE, $grouped)
    );
  }

  /**
   * Helper function to format allowed timezone as a grouped list.
   */
  public function formatTimezoneOptions(array $zonelist, $grouped = TRUE) {
    $prepend = '';
    $append = '';
    $add_abbr = $this->getSetting('add_abbreviations');

    $zones = [];
    foreach ($zonelist as $value => $zone) {
      if (!is_string($zone)) {
        $zone = $zone->render();
      }
      // Because many time zones exist in PHP only for backward compatibility
      // reasons and should not be used, the list is filtered by a regular
      // expression.
      if (preg_match('!^((Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)/|UTC$)!', $zone)) {
        $zones[$value] = $this->t('@zone', [
          '@zone' => $this->t(str_replace('_', ' ', $zone)), // phpcs:ignore
        ]);
      }
    }

    $now = time();

    // Sort the translated time zones alphabetically.
    asort($zones);
    if ($grouped) {
      $grouped_zones = [];
      foreach ($zones as $key => $value) {
        $split = explode('/', $value);
        $city = array_pop($split);
        $region = array_shift($split);

        // If configured, add the timezone abbreviation.
        if ($add_abbr) {
          $tz = new \DateTimeZone(str_replace(' ', '_', $key));
          $transition = $tz->getTransitions($now, $now);
          $abbr = $transition[0]['abbr'];
          if ($add_abbr == 'before') {
            $prepend = $abbr . ' ';
          }
          elseif ($add_abbr == 'after') {
            $append = ' ' . $abbr;
          }
        }

        if (!empty($region)) {
          $label = empty($split) ? $city : $city . ' (' . implode('/', $split) . ')';
          $grouped_zones[$region][$key] = $prepend . $label . $append;
        }
        else {
          $grouped_zones[$key] = $prepend . $value . $append;
        }
      }
      foreach ($grouped_zones as $key => $value) {
        if (is_array($grouped_zones[$key])) {
          asort($grouped_zones[$key]);
        }
      }
      $zones = $grouped_zones;
    }
    return $zones;

  }

  /**
   * Helper function to return only the site's timezone.
   */
  public function getSiteTimezone() {
    // Ignore PHP strict notice if time zone has not yet been set in the php.ini
    // configuration.
    // @phpstan-ignore-next-line
    $config = \Drupal::config('system.date');
    $config_data_default_timezone = $config
      ->get('timezone.default');
    return !empty($config_data_default_timezone) ? $config_data_default_timezone : @date_default_timezone_get();
  }

}
