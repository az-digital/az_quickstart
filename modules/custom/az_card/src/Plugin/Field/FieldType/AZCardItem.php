<?php

namespace Drupal\az_card\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines the 'az_card' field type.
 *
 * @property string $title
 * @property string $body
 * @property string $body_format
 * @property string $link_title
 * @property string $link_uri
 */
#[FieldType(
  id: "az_card",
  label: new TranslatableMarkup("Card"),
  default_widget: "az_card",
  default_formatter: "az_card_default",
)]
class AZCardItem extends FieldItemBase {

  /**
   * Card Background classes for the Cloud color.
   *
   * Arizona Bootstrap 5.3 renamed Cool Gray to Cloud and kept the old class
   * as an alias, so a saved card can hold either one.
   *
   * @todo Replace with AZBootstrapColorNames::updateClasses() when
   *   https://github.com/az-digital/az_quickstart/pull/6206 is merged.
   *
   * @var string[]
   */
  const CLOUD_BACKGROUND_CLASSES = ['text-bg-cloud', 'text-bg-cool-gray'];

  /**
   * Gets the classes for the card link from its Card Link Style.
   *
   * If the link is a button (has the btn class), remove w-100 (full width),
   * so the button is only as wide as its text, as in the arizona.edu card
   * design. A text link keeps w-100. Rationale: every button option in Card
   * Link Style has w-100 in its saved value, for example 'btn w-100 btn-red'.
   * Removing it from the option values instead would need an update hook to
   * rewrite every saved card.
   *
   * @param string|null $link_style
   *   The saved Card Link Style, such as 'btn w-100 btn-red'.
   *
   * @return string[]
   *   The classes for the link.
   */
  public static function linkClasses(?string $link_style): array {
    $classes = empty($link_style) ? ['btn', 'btn-red'] : explode(' ', $link_style);
    if (in_array('btn', $classes, TRUE)) {
      $classes = array_values(array_diff($classes, ['w-100']));
    }
    return $classes;
  }

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
    $properties['link_uri'] = DataDefinition::create('string')
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
