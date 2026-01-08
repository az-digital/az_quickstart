<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag\MetatagManager;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\az_search_api\Plugin\search_api\processor\Property\AZSummaryProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's URL to the indexed data.
 */
#[SearchApiProcessor(
  id: 'az_search_summary',
  label: new TranslatableMarkup('Quickstart Summary'),
  description: new TranslatableMarkup("Generates a summary for an entity."),
  stages: [
    'add_properties' => 0,
  ],
  locked: TRUE,
  hidden: TRUE,
)]
class AZSearchSummary extends ProcessorPluginBase {

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
    $processor->setMetatagManager($container->get('metatag.manager'));
    return $processor;
  }

  /**
   * Retrieves the metatag manager.
   *
   * @return \Drupal\metatag\MetatagManager
   *   The MetatagManager
   */
  public function getMetatagManager(): MetatagManager {
    return $this->metatagManager ?: \Drupal::service('metatag.manager');
  }

  /**
   * Sets the metatag manager.
   *
   * @param \Drupal\metatag\MetatagManager $metatag_manager
   *   The metatag manager.
   *
   * @return $this
   */
  public function setMetatagManager(MetatagManager $metatag_manager): static {
    $this->metatagManager = $metatag_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('Search Summary'),
        'description' => $this->t('A generates summary for the item'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['az_search_summary'] = new AZSummaryProperty($definition);
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
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'az_search_summary');
      foreach ($fields as $field) {
        $metatag = $this->getMetatagManager();
        $tags = $metatag->tagsFromEntityWithDefaults($entity);
        $tokens = $metatag->generateTokenValues($tags, $entity);
        $description = $tokens['description'] ?? '';
        // If no metatag, see if we have a body.
        if (empty($description) && $entity->hasField('field_az_body')) {
          $value = $entity->get('field_az_body')->value;
          $format = $entity->get('field_az_body')->format;
          if (!empty($value) && !empty($format)) {
            // Summarize the body for some kind of summary. Not very desirable.
            $summary = text_summary($value, $format);
            if (!empty($summary)) {
              $field->addValue($summary);
            }
          }
        }
        elseif (!empty($description)) {
          $field->addValue($description);
        }

      }
    }
  }

}
