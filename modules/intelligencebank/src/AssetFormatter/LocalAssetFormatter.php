<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormState;
use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\node\Entity\Node;

/**
 * Class LocalAssetFormatter.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class LocalAssetFormatter extends AssetFormatterBase {

  /**
   * File ID.
   *
   * @var int
   *
   * @see \Drupal\file\FileInterface::load()
   */
  private $fileId;

  /**
   * Field formatter instance.
   *
   * @var \Drupal\Core\Field\FormatterInterface|null
   */
  private $fieldFormatter;

  /**
   * Field definition, suitable for field formatter.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  private $fieldDefinition;

  /**
   * LocalAssetFormatter constructor.
   *
   * @param string $type
   *   The field type plugin id.
   * @param int $file_id
   *   The File ID.
   * @param array $display_settings
   *   List of display settings used as formatter options.
   */
  public function __construct($type, $file_id, array $display_settings) {
    parent::__construct($type, $display_settings);
    $this->fileId = $file_id;

    // For document/file we have an extra field formatter setting: use_description_as_link_text,
    // it enables #description property of the field item ($item->description)
    if ($type === 'file' && !empty($display_settings['use_description_as_link_text'])) {
      $this->settings['description'] = $display_settings['alt'];
    }

    $map = AssetFormatterManager::matchFieldTypeByAssetType($type);
    $this->fieldDefinition = BaseFieldDefinition::create($map['type'])
      ->setName('ib_dam_mock_local')
      ->setComputed(FALSE)
      ->setSetting('file_extensions', 'png pdf mp3 mp4');

    $this->fieldFormatter = \Drupal::service('plugin.manager.field.formatter')->getInstance([
      'settings' => $display_settings,
      'third_party_settings' => [],
      'label' => '',
      'view_mode' => '_custom',
      'field_definition' => $this->fieldDefinition,
      'configuration' => [
        'label' => 'hidden',
        'type' => $map['formatter'],
      ],
    ]);

    $this->fieldFormatter->setSettings($this->settings);
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    $entity = Node::create([
      'type' => 'mock',
      'title' => 'mock',
    ]);

    $value = ['target_id' => $this->fileId];
    if (!empty($this->settings)) {
      $value = array_merge($value, $this->settings);
    }

    $items = \Drupal::typedDataManager()->create(
      $this->fieldDefinition,
      $value,
      $this->fieldDefinition->getName(),
      $entity->getTypedData()
    );

    $this->fieldFormatter->prepareView([$entity->id() => $items]);

    $output = $this->fieldFormatter->view($items);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL) {
    $defaults = [
      'alt'   => $asset->getDescription() ?? $asset->getName(),
      'title' => $asset->getName(),
    ];
    $form = [];

    $map = AssetFormatterManager::matchFieldTypeByAssetType($this->assetType);

    if (!empty($map['extra_settings'])) {
      foreach ($map['extra_settings'] as $callable) {
        if (!is_callable($callable) || !$callable[0] === AssetFeatures::class) {
          continue;
        }
        $form += call_user_func($callable, $defaults);
      }
    }

    $form += $this->fieldFormatter->settingsForm([], new FormState());

    if (isset($form['use_description_as_link_text'])) {
      $form['use_description_as_link_text']['#default_value'] = FALSE;
    }
    return $form;
  }

}
