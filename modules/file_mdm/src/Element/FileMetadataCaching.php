<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\FormElementBase;

/**
 * Implements a form element to enable capturing cache information for file_mdm.
 */
#[RenderElement('file_mdm_caching')]
class FileMetadataCaching extends FormElementBase {

  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [[$class, 'processCaching']],
      '#element_validate' => [[$class, 'validateCaching']],
    ];
  }

  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      $disallowed_paths = $input['disallowed_paths'];
      if (!empty($disallowed_paths)) {
        $disallowed_paths = preg_replace('/\r/', '', $disallowed_paths);
        $disallowed_paths = explode("\n", $disallowed_paths);
        while (empty($disallowed_paths[count($disallowed_paths) - 1])) {
          array_pop($disallowed_paths);
        }
        $input['disallowed_paths'] = $disallowed_paths ?: [];
      }
      else {
        $input['disallowed_paths'] = [];
      }
      return $input;
    }
    return NULL;
  }

  /**
   * Processes a 'file_mdm_caching' form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processCaching(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Cache metadata'),
      '#default_value' => $element['#default_value']['enabled'],
      '#description' => t("If selected, metadata retrieved from files will be cached for further access."),
    ];
    $options = [86400, 172800, 604800, 1209600, 3024000, 7862400];
    $options = array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($options, $options));
    $options = [-1 => t('Never')] + $options;
    $element['expiration'] = [
      '#type' => 'select',
      '#title' => t('Cache expires'),
      '#default_value' => $element['#default_value']['expiration'],
      '#options' => $options,
      '#description' => t("Specify the required lifetime of cached entries. Longer times may lead to increased cache sizes."),
      '#states' => [
        'visible' => [
          ':input[name="' . $element['#name'] . '[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['disallowed_paths'] = [
      '#type' => 'textarea',
      '#title' => t('Excluded paths'),
      '#rows' => 3,
      '#default_value' => implode("\n", $element['#default_value']['disallowed_paths']),
      '#description' => t("Only files prefixed by a valid URI scheme will be cached, like for example <kbd>public://</kbd>. Files in the <kbd>temporary://</kbd> scheme will never be cached. Specify here if there are any paths to be additionally <strong>excluded</strong> from caching, one per line. Use wildcard patterns when entering the path. For example, <kbd>public://styles/*</kbd>."),
      '#states' => [
        'visible' => [
          ':input[name="' . $element['#name'] . '[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateCaching(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    // Validate cache exclusion paths.
    foreach ($element['#value']['disallowed_paths'] as $path) {
      if (!\Drupal::service('stream_wrapper_manager')->isValidUri($path)) {
        $form_state->setError($element['disallowed_paths'], t("'@path' is an invalid URI path", ['@path' => $path]));
      }
    }
  }

}
