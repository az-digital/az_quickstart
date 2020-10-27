<?php

namespace Drupal\az_card_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'az_card' field type.
 *
 * @FieldType(
 *   id = "az_card",
 *   category = @Translation("Arizona Digital"),
 *   label = @Translation("Card"),
 *   description = @Translation("Composite card field type"),
 *   category = @Translation("AZ Quickstart"),
 *   default_widget = "az_card_default",
 *   default_formatter = "az_card"
 * )
 */
class AZCard extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['media_id'] = DataDefinition::create('integer')
      ->setLabel(t('Card media'))
      ->setRequired(FALSE);

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Card title'))
      ->setRequired(FALSE);

    $properties['body'] = DataDefinition::create('string')
      ->setLabel(t('Card body'))
      ->setRequired(FALSE);

    $properties['body_format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Card body text format'))
      ->setRequired(FALSE);

    // $properties['body_processed'] = DataDefinition::create('string')
    //   ->setLabel(t('Processed card body'))
    //   ->setDescription(t('The card body with the text format applied.'))
    //   ->setComputed(TRUE)
    //   ->setClass('\Drupal\text\TextProcessed')
    //   ->setSetting('text source', 'body')
    //   ->setInternal(FALSE);

    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Card options'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'title' => [
          'description' => 'Card title',
          'type' => 'varchar',
          'length' => 255,
        ],
        'body' => [
          'description' => 'Card body',
          'type' => 'text',
          'size' => 'big',
        ],
        'format' => [
          'description' => 'Card body text format',
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'media_id' => [
          'description' => 'Card media',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'options' => [
          'description' => 'Serialized array of options for the card.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'media_id' => ['media_id'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    // Attributes for sample image.
    static $images = [];
    $min_resolution = '100x100';
    $max_resolution = '600x600';
    $extensions = ['png', 'gif', 'jpg', 'jpeg'];
    $extension = array_rand(array_combine($extensions, $extensions));
    // Generate a max of 5 different images.
    if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= 5) {
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      $tmp_file = $file_system->tempnam('temporary://', 'generateImage_');
      $destination = $tmp_file . '.' . $extension;
      try {
        $file_system->move($tmp_file, $destination);
      }
      catch (FileException $e) {
        // Ignore failed move.
      }
      if ($path = $random->image($file_system->realpath($destination), $min_resolution, $max_resolution)) {
        $image = File::create();
        $image->setFileUri($path);
        $image->setOwnerId(\Drupal::currentUser()->id());
        $image->setMimeType(\Drupal::service('file.mime_type.guesser')->guess($path));
        $image->setFileName($file_system->basename($path));
        $destination_dir = 'public://generated_sample';
        $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);
        $destination = $destination_dir . '/' . basename($path);
        $file = file_move($image, $destination);
        $images[$extension][$min_resolution][$max_resolution][$file->id()] = $file;
      }
      else {
        return [];
      }
    }
    else {
      // Select one of the images we've already generated for this field.
      $image_index = array_rand($images[$extension][$min_resolution][$max_resolution]);
      $file = $images[$extension][$min_resolution][$max_resolution][$image_index];
    }
    $image_media = Media::create([
      'name' => 'Image 1',
      'bundle' => 'az_image',
      'uid' => '1',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'status' => '1',
      'field_media_az_image' => [
        'target_id' => $file->id(),
        'alt' => t('Test Alt Text'),
        'title' => t('Test Title Text'),
      ],
    ]);
    $image_media->save();
    $values['media_id'] = $image_media->id();
    $values['title'] = $random->word(10);
    $values['body'] = $random->sentences(8);
    $values['body_format'] = 'basic_html';

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $title = $this->get('title')->getValue();
    $body = $this->get('body')->getValue();
    $media = $this->get('media_id')->getValue();
    return empty($title) && empty($body) && empty($media);
  }

}
