<?php

declare(strict_types=1);

namespace Drupal\az_media_slate\Plugin\Field\FieldType;

use Drupal\az_media_slate\Field\AzMediaSlateFormItemList;
use Drupal\az_media_slate\SlateUrl;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * What the Slate Form component needs to embed one form.
 *
 * Only the computed az_media_slate_form field on Slate Form media uses this
 * type, and nothing stores it. Its three properties match the component's
 * form prop, so Canvas can pass them straight through.
 *
 * @see \Drupal\az_media_slate\Field\AzMediaSlateFormItemList
 * @see az_media_slate_canvas_storable_prop_shape_alter()
 */
#[FieldType(
  id: 'az_media_slate_form',
  label: new TranslatableMarkup('Slate form embed'),
  description: new TranslatableMarkup('The checked URLs and name a Slate form embed needs.'),
  no_ui: TRUE,
  list_class: AzMediaSlateFormItemList::class,
)]
class AzMediaSlateFormItem extends FieldItemBase {

  /**
   * Builds this item's values from a checked URL.
   *
   * The computed field and the Slate formatter both call this, so the
   * component gets the same values from Canvas and from the page builder.
   *
   * @param \Drupal\az_media_slate\SlateUrl $slate_url
   *   A URL that passed SlateUrl::parse().
   * @param string $label
   *   The media item's name.
   *
   * @return array{embed_url: string, canonical_url: string, label: string}
   *   The values, keyed like the component's form prop.
   */
  public static function valuesFromUrl(SlateUrl $slate_url, string $label): array {
    return [
      'embed_url' => $slate_url->getEmbedUrl(),
      'canonical_url' => $slate_url->getCanonicalUrl(),
      'label' => $label,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['embed_url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Embed URL'));
    $properties['canonical_url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Form URL'));
    $properties['label'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Form name'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * No columns, because the field is computed and never stored.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return ['columns' => []];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'embed_url';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('embed_url')->getValue();
    return $value === NULL || $value === '';
  }

}
