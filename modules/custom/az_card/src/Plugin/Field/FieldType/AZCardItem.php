<?php

namespace Drupal\az_card\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines the 'az_card' field type.
 *
 * @FieldType(
 *   id = "az_card",
 *   label = @Translation("Card"),
 *   category = @Translation("AZ Quickstart"),
 *   default_widget = "az_card",
 *   default_formatter = "az_card_default"
 * )
 */
class AZCardItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $title = $this->get('title')->getValue();
    $body = $this->get('body')->getValue();
    $media = $this->get('media')->getValue();
    $link_title = $this->get('link_title')->getValue();
    $link_uri = $this->get('link_uri')->getValue();
    return empty($title) && empty($body) && empty($media) && empty($link_title) && empty($link_uri);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Card Title'));
    $properties['body'] = DataDefinition::create('string')
      ->setLabel(t('Card Body'));
    $properties['body_format'] = DataDefinition::create('string')
      ->setLabel(t('Card Body Text Format'));
    $properties['media'] = DataDefinition::create('integer')
      ->setLabel(t('Card Media'));
    $properties['link_title'] = DataDefinition::create('string')
      ->setLabel(t('Card Link Title'));
    $properties['link_uri'] = DataDefinition::create('uri')
      ->setLabel(t('Card Link URI'));
    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Card Options'));

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
      'media' => [
        'type' => 'int',
        'size' => 'normal',
      ],
      'link_title' => [
        'type' => 'varchar',
        'length' => 255,
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

    $values['title'] = $random->word(mt_rand(1, 255));

    $values['body'] = $random->paragraphs(5);

    $values['media'] = mt_rand(-1000, 1000);

    $values['link_title'] = $random->word(mt_rand(1, 255));

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
