<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\az_search_api\Plugin\search_api\processor\Property\AZMetatagProperty;

/**
 * Adds an individual metatag from the item into the index.
 */
#[SearchApiProcessor(
  id: 'az_metatag_delimited',
  label: new TranslatableMarkup('Metatag, Delimited (Quickstart)'),
  description: new TranslatableMarkup("Retrieves a metatag for use in Search API, with comma delimiters."),
  stages: [
    'add_properties' => 0,
  ],
  locked: TRUE,
  hidden: TRUE,
)]
class AZMetatagDelimited extends AZMetatag {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Metatag, Delimited (Quickstart)'),
        'description' => $this->t('A (possibly inherited) metatag from the item.'),
        // Alert search API this field has multiple values.
        'is_list' => TRUE,
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['az_metatag_delimited'] = new AZMetatagProperty($definition);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();

    if ($entity) {
      $fields = $item->getFields(FALSE);
      // Get only metatag fields.
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'az_metatag_delimited');

      // Render the metatag tokens for the provided entity.
      $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
      $tokens = $this->metatagManager->generateTokenValues($tags, $entity);
      foreach ($fields as $field) {
        // Find out which metatag token this field needs.
        $config = $field->getConfiguration();
        $metatag = $config['value'];
        // Add the metatag token as a value if it exists.
        if (!empty($metatag) && !empty($tokens[$metatag])) {
          // This class is for delimited metatags.
          $values = explode(',', $tokens[$metatag]);
          foreach ($values as $value) {
            $value = trim($value);
            $field->addValue($value);
          }
        }
      }
    }
  }

}
