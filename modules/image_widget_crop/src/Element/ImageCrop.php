<?php

namespace Drupal\image_widget_crop\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Entity\CropType;
use Drupal\file\FileInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Provides a form element for crop.
 *
 * @FormElement("image_crop")
 */
class ImageCrop extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#process' => [
        [static::class, 'processCrop'],
      ],
      '#file' => NULL,
      '#crop_preview_image_style' => 'crop_thumbnail',
      '#crop_type_list' => [],
      '#crop_types_required' => [],
      '#warn_multiple_usages' => FALSE,
      '#show_default_crop' => TRUE,
      '#show_crop_area' => FALSE,
      '#attached' => [
        'library' => [
          'image_widget_crop/cropper.integration',
        ],
      ],
      '#element_validate' => [[self::class, 'cropRequired']],
      '#tree' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $return = [];

    if ($input) {
      return $input;
    }

    return $return;
  }

  /**
   * Render API callback: Expands the image_crop element type.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   form actions container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processCrop(array &$element, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $element['#file'];
    if (!empty($file) && preg_match('/image/', $file->getMimeType())) {
      /** @var \Drupal\Core\Image\Image $image */
      $image = \Drupal::service('image.factory')->get($file->getFileUri());
      if (!$image->isValid()) {
        $element['message'] = [
          '#type' => 'container',
          '#markup' => t('The file "@file" is not valid on element @name.', [
            '@file' => $file->getFileUri(),
            '@name' => $element['#name'],
          ]),
          '#attributes' => [
            'class' => ['messages messages--error'],
          ],
        ];
        // Stop image_crop process and display error message.
        return $element;
      }

      $crop_type_list = $element['#crop_type_list'];
      // Display all crop types if none is selected.
      if (empty($crop_type_list)) {
        /** @var \Drupal\image_widget_crop\ImageWidgetCropInterface $iwc_manager */
        $iwc_manager = \Drupal::service('image_widget_crop.manager');
        $available_crop_types = $iwc_manager->getAvailableCropType(CropType::getCropTypeNames());
        $crop_type_list = array_keys($available_crop_types);
      }
      $element['crop_wrapper'] = [
        '#type' => 'details',
        '#title' => t('Crop image'),
        '#attributes' => [
          'class' => ['image-data__crop-wrapper'],
          'data-drupal-iwc' => 'wrapper',
        ],
        '#open' => $element['#show_crop_area'],
        '#weight' => 100,
      ];

      if ($element['#warn_multiple_usages']) {
        // Warn the user if the crop is used more than once.
        $usage_counter = self::countFileUsages($file);
        if ($usage_counter > 1) {
          $element['crop_reuse'] = [
            '#type' => 'container',
            '#markup' => t('This crop definition affects more usages of this image'),
            '#attributes' => [
              'class' => ['messages messages--warning'],
            ],
            '#weight' => -10,
          ];
        }
      }

      // Ensure that the ID of an element is unique.
      $list_id = \Drupal::service('uuid')->generate();

      $element['crop_wrapper'][$list_id] = [
        '#type' => 'vertical_tabs',
        '#parents' => [$list_id],
      ];

      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $crop_type_storage */
      $crop_type_storage = \Drupal::entityTypeManager()
        ->getStorage('crop_type');

      /** @var \Drupal\crop\Entity\CropType[] $crop_types */
      if ($crop_types = $crop_type_storage->loadMultiple($crop_type_list)) {
        foreach ($crop_types as $type => $crop_type) {
          $ratio = $crop_type->getAspectRatio() ?: 'NaN';
          $title = self::isRequiredType($element, $type) ? t('@label (required)', ['@label' => $crop_type->label()]) : $crop_type->label();
          $element['crop_wrapper'][$type] = [
            '#type' => 'details',
            '#title' => $title,
            '#group' => $list_id,
            '#attributes' => [
              'data-drupal-iwc' => 'type',
              'data-drupal-iwc-id' => $type,
              'data-drupal-iwc-ratio' => $ratio,
              'data-drupal-iwc-required' => self::isRequiredType($element, $type),
              'data-drupal-iwc-show-default-crop' => $element['#show_default_crop'] ? 'true' : 'false',
              'data-drupal-iwc-soft-limit' => Json::encode($crop_type->getSoftLimit()),
              'data-drupal-iwc-hard-limit' => Json::encode($crop_type->getHardLimit()),
              'data-drupal-iwc-original-width' => ($file instanceof FileEntity) ? $file->getMetadata('width') : getimagesize($file->getFileUri())[0],
              'data-drupal-iwc-original-height' => ($file instanceof FileEntity) ? $file->getMetadata('height') : getimagesize($file->getFileUri())[1],
            ],
          ];

          // Generation of html List with image & crop information.
          $element['crop_wrapper'][$type]['crop_container'] = [
            '#id' => $type,
            '#type' => 'container',
            '#attributes' => ['class' => ['crop-preview-wrapper', $list_id]],
            '#weight' => -10,
          ];

          $element['crop_wrapper'][$type]['crop_container']['image'] = [
            '#theme' => 'image_style',
            '#style_name' => $element['#crop_preview_image_style'],
            '#attributes' => [
              'class' => ['crop-preview-wrapper__preview-image'],
              'data-drupal-iwc' => 'image',
            ],
            '#uri' => $file->getFileUri(),
            '#weight' => -10,
          ];

          $element['crop_wrapper'][$type]['crop_container']['reset'] = [
            '#type' => 'button',
            '#value' => t('Reset crop'),
            '#attributes' => [
              'class' => ['crop-preview-wrapper__crop-reset'],
              'data-drupal-iwc' => 'reset',
            ],
            '#weight' => -10,
          ];

          // Generation of html List with image & crop information.
          $element['crop_wrapper'][$type]['crop_container']['values'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['crop-preview-wrapper__value']],
            '#weight' => -9,
          ];

          // Element to track whether cropping is applied or not.
          $element['crop_wrapper'][$type]['crop_container']['values']['crop_applied'] = [
            '#type' => 'hidden',
            '#attributes' => [
              'data-drupal-iwc-value' => 'applied',
              'data-drupal-iwc-id' => $type,
            ],
            '#default_value' => 0,
          ];
          $edit = FALSE;
          $properties = [];
          $form_state_values = $form_state->getValue($element['#parents']);
          // Check if form state has values.
          if (self::hasCropValues($element, $type, $form_state)) {
            $form_state_properties = $form_state_values['crop_wrapper'][$type]['crop_container']['values'];
            // If crop is applied by the form state we keep it that way.
            if ($form_state_properties['crop_applied'] == '1') {
              $element['crop_wrapper'][$type]['crop_container']['values']['crop_applied']['#default_value'] = 1;
              $edit = TRUE;
            }
            $properties = $form_state_properties;
          }

          /** @var \Drupal\crop\CropInterface $crop */
          $crop = Crop::findCrop($file->getFileUri(), $type);
          if ($crop) {
            $edit = TRUE;
            /** @var \Drupal\image_widget_crop\ImageWidgetCropInterface $iwc_manager */
            $iwc_manager = \Drupal::service('image_widget_crop.manager');
            $original_properties = $iwc_manager->getCropProperties($crop);

            // If form state values have the same values that were saved or if
            // form state has no values yet and there are saved values then we
            // use the saved values.
            $properties = $original_properties == $properties || empty($properties) ? $original_properties : $properties;
            $element['crop_wrapper'][$type]['crop_container']['values']['crop_applied']['#default_value'] = 1;
            // If the user edits an entity and while adding new images resets an
            // saved crop we keep it reset.
            if (isset($properties['crop_applied']) && $properties['crop_applied'] == '0') {
              $element['crop_wrapper'][$type]['crop_container']['values']['crop_applied']['#default_value'] = 0;
            }
          }
          self::getCropFormElement($element, 'crop_container', $properties, $edit, $type);
        }
        // Stock Original File Values.
        $element['file-uri'] = [
          '#type' => 'value',
          '#value' => $file->getFileUri(),
        ];

        $element['file-id'] = [
          '#type' => 'value',
          '#value' => $file->id(),
        ];
      }
    }
    return $element;
  }

  /**
   * Check if given  $crop_type is required for current instance or not.
   *
   * @param array $element
   *   All form elements.
   * @param string $crop_type_id
   *   The id of the current crop.
   *
   * @return string
   *   Return string "1" if given crop is required or "0".
   */
  public static function isRequiredType(array $element, $crop_type_id) {
    return (string) (static::hasCropRequired($element) && in_array($crop_type_id, $element['#crop_types_required']) ?: FALSE);
  }

  /**
   * Counts how many times a file has been used.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to check usages.
   *
   * @return int
   *   Returns how many times the file has been used.
   */
  public static function countFileUsages(FileInterface $file) {
    $counter = 0;
    $file_usage = \Drupal::service('file.usage')->listUsage($file);
    foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($file_usage)) as $usage) {
      $counter += (int) $usage;
    }
    return $counter;
  }

  /**
   * Inject crop elements into the form.
   *
   * @param array $element
   *   All form elements.
   * @param string $element_wrapper_name
   *   Name of element contains all crop properties.
   * @param array $original_properties
   *   All properties calculate for apply to.
   * @param bool $edit
   *   Context of this form.
   * @param string $crop_type_id
   *   The id of the current crop.
   *
   * @return array|null
   *   Populate all crop elements into the form.
   */
  public static function getCropFormElement(array &$element, $element_wrapper_name, array $original_properties, $edit, $crop_type_id) {
    $crop_properties = self::getCropFormProperties($original_properties, $edit);

    // Generate all coordinate elements into the form when process is active.
    foreach ($crop_properties as $property => $value) {
      $crop_element = &$element['crop_wrapper'][$crop_type_id][$element_wrapper_name]['values'][$property];
      $value_property = self::getCropFormPropertyValue($element, $crop_type_id, $edit, $value['value'], $property);
      $crop_element = [
        '#type' => 'hidden',
        '#attributes' => ['data-drupal-iwc-value' => $property],
        '#crop_type' => $crop_type_id,
        '#element_name' => $property,
        '#default_value' => $value_property,
      ];

      if ($property == 'height' || $property == 'width') {
        $crop_element['#element_validate'] = [
          [
            static::class,
            'validateHardLimit',
          ],
        ];
      }
    }
    return $element;
  }

  /**
   * Update crop elements of crop into the form.
   *
   * @param array $original_properties
   *   All properties calculate for apply to.
   * @param bool $edit
   *   Context of this form.
   *
   * @return array|null
   *   Populate all crop elements into the form.
   */
  public static function getCropFormProperties(array $original_properties, $edit) {
    $crop_elements = self::setCoordinatesElement();
    if (!empty($original_properties) && $edit) {
      foreach ($crop_elements as $properties => $value) {
        $crop_elements[$properties]['value'] = $original_properties[$properties];
      }
    }
    return $crop_elements;
  }

  /**
   * Get default value of property elements.
   *
   * @param array $element
   *   All form elements without crop properties.
   * @param string $crop_type
   *   The id of the current crop.
   * @param bool $edit
   *   Context of this form.
   * @param int|null $value
   *   The values calculated by ImageCrop::getCropFormProperties().
   * @param string $property
   *   Name of current property @see setCoordinatesElement().
   *
   * @return int|null
   *   Value of this element.
   */
  public static function getCropFormPropertyValue(array &$element, $crop_type, $edit, $value, $property) {
    // Standard case.
    if (!empty($edit) && isset($value)) {
      return $value;
    }
    // Populate value when ajax populates values after process.
    if (isset($element['#value']) && isset($element['crop_wrapper'])) {
      $ajax_element = &$element['#value']['crop_wrapper']['container'][$crop_type]['values'];
      return (isset($ajax_element[$property]) && !empty($ajax_element[$property])) ? $ajax_element[$property] : NULL;
    }
    return NULL;
  }

  /**
   * Form element validation handler for crop widget elements.
   *
   * @param array $element
   *   All form elements without crop properties.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see ImageCrop::getCropFormElement()
   */
  public static function validateHardLimit(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\crop\Entity\CropType $crop_type */
    $crop_type = \Drupal::entityTypeManager()
      ->getStorage('crop_type')
      ->load($element['#crop_type']);
    $parents = $element['#parents'];
    array_pop($parents);
    $crop_values = $form_state->getValue($parents);
    $hard_limit = $crop_type->getHardLimit();
    $action_button = $form_state->getTriggeringElement()['#value'];
    // We need to add this test in multilingual context because,
    // the "#value" element are a simple string in translate form,
    // and an TranslatableMarkup object in other cases.
    $operation = ($action_button instanceof TranslatableMarkup) ? $action_button->getUntranslatedString() : $action_button;

    if ((int) $crop_values['crop_applied'] == 0 || $operation == 'Remove') {
      return;
    }

    $element_name = $element['#element_name'];
    if ($hard_limit[$element_name] !== 0 && !empty($hard_limit[$element_name])) {
      if ($hard_limit[$element_name] > (int) $crop_values[$element_name]) {
        $form_state->setError($element, t('Crop @property is smaller than the allowed @hard_limitpx for @crop_name',
          [
            '@property' => $element_name,
            '@hard_limit' => $hard_limit[$element_name],
            '@crop_name' => $crop_type->label(),
          ]
        ));
      }
    }
  }

  /**
   * Evaluate if current element has required crops set from widget settings.
   *
   * @param array $element
   *   All form elements without crop properties.
   *
   * @return bool
   *   True if 'crop_types_required' settings is set or False.
   *
   * @see ImageCrop::cropRequired()
   */
  public static function hasCropRequired(array $element) {
    return isset($element['#crop_types_required']) || !empty($element['#crop_types_required']);
  }

  /**
   * Form element validation handler for crop widget elements.
   *
   * @param array $element
   *   All form elements without crop properties.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see ImageCrop::getCropFormElement()
   */
  public static function cropRequired(array $element, FormStateInterface $form_state) {
    if (!static::hasCropRequired($element)) {
      return;
    }

    $required_crops = [];
    foreach ($element['#crop_types_required'] as $crop_type_id) {
      $crop_applied = $form_state->getValue($element['#parents'])['crop_wrapper'][$crop_type_id]['crop_container']['values']['crop_applied'];
      $action_button = $form_state->getTriggeringElement()['#value'];
      $operation = ($action_button instanceof TranslatableMarkup) ? $action_button->getUntranslatedString() : $action_button;

      if (self::fileTriggered($form_state) && self::requiredApplicable($crop_applied, $operation)) {
        /** @var \Drupal\crop\Entity\CropType $crop_type */
        $crop_type = \Drupal::entityTypeManager()
          ->getStorage('crop_type')
          ->load($crop_type_id);
        $required_crops[] = $crop_type->label();
      }
    }

    if (!empty($required_crops)) {
      $form_state->setError($element, \Drupal::translation()
        ->formatPlural(count($required_crops), '@crop_required is required.', '@crops_required are required.', [
          "@crop_required" => current($required_crops),
          "@crops_required" => implode(', ', $required_crops),
        ]
        ));
    }
  }

  /**
   * Unsure we have triggered 'file_managed_file_submit' button.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   True if triggered button are 'file_managed_file_submit' or False.
   */
  public static function fileTriggered(FormStateInterface $form_state) {
    if (isset($form_state->getTriggeringElement()['#submit'])) {
      return !in_array('file_managed_file_submit', $form_state->getTriggeringElement()['#submit']);
    }

    return FALSE;
  }

  /**
   * Evaluate if crop is applicable on current CropType.
   *
   * @param int $crop_applied
   *   Crop applied parents.
   * @param string $operation
   *   Label current operation.
   *
   * @return bool
   *   True if current crop operation isn't "Reset crop" or False.
   */
  public static function requiredApplicable($crop_applied, $operation) {
    return ((int) $crop_applied === 0 && $operation !== 'Remove');
  }

  /**
   * Set All sizes properties of the crops.
   *
   * @return array|null
   *   Set all possible crop zone properties.
   */
  public static function setCoordinatesElement() {
    return [
      'x' => ['label' => t('X coordinate'), 'value' => NULL],
      'y' => ['label' => t('Y coordinate'), 'value' => NULL],
      'width' => ['label' => t('Width'), 'value' => NULL],
      'height' => ['label' => t('Height'), 'value' => NULL],
    ];
  }

  /**
   * Evaluate if element has crop values in form states.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   form actions container.
   * @param string $type
   *   Id of current crop type.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   True if crop element have values or False if not.
   */
  public static function hasCropValues(array $element, $type, FormStateInterface $form_state) {
    $form_state_values = $form_state->getValue($element['#parents']);
    return !empty($form_state_values) && isset($form_state_values['crop_wrapper'][$type]);
  }

}
