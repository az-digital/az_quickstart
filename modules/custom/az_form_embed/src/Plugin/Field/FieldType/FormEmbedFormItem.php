<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Plugin\Field\FieldType;

use Drupal\az_form_embed\Field\FormEmbedFormItemList;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * What the Form embed component needs to embed one form.
 *
 * Only the computed az_form_embed_form field on Form embed media uses this
 * type, and nothing stores it. Its properties match the component's form
 * prop, so Canvas can pass them straight through.
 *
 * @see \Drupal\az_form_embed\Field\FormEmbedFormItemList
 * @see az_form_embed_canvas_storable_prop_shape_alter()
 */
#[FieldType(
  id: 'az_form_embed_form',
  label: new TranslatableMarkup('Form embed values'),
  description: new TranslatableMarkup('The checked addresses and names a form embed needs.'),
  no_ui: TRUE,
  list_class: FormEmbedFormItemList::class,
)]
class FormEmbedFormItem extends FieldItemBase {

  /**
   * The shape of the component's form prop, as Canvas compares it.
   *
   * Canvas describes a prop by its JSON schema, minus titles, descriptions
   * and examples. The Canvas hook looks for exactly this shape, so keep it in
   * step with components/form-embed/form-embed.component.yml.
   * FormEmbedPropShapeTest fails if the two drift apart. If they did, Canvas
   * couldn't fill the prop and would leave the component out of its library,
   * with no other error.
   *
   * Keep vendor a plain string, not a list of allowed values. Rationale:
   * adding a vendor mustn't change this shape.
   *
   * Careful: once a site has Canvas pages, never remove or rename a property
   * here. Canvas saves each page with the component version it was built
   * with, and that version reads these properties by name. For example,
   * removing one makes those pages log "Property ... is unknown" and drop the
   * form. Opening such a page in Canvas upgrades it but clears the picked
   * form, so an editor has to pick it again on every page.
   */
  public const PROP_SHAPE = [
    'type' => 'object',
    'properties' => [
      'vendor' => ['type' => 'string'],
      'embed_url' => ['type' => 'string', 'format' => 'uri'],
      'canonical_url' => ['type' => 'string', 'format' => 'uri'],
      'label' => ['type' => 'string'],
    ],
    'required' => ['vendor', 'embed_url', 'canonical_url'],
  ];

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['vendor'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Form provider id'));
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
