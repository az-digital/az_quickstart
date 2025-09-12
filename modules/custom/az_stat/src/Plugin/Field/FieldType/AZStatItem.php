<?php

namespace Drupal\az_stat\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines the 'az_stat' field type.
 *
 * @property string $stat_heading
 * @property string $stat_description
 * @property string $stat_source
 * @property string $link_uri
 */
#[FieldType(
  id: "az_stat",
  label: new TranslatableMarkup("Stat"),
  default_widget: "az_stat",
  default_formatter: "az_stat_default",
)]
class AZStatItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $stat_heading = $this->get('stat_heading')->getValue();
    $stat_description = $this->get('stat_description')->getValue();
    $media = $this->get('media')->getValue();
    $stat_source = $this->get('stat_source')->getValue();
    $link_uri = $this->get('link_uri')->getValue();
    return empty($stat_heading) && empty($stat_description) && empty($media) && empty($stat_source) && empty($link_uri);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['stat_heading'] = DataDefinition::create('string')
      ->setLabel(t('Stat Title'));
    $properties['stat_description'] = DataDefinition::create('string')
      ->setLabel(t('Stat Body'));
    $properties['media'] = DataDefinition::create('integer')
      ->setLabel(t('Stat Media'));
    $properties['stat_source'] = DataDefinition::create('string')
      ->setLabel(t('Stat Source'));
    $properties['link_uri'] = DataDefinition::create('string')
      ->setLabel(t('Stat Link URI'));
    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Stat Options'));

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
      'stat_heading' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'stat_description' => [
        'type' => 'text',
        'size' => 'big',
      ],
      'media' => [
        'type' => 'int',
        'size' => 'normal',
      ],
      'stat_source' => [
        'type' => 'text',
        'size' => 'normal',
      ],
      'link_uri' => [
        'type' => 'varchar',
        'length' => 2048,
      ],
      'options' => [
        'type' => 'blob',
        'size' => 'normal',
        'serialize' => TRUE,
      ],
    ];

    $schema = [
      'columns' => $columns,
      'media' => ['media'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {

    $random = new Random();

    $values['stat_heading'] = $random->word(mt_rand(1, 255));

    $values['stat_description'] = $random->paragraphs(5);

    $values['media'] = mt_rand(-1000, 1000);

    $values['stat_source'] = $random->word(mt_rand(1, 255));

    $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
    $domain_length = mt_rand(7, 15);
    $protocol = mt_rand(0, 1) ? 'https' : 'http';
    $www = mt_rand(0, 1) ? 'www' : '';
    $domain = $random->word($domain_length);
    $tld = $tlds[mt_rand(0, (count($tlds) - 1))];
    $values['link_uri'] = "$protocol://$www.$domain.$tld";

    return $values;
  }

}
