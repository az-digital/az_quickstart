<?php

namespace Drupal\az_ranking\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines the 'az_ranking' field type.
 *
 * @property string $ranking_heading
 * @property string $ranking_description
 * @property string $ranking_source
 * @property string $link_uri
 * @property array $options
 */
#[FieldType(
  id: "az_ranking",
  label: new TranslatableMarkup("Ranking"),
  default_widget: "az_ranking",
  default_formatter: "az_ranking_default",
)]
class AZRankingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $ranking_heading = $this->get('ranking_heading')->getValue();
    $ranking_description = $this->get('ranking_description')->getValue();
    $media = $this->get('media')->getValue();
    $ranking_source = $this->get('ranking_source')->getValue();
    $link_uri = $this->get('link_uri')->getValue();
    return empty($ranking_heading) && empty($ranking_description) && empty($media) && empty($ranking_source) && empty($link_uri);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['ranking_heading'] = DataDefinition::create('string')
      ->setLabel(t('Ranking Title'));
    $properties['ranking_description'] = DataDefinition::create('string')
      ->setLabel(t('Ranking Body'));
    $properties['media'] = DataDefinition::create('integer')
      ->setLabel(t('Ranking Media'));
    $properties['ranking_source'] = DataDefinition::create('string')
      ->setLabel(t('Ranking Source'));
    $properties['link_uri'] = DataDefinition::create('string')
      ->setLabel(t('Ranking Link URI'));
    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Ranking Options'));

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
      'ranking_heading' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'ranking_description' => [
        'type' => 'text',
        'size' => 'big',
      ],
      'media' => [
        'type' => 'int',
        'size' => 'normal',
      ],
      'ranking_source' => [
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

    $values['ranking_heading'] = $random->word(mt_rand(1, 255));

    $values['ranking_description'] = $random->paragraphs(5);

    $values['media'] = mt_rand(-1000, 1000);

    $values['ranking_source'] = $random->word(mt_rand(1, 255));

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
