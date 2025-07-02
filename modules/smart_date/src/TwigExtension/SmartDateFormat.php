<?php

namespace Drupal\smart_date\TwigExtension;

use Drupal\smart_date\SmartDatePluginTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Custom twig functions.
 */
class SmartDateFormat extends AbstractExtension {

  use SmartDatePluginTrait;

  /**
   * The configuration for the field whose values are being output.
   *
   * @var mixed
   */
  protected $fieldDefinition;

  /**
   * Declare the twig filter.
   *
   * @return array|TwigFilter[]
   *   The formatted date.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('smart_date_format',
        $this->formatInput(...),
      ),
    ];
  }

  /**
   * Function to apply a Smart Date Format to input.
   *
   * @param mixed $input
   *   The field values to process.
   * @param string $format
   *   The Smart Date format to use for output.
   * @param string|null $timezone
   *   (optional) Time zone identifier.
   *
   * @return array
   *   A render array of the output.
   */
  public function formatInput($input, $format = 'default', $timezone = NULL) {
    $output = '';
    // Conditional logic to handle different initial values.
    if (isset($input['#object']) && is_object($input['#object']) && method_exists($input['#object'], 'getFieldDefinition')) {
      // Handle a full field object, if available.
      // Retrieve the field definition for later use.
      $this->fieldDefinition = $input['#object']->getFieldDefinition($input['#field_name']);
      // Get the current language.
      // @phpstan-ignore-next-line
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      // Use the Smart Date Trait function for processing values.
      $output = $this->viewElements($input['#items'], $language, $format);
    }
    elseif (!empty($input['#value'])) {
      // Handle a specific field values.
      // @phpstan-ignore-next-line
      $entity_storage_manager = \Drupal::entityTypeManager()
        ->getStorage('smart_date_format');
      $format_obj = $entity_storage_manager->load($format);
      $settings = $format_obj->getOptions();
      $start_ts = $input['#value'];
      $end_ts = $input['#end_value'] ?? $input['#value'];
      // @todo pull timezone from render array and pass in.
      $output = $this->formatSmartDate($start_ts, $end_ts, $settings, $timezone);
    }
    else {
      // @todo any default fallback needed? Handle a single timestamp?
      // Return the input unchanged, since we don't understand it.
      return $input;
    }
    return $output;
  }

  /**
   * Mock the getSetting function of a normal formatter, returning false.
   *
   * @param string $key
   *   The setting to retrieve.
   *
   * @return mixed
   *   The setting from the field definition, if available, or FALSE.
   */
  private function getSetting($key): mixed { // phpcs:ignore
    // @todo Find a way to get the formatter settings.
    return $this->fieldDefinition->getSetting($key) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The machine name, as a string.
   */
  public function getName() {
    return 'smart_date_format.twig_extension';
  }

}
