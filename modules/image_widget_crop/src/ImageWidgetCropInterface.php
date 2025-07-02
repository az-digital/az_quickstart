<?php

namespace Drupal\image_widget_crop;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Entity\CropType;
use Drupal\image\Entity\ImageStyle;

/**
 * Defines the interface for ImageWidgetCropManager calculation class service.
 */
interface ImageWidgetCropInterface {

  /**
   * Create new crop entity with user properties.
   *
   * @param array $properties
   *   All properties returned by the crop plugin (js),
   *   and the size of thumbnail image.
   * @param array|mixed $field_value
   *   An array of values for the contained properties of image_crop widget.
   * @param \Drupal\crop\Entity\CropType $crop_type
   *   The entity CropType.
   */
  public function applyCrop(array $properties, $field_value, CropType $crop_type);

  /**
   * Update old crop with new properties choose in UI.
   *
   * @param array $properties
   *   All properties returned by the crop plugin (js),
   *   and the size of thumbnail image.
   * @param array|mixed $field_value
   *   An array of values contain properties of image_crop widget.
   * @param \Drupal\crop\Entity\CropType $crop_type
   *   The entity CropType.
   */
  public function updateCrop(array $properties, $field_value, CropType $crop_type);

  /**
   * Save the crop when this crop not exist.
   *
   * @param double[] $crop_properties
   *   The properties of the crop applied to the original image (dimensions).
   * @param array|mixed $field_value
   *   An array of values for the contained properties of image_crop widget.
   * @param \Drupal\crop\Entity\CropType $crop_type
   *   The entity CropType.
   * @param bool $notify
   *   Show notification after actions (default TRUE).
   */
  public function saveCrop(
    array $crop_properties,
    $field_value,
    CropType $crop_type,
    $notify = TRUE
  );

  /**
   * Delete the crop when user delete it.
   *
   * @param string $file_uri
   *   Uri of image uploaded by user.
   * @param \Drupal\crop\Entity\CropType $crop_type
   *   The CropType object.
   * @param int $file_id
   *   Id of image uploaded by user.
   */
  public function deleteCrop($file_uri, CropType $crop_type, $file_id);

  /**
   * Get center of crop selection.
   *
   * @param int[] $axis
   *   Coordinates of x-axis & y-axis.
   * @param array $crop_selection
   *   Coordinates of crop selection (width & height).
   *
   * @return arraystringinteger
   *   Coordinates (x-axis & y-axis) of crop selection zone.
   */
  public function getAxisCoordinates(array $axis, array $crop_selection);

  /**
   * Get the size and position of the crop.
   *
   * @param array $field_values
   *   The original values of image.
   * @param array $properties
   *   The original height of image.
   *
   * @return array
   *   The data dimensions (width & height) into this ImageStyle or,
   *   empty array is the image isn't a valid file.
   */
  public function getCropOriginalDimension(array $field_values, array $properties);

  /**
   * Get one effect instead of ImageStyle.
   *
   * @param \Drupal\image\Entity\ImageStyle $image_style
   *   The ImageStyle to get data.
   * @param string $data_type
   *   The type of data needed in current ImageStyle.
   *
   * @return mixed|null
   *   The effect data in current ImageStyle.
   */
  public function getEffectData(ImageStyle $image_style, $data_type);

  /**
   * Get the imageStyle using this crop_type.
   *
   * @param string $crop_type_name
   *   The id of the current crop_type entity.
   *
   * @return array
   *   All imageStyle used by this crop_type.
   */
  public function getImageStylesByCrop($crop_type_name);

  /**
   * Apply different operation on ImageStyles.
   *
   * @param array $image_styles
   *   All ImageStyles used by this cropType.
   * @param string $file_uri
   *   Uri of image uploaded by user.
   * @param bool $create_derivative
   *   Boolean to create an derivative of the image uploaded.
   */
  public function imageStylesOperations(array $image_styles, $file_uri, $create_derivative = FALSE);

  /**
   * Update existent crop entity properties.
   *
   * @param \Drupal\crop\Entity\Crop $crop
   *   The crop object loaded.
   * @param array $crop_properties
   *   The machine name of ImageStyle.
   */
  public function updateCropProperties(Crop $crop, array $crop_properties);

  /**
   * Load all crop using the ImageStyles.
   *
   * @param array $image_styles
   *   All ImageStyle for this current CROP.
   * @param \Drupal\crop\Entity\CropType $crop_type
   *   The entity CropType.
   * @param string $file_uri
   *   Uri of uploaded file.
   *
   * @return array
   *   All crop used this ImageStyle.
   */
  public function loadImageStyleByCrop(array $image_styles, CropType $crop_type, $file_uri);

  /**
   * Compare crop zone properties when user saved one crop.
   *
   * @param array $crop_properties
   *   The crop properties after saved the form.
   * @param array $old_crop
   *   The crop properties save in this crop entity,
   *   Only if this crop already exist.
   *
   * @return bool
   *   Return true if properties is not identical.
   */
  public function cropHasChanged(array $crop_properties, array $old_crop);

  /**
   * Verify if the crop is used by a ImageStyle.
   *
   * @param string[] $crop_list
   *   The list of existent Crop Type.
   *
   * @return arrayinteger
   *   The list of Crop Type filtered.
   */
  public function getAvailableCropType(array $crop_list);

  /**
   * Get All sizes properties of the crops for an file.
   *
   * @param \Drupal\crop\Entity\Crop $crop
   *   All crops attached to this file based on URI.
   *
   * @return arrayarray
   *   Get all crop zone properties (x, y, height, width),
   */
  public static function getCropProperties(Crop $crop);

  /**
   * Fetch all fields FileField and use "image_crop" element on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function buildCropToEntity(EntityInterface $entity);

  /**
   * Fetch all form elements using image_crop element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function buildCropToForm(FormStateInterface $form_state);

}
