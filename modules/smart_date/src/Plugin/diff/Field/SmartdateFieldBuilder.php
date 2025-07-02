<?php

namespace Drupal\smart_date\Plugin\diff\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\diff\FieldDiffBuilderBase;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\SmartDatePluginTrait;

/**
 * Plugin to diff text fields.
 *
 * @FieldDiffBuilder(
 *   id = "smartdate_field_diff_builder",
 *   label = @Translation("Smart Date Field Diff"),
 *   field_types = {
 *     "smartdate"
 *   },
 * )
 */
class SmartdateFieldBuilder extends FieldDiffBuilderBase {

  use SmartDatePluginTrait;

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items): mixed {
    $result = [];

    $format = \Drupal::entityTypeManager()
      ->getStorage('smart_date_format')
      ->load($this->configuration['format']);

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      $values = $field_item->getValue();

      if (!isset($values['value'])) {
        continue;
      }

      if (!isset($values['end_value'])) {
        if (isset($values['duration']) && $values['duration'] > 0) {
          $values['end_value'] = $values['value'] + ($values['duration'] * 60);
        }
        else {
          $values['end_value'] = $values['value'];
        }
      }

      $date_value = static::formatSmartDate($values['value'], $values['end_value'], $format->getOptions(), NULL, 'string');
      $result[$field_key][] = $this->t('Date: @date_value', ['@date_value' => $date_value]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    unset($form['format_type']);

    // Change the description of the timezone_override element.
    if (isset($form['timezone_override'])) {
      $form['timezone_override']['#description'] = $this->t('The time zone selected here will be used unless overridden on an individual date.');
    }

    // Ask the user to choose a Smart Date Format.
    $formatOptions = [];

    $smartDateFormats = \Drupal::entityTypeManager()
      ->getStorage('smart_date_format')
      ->loadMultiple();

    foreach ($smartDateFormats as $type => $format) {
      if ($format instanceof SmartDateFormat) {
        $formatted = static::formatSmartDate(time(), time() + 3600, $format->getOptions(), NULL, 'string');
        $formatOptions[$type] = $format->label() . ' (' . $formatted . ')';
      }
    }

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Smart Date Format'),
      '#description' => $this->t('Choose which display configuration to use.'),
      '#default_value' => 'default',
      '#options' => $formatOptions,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['timezone_override'] = $form_state->getValue('timezone_override');
    $this->configuration['format'] = $form_state->getValue('format');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $default_configuration = [
      'timezone_override' => 0,
      'format' => 'default',
    ];
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
