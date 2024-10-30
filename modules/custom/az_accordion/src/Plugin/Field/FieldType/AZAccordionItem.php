<?php

namespace Drupal\az_accordion\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'az_accordion' field type.
 *
 * @property string $title
 * @property string $body
 * @property string $body_format
 * @property bool $collapsed
 */
#[FieldType(
  id: "az_accordion",
  label: new TranslatableMarkup("Accordion"),
  category: new TranslatableMarkup("AZ Quickstart"),
  default_widget: "az_accordion",
  default_formatter: "az_accordion_default",
)]
class AZAccordionItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $title = $this->get('title')->getValue();
    $body = $this->get('body')->getValue();
    return empty($title) && empty($body);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Accordion Title'));
    $properties['body'] = DataDefinition::create('string')
      ->setLabel(t('Accordion Item'));
    $properties['body_format'] = DataDefinition::create('string')
      ->setLabel(t('Accordion Item Text Format'));
    $properties['collapsed'] = DataDefinition::create('boolean')
      ->setLabel(t('Collapsed by Default'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    // @todo Add more constraints here.
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'title' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'body' => [
        'type' => 'text',
        'size' => 'big',
      ],
      'body_format' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'collapsed' => [
        'type' => 'int',
        'size' => 'tiny',
      ],
    ];

    $schema = [
      'columns' => $columns,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['title'] = $random->word(mt_rand(1, 255));
    $values['body'] = $random->paragraphs(5);
    $values['collapsed'] = mt_rand(0, 1);
    return $values;
  }

}
