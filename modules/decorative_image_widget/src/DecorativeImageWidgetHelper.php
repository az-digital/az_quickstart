<?php

namespace Drupal\decorative_image_widget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Helper class to modify image widgets. See decorative_image_widget.module.
 */
class DecorativeImageWidgetHelper {

  /**
   * Returns normalized Lightning Media-specific settings for the widget.
   *
   * @param \Drupal\image\Plugin\Field\FieldWidget\ImageWidget $widget
   *   The widget plugin.
   *
   * @return array
   *   The normalized settings.
   */
  protected static function getSettings(ImageWidget $widget) {
    $settings = $widget->getThirdPartySettings('decorative_image_widget') ?: [];

    $settings += [
      'use_decorative_checkbox' => FALSE,
    ];

    return $settings;
  }

  /**
   * Returns image widget form for our enhancements.
   *
   * @param \Drupal\image\Plugin\Field\FieldWidget\ImageWidget $widget
   *   The widget plugin.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   *
   * @return array
   *   The settings form elements.
   */
  public static function getSettingsForm(ImageWidget $widget, FieldDefinitionInterface $fieldDefinition) {
    $settings = static::getSettings($widget);

    if (!$fieldDefinition->getSetting('alt_field_required')) {
      return [
        'use_decorative_checkbox' => [
          '#type' => 'checkbox',
          '#title' => t('Force image to be marked decorative if no alt text provided'),
          '#description' => t('Adds a "Decorative" checkbox that must be checked if the user wants to have empty alt text.'),
          '#default_value' => $settings['use_decorative_checkbox'],
        ],
      ];
    }

    return [];
  }

  /**
   * Returns summarized settings for our image widget settings.
   *
   * @param \Drupal\image\Plugin\Field\FieldWidget\ImageWidget $widget
   *   The widget plugin.
   * @param array $summary
   *   (optional) An existing summary to augment.
   *
   * @return string[]
   *   The summarized settings.
   */
  public static function summarize(ImageWidget $widget, ?array &$summary = NULL) {
    $settings = static::getSettings($widget);

    if (is_null($summary)) {
      $summary = [];
    }

    if (!empty($settings['use_decorative_checkbox'])) {
      $summary[] = t('Decorative checkbox');
    }

    return $summary;
  }

  /**
   * Alters an image widget form element.
   *
   * @param array $element
   *   The widget form element.
   * @param \Drupal\image\Plugin\Field\FieldWidget\ImageWidget $widget
   *   The widget plugin.
   */
  public static function alter(array &$element, ImageWidget $widget) {
    // Store the widget settings where process() can see them.
    $element['#decorative_image_widget'] = static::getSettings($widget);

    $element['#process'][] = [static::class, 'process'];
  }

  /**
   * Process callback: perform extra processing of an image widget form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The whole form.
   *
   * @return array
   *   The processed form element.
   */
  public static function process(array $element, FormStateInterface $formState, array $form) {
    $settings = $element['#decorative_image_widget'];

    if (!empty($settings['use_decorative_checkbox']) && !$element['alt']['#required']) {
      // We need to determine if the decorative checkbox we're adding should
      // be checked by default or not. We only want it checked if the user has
      // already saved the image and is editing it, but when first seeing the
      // fields we want them to be explicit about clicking it.
      // Checking if the default value for the form element has as file ID and
      // and empty string set for alt seems to be reliable.
      $decorativeDefaultValue = !empty($element['#default_value']['target_id']) && isset($element['#default_value']['alt']) && empty($element['#default_value']['alt']);
      $element['decorative'] = [
        '#type' => 'checkbox',
        '#description' => t(
          'This image is <a href="@url" target="_blank">decorative</a> and should be hidden from screen readers.',
          ['@url' => 'https://www.w3.org/WAI/tutorials/images/decorative/']
        ),
        '#title' => t('Decorative'),
        '#weight' => $element['alt']['#weight'],
        '#access' => $element['alt']['#access'] ?? TRUE,
        '#default_value' => $decorativeDefaultValue,
        '#attributes' => [
          'class' => ['decorative-checkbox'],
        ],
      ];

      // Add a class name to the alt textfield so we can easily find it in JS.
      $element['alt']['#attributes']['class'][] = 'alt-textfield';

      // Add validation for the alt text that will require it have a value
      // unless the decorative checkbox is checked.
      $element['alt']['#element_validate'] = [
        [static::class, 'validateAltText'],
      ];

      $element['#attached']['library'][] = 'decorative_image_widget/decorative_image_widget';
    }

    return $element;
  }

  /**
   * Custom validation of image widget alt text field.
   *
   * @param array $element
   *   The image widget alt text element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public static function validateAltText(array $element, FormStateInterface $formState) {
    // Only perform validation if the function is triggered from other places
    // than the image process form. We don't want this validation to run when an
    // image was just uploaded and they haven't had an opportunity to provide
    // the alt text. ImageWidget does this too, see ::validateRequiredFields.
    $triggering_element = $formState->getTriggeringElement();
    if (!empty($triggering_element['#submit']) && in_array('file_managed_file_submit', $triggering_element['#submit'], TRUE)) {
      return;
    }

    $parents = $element['#parents'];
    array_pop($parents);

    // Back out if no image was submitted.
    $fid_form_element = array_merge($parents, ['fids']);
    if (empty($formState->getValue($fid_form_element))) {
      return;
    }

    $missing_alt_text = empty($formState->getValue($element['#parents']));
    $decorative_form_element = array_merge($parents, ['decorative']);
    $decorative_checked = (bool) $formState->getValue($decorative_form_element);
    if ($missing_alt_text && !$decorative_checked) {
      $formState->setErrorByName(implode('][', $element['#parents']), t('You must provide alternative text or indicate the image is decorative.'));
    }
  }

}
