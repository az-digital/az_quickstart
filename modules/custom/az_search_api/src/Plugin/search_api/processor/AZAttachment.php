<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\media\Entity\Media;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_attachments\Plugin\search_api\processor\FilesExtractor;

/**
 * Indexes all file fields.
 */
#[SearchApiProcessor(
  id: 'az_attachments',
  label: new TranslatableMarkup('Quickstart Attachments'),
  description: new TranslatableMarkup("Extracts all file fields on an entity."),
  stages: [
    'add_properties' => 0,
  ],
)]
class AZAttachment extends FilesExtractor {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Quickstart Attachments'),
        'description' => $this->t('All file fields attached to indexed items'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['az_attachments'] = new ProcessorProperty($definition);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $config = $this->configFactory->get(static::CONFIGNAME);
    $extractor_plugin_id = $config->get('extraction_method');
    // Get the config option to read text files directly.
    $this->configuration['read_text_files_directly'] = $config->get('read_text_files_directly');
    if ($extractor_plugin_id !== '') {
      $configuration = $config->get($extractor_plugin_id . '_configuration');
      $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
      // Get the entity.
      try {
        $entity = $item->getOriginalObject()->getValue();
      }
      catch (SearchApiException) {
        return;
      }
      if (!$entity instanceof FieldableEntityInterface) {
        return;
      }
      $is_entity_type_file = $entity->getEntityTypeId() === 'file';
      foreach ($this->getFileFieldsAndFileEntityItems() as $field_name => $label) {
        $files = [];
        // If the parent entity is not a file, no need to parse the
        // saa static::SAA_FILE_ENTITY item.
        if (!$is_entity_type_file && $field_name === static::SAA_FILE_ENTITY) {
          break;
        }
        if ($is_entity_type_file && $field_name === static::SAA_FILE_ENTITY) {
          $files[] = $entity;
        }

        // A way to load $field.
        foreach ($this->fieldHelper->filterForPropertyPath($item->getFields(), NULL, 'az_attachments') as $field) {
          $all_fids = [];
          if ($entity->hasField($field_name)) {
            // Get type to manage media entity reference case.
            $type = $entity->get($field_name)->getFieldDefinition()->getType();
            if ($type === 'entity_reference') {
              /** @var \Drupal\Core\Field\BaseFieldDefinition $field_def */
              $field_def = $entity->get($field_name)->getFieldDefinition();
              if ($field_def->getItemDefinition()->getSetting('target_type') === 'media') {
                // This is a media field.
                $filefield_values = $entity->get($field_name)->filterEmptyItems()->getValue();
                foreach ($filefield_values as $media_value) {
                  $media = Media::load($media_value['target_id']);
                  if ($media !== NULL) {
                    $bundle_configuration = $media->getSource()->getConfiguration();
                    if (isset($bundle_configuration['source_field'])) {
                      /** @var \Drupal\Core\Field\FieldItemListInterface $field_item */
                      foreach ($media->get($bundle_configuration['source_field'])->filterEmptyItems() as $field_item) {
                        if ($field_item->getFieldDefinition()->getType() === 'file') {
                          $value = $field_item->getValue();
                          $all_fids[] = $value['target_id'];
                        }
                      }
                    }
                  }
                }
              }
            }
            elseif ($type === "file") {
              $filefield_values = $entity->get($field_name)->filterEmptyItems()->getValue();
              foreach ($filefield_values as $filefield_value) {
                $all_fids[] = $filefield_value['target_id'];
              }
            }
            if (!empty($all_fids)) {
              $fids = $this->limitToAllowedNumber($all_fids);
              // Retrieve the files.
              $files = $this->entityTypeManager
                ->getStorage('file')
                ->loadMultiple($fids);
            }
          }
          if (!empty($files)) {
            $extraction = '';
            foreach ($files as $file) {
              if ($this->isFileIndexable($file, $item, $field_name)) {
                $extraction .= $this->extractOrGetFromCache($entity, $file, $extractor_plugin);
              }
            }
            if (!empty($extraction)) {
              $field->addValue($extraction);
            }
          }
        }
      }
    }
  }

}
