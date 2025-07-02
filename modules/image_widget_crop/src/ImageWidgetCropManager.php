<?php

namespace Drupal\image_widget_crop;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Entity\CropType;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\image\Entity\ImageStyle;

/**
 * ImageWidgetCropManager calculation class.
 */
class ImageWidgetCropManager implements ImageWidgetCropInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The crop storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $cropStorage;

  /**
   * The crop storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $cropTypeStorage;

  /**
   * The image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The File storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The ImageWidgetCrop general settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $imageWidgetCropSettings;

  /**
   * Constructs a ImageWidgetCropManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cropStorage = $this->entityTypeManager->getStorage('crop');
    $this->cropTypeStorage = $this->entityTypeManager->getStorage('crop_type');
    $this->imageStyleStorage = $this->entityTypeManager->getStorage('image_style');
    $this->fileStorage = $this->entityTypeManager->getStorage('file');
    $this->imageWidgetCropSettings = $config_factory->get('image_widget_crop.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function applyCrop(array $properties, $field_value, CropType $crop_type) {
    $crop_properties = $this->getCropOriginalDimension($field_value, $properties);
    if (!empty($crop_properties)) {
      $this->saveCrop(
        $crop_properties,
        $field_value,
        $crop_type,
        $this->imageWidgetCropSettings->get('settings.notify_apply')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateCrop(array $properties, $field_value, CropType $crop_type) {
    $crop_properties = $this->getCropOriginalDimension($field_value, $properties);
    if (!empty($crop_properties)) {
      $image_styles = $this->getImageStylesByCrop($crop_type->id());

      if (!empty($image_styles)) {
        $crops = $this->loadImageStyleByCrop($image_styles, $crop_type, $field_value['file-uri']);
      }

      if (empty($crops)) {
        $this->saveCrop($crop_properties, $field_value, $crop_type, $this->imageWidgetCropSettings->get('settings.notify_update'));
        return;
      }

      /** @var \Drupal\crop\Entity\Crop $crop */
      foreach ($crops as $crop) {
        if (!$this->cropHasChanged($crop_properties, array_merge($crop->position(), $crop->size()))) {
          return;
        }

        $this->updateCropProperties($crop, $crop_properties);
        $this->messenger()->addMessage($this->t('The crop "@cropType" was successfully updated for image "@filename".', ['@cropType' => $crop_type->label(), '@filename' => $this->fileStorage->load($field_value['file-id'])->getFilename()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveCrop(array $crop_properties, $field_value, CropType $crop_type, $notify = TRUE) {
    $values = [
      'type' => $crop_type->id(),
      'entity_id' => $field_value['file-id'],
      'entity_type' => 'file',
      'uri' => $field_value['file-uri'],
      'x' => $crop_properties['x'],
      'y' => $crop_properties['y'],
      'width' => $crop_properties['width'],
      'height' => $crop_properties['height'],
    ];

    /** @var \Drupal\crop\CropInterface $crop */
    $crop = $this->cropStorage->create($values);
    $crop->save();

    if ($notify) {
      $this->messenger()->addMessage($this->t('The crop "@cropType" was successfully added for image "@filename".', ['@cropType' => $crop_type->label(), '@filename' => $this->fileStorage->load($field_value['file-id'])->getFilename()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCrop($file_uri, CropType $crop_type, $file_id) {
    $image_styles = $this->getImageStylesByCrop($crop_type->id());
    $crop = $this->cropStorage->loadByProperties([
      'type' => $crop_type->id(),
      'uri' => $file_uri,
    ]);
    $this->cropStorage->delete($crop);
    $this->imageStylesOperations($image_styles, $file_uri);
    $this->messenger()->addMessage($this->t('The crop "@cropType" was successfully deleted for image "@filename".', [
      '@cropType' => $crop_type->label(),
      '@filename' => $this->fileStorage->load($file_id)->getFilename(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getAxisCoordinates(array $axis, array $crop_selection) {
    return [
      'x' => (int) round($axis['x'] + ($crop_selection['width'] / 2)),
      'y' => (int) round($axis['y'] + ($crop_selection['height'] / 2)),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCropOriginalDimension(array $field_values, array $properties) {
    $crop_coordinates = [];

    /** @var \Drupal\Core\Image\Image $image */
    $image = \Drupal::service('image.factory')->get($field_values['file-uri']);
    if (!$image->isValid()) {
      $this->messenger()->addError($this->t('The file "@file" is not valid, your crop is not applied.', [
        '@file' => $field_values['file-uri'],
      ]));
      return $crop_coordinates;
    }

    // Get Center coordinate of crop zone on original image.
    $axis_coordinate = $this->getAxisCoordinates(
      ['x' => $properties['x'], 'y' => $properties['y']],
      ['width' => $properties['width'], 'height' => $properties['height']]
    );

    // Calculate coordinates (position & sizes) of crop zone on original image.
    $crop_coordinates['width'] = $properties['width'];
    $crop_coordinates['height'] = $properties['height'];
    $crop_coordinates['x'] = $axis_coordinate['x'];
    $crop_coordinates['y'] = $axis_coordinate['y'];

    return $crop_coordinates;
  }

  /**
   * {@inheritdoc}
   */
  public function getEffectData(ImageStyle $image_style, $data_type) {
    $data = NULL;
    /* @var  \Drupal\image\ImageEffectInterface $effect */
    foreach ($image_style->getEffects() as $uuid => $effect) {
      $data_effect = $image_style->getEffect($uuid)->getConfiguration()['data'];
      if (isset($data_effect[$data_type])) {
        $data = $data_effect[$data_type];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStylesByCrop($crop_type_name) {
    $styles = [];
    $image_styles = $this->imageStyleStorage->loadMultiple();

    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      $image_style_data = $this->getEffectData($image_style, 'crop_type');
      if (!empty($image_style_data) && ($image_style_data == $crop_type_name)) {
        $styles[] = $image_style;
      }
    }

    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function imageStylesOperations(array $image_styles, $file_uri, $create_derivative = FALSE) {
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      if ($create_derivative) {
        // Generate the image derivative uri.
        $destination_uri = $image_style->buildUri($file_uri);

        // Create a derivative of the original image with a good uri.
        $image_style->createDerivative($file_uri, $destination_uri);
      }
      // Flush the cache of this ImageStyle.
      $image_style->flush($file_uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateCropProperties(Crop $crop, array $crop_properties) {
    // Parse all properties if this crop have changed.
    foreach ($crop_properties as $crop_coordinate => $value) {
      // Edit the crop properties if he have changed.
      $crop->set($crop_coordinate, $value, TRUE);
    }

    $crop->save();
  }

  /**
   * {@inheritdoc}
   */
  public function loadImageStyleByCrop(array $image_styles, CropType $crop_type, $file_uri) {
    $crops = [];
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    foreach ($image_styles as $image_style) {
      /** @var \Drupal\crop\Entity\Crop $crop */
      $crop = Crop::findCrop($file_uri, $crop_type->id());
      if (!empty($crop)) {
        $crops[$image_style->id()] = $crop;
      }
    }

    return $crops;
  }

  /**
   * {@inheritdoc}
   */
  public function cropHasChanged(array $crop_properties, array $old_crop) {
    return !empty(array_diff_assoc($crop_properties, $old_crop));
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableCropType(array $crop_list) {
    $available_crop = [];
    foreach ($crop_list as $crop_machine_name => $crop_label) {
      $image_styles = $this->getImageStylesByCrop($crop_machine_name);
      if (!empty($image_styles)) {
        $available_crop[$crop_machine_name] = $crop_label;
      }
    }

    return $available_crop;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCropProperties(Crop $crop) {
    $anchor = $crop->anchor();
    $size = $crop->size();
    return [
      'x' => $anchor['x'],
      'y' => $anchor['y'],
      'height' => $size['height'],
      'width' => $size['width'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildCropToEntity(EntityInterface $entity) {
    if (isset($entity) && $entity instanceof FieldableEntityInterface) {
      // Loop all fields of the saved entity.
      foreach ($entity->getFields() as $entity_fields) {
        // If current field is FileField and use imageWidgetCrop.
        if ($entity_fields instanceof FileFieldItemList) {
          /* First loop to get each elements independently in the field values.
          Required if the image field cardinality > 1. */
          foreach ($entity_fields->getValue() as $crop_elements) {
            foreach ($crop_elements as $crop_element) {
              if (is_array($crop_element) && isset($crop_element['crop_wrapper'])) {

                // If file-id key is not available, set it same as parent elements target_id
                if (empty($crop_element['file-id']) && !empty($crop_elements['target_id'])) {
                  $crop_element['file-id'] = $crop_elements['target_id'];
                }

                // Reload image since its URI could have been changed,
                // by other modules.
                /** @var \Drupal\file_entity\Entity\FileEntity $file */
                $file = $this->fileStorage->load($crop_element['file-id']);
                $crop_element['file-uri'] = $file->getFileUri();
                // Parse all value of a crop_wrapper element and get properties
                // associate with her CropType.
                foreach ($crop_element['crop_wrapper'] as $crop_type_name => $properties) {
                  $properties = $properties['crop_container']['values'];
                  /** @var \Drupal\crop\Entity\CropType $crop_type */
                  $crop_type = $this->cropTypeStorage->load($crop_type_name);

                  // If the crop type needed is disabled or delete.
                  if (empty($crop_type) && $crop_type instanceof CropType) {
                    $this->messenger()->addError($this->t("The CropType ('@cropType') is not active or not defined. Please verify configuration of image style or ImageWidgetCrop formatter configuration", ['@cropType' => $crop_type->id()]));
                    return;
                  }

                  // If this crop is available to create an crop entity.
                  if ($entity->isNew()) {
                    if ($properties['crop_applied'] == '1' && isset($properties) && (!empty($properties['width']) && !empty($properties['height']))) {
                      $this->applyCrop($properties, $crop_element, $crop_type);
                    }
                  }
                  else {
                    // Get all imagesStyle used this crop_type.
                    $image_styles = $this->getImageStylesByCrop($crop_type_name);
                    $crops = $this->loadImageStyleByCrop($image_styles, $crop_type, $crop_element['file-uri']);
                    // If the entity already exist & is not deleted by user
                    // update $crop_type_name crop entity.
                    if ($properties['crop_applied'] == '0' && !empty($crops)) {
                      $this->deleteCrop($crop_element['file-uri'], $crop_type, $crop_element['file-id']);
                    }
                    elseif (isset($properties) && (!empty($properties['width']) && !empty($properties['height']))) {
                      $this->updateCrop($properties, $crop_element, $crop_type);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildCropToForm(FormStateInterface $form_state) {
    if ($entity = $form_state->getFormObject()->getEntity()) {
      $form_state_values = $form_state->getValues();
      if (is_array($form_state_values['image_crop']) && isset($form_state_values['image_crop']['crop_wrapper'])) {
        // Parse all values and get properties associate with the crop type.
        foreach ($form_state_values['image_crop']['crop_wrapper'] as $crop_type_name => $properties) {
          $properties = $properties['crop_container']['values'];
          /** @var \Drupal\crop\Entity\CropType $crop_type */
          $crop_type = $this->cropTypeStorage->load($crop_type_name);

          // If the crop type needed is disabled or delete.
          if (empty($crop_type) && $crop_type instanceof CropType) {
            $this->messenger()->addError($this->t("The CropType ('@cropType') is not active or not defined. Please verify configuration of image style or ImageWidgetCrop formatter configuration", ['@cropType' => $crop_type->id()]));
            return;
          }

          if (is_array($properties) && isset($properties)) {
            $crop_exists = Crop::cropExists($entity->getFileUri(), $crop_type_name);
            if (!$crop_exists) {
              if ($properties['crop_applied'] == '1' && isset($properties) && (!empty($properties['width']) && !empty($properties['height']))) {
                $this->applyCrop($properties, $form_state_values['image_crop'], $crop_type);
              }
            }
            else {
              // Get all imagesStyle used this crop_type.
              $image_styles = $this->getImageStylesByCrop($crop_type_name);
              $crops = $this->loadImageStyleByCrop($image_styles, $crop_type, $entity->getFileUri());
              // If the entity already exist & is not deleted by user update
              // $crop_type_name crop entity.
              if ($properties['crop_applied'] == '0' && !empty($crops)) {
                $this->deleteCrop($entity->getFileUri(), $crop_type, $entity->id());
              }
              elseif (isset($properties) && (!empty($properties['width']) && !empty($properties['height']))) {
                $this->updateCrop($properties, [
                  'file-uri' => $entity->getFileUri(),
                  'file-id' => $entity->id(),
                ], $crop_type);
              }
            }
          }
        }
      }
    }
    else {
      $this->messenger()->addError($this->t('No File element found.'));
      return;
    }
  }

}
