<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag\MetatagManager;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\az_search_api\Plugin\search_api\processor\Property\AZMetatagProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an individual metatag from the item into the index.
 */
#[SearchApiProcessor(
  id: 'az_metatag',
  label: new TranslatableMarkup('Simple Metatag (Quickstart)'),
  description: new TranslatableMarkup("Retrieves an individual metatag for use in Search API."),
  stages: [
    'add_properties' => 0,
  ],
  locked: TRUE,
  hidden: TRUE,
)]
class AZMetatag extends ProcessorPluginBase {

  /**
   * The MetatagManager. MetatagManagerInterface does not have APIs we need.
   */
  protected ?MetatagManager $metatagManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->metatagManager = $container->get('metatag.manager');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Simple Metatag (Quickstart)'),
        'description' => $this->t('A (possibly inherited) metatag from the item.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['az_metatag'] = new AZMetatagProperty($definition);
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
        ->filterForPropertyPath($fields, NULL, 'az_metatag');

      // Render the metatag tokens for the provided entity.
      $tags = $this->metatagManager->tagsFromEntityWithDefaults($entity);
      $tokens = $this->metatagManager->generateTokenValues($tags, $entity);
      foreach ($fields as $field) {
        // Find out which metatag token this field needs.
        $config = $field->getConfiguration();
        $metatag = $config['value'];
        // Add the metatag token as a value if it exists.
        if (!empty($metatag) && !empty($tokens[$metatag])) {
          $field->addValue($tokens[$metatag]);
        }
      }
    }
  }

}
