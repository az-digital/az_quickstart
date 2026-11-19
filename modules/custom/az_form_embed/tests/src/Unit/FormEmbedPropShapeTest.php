<?php

declare(strict_types=1);

namespace Drupal\Tests\az_form_embed\Unit;

use Drupal\az_form_embed\Plugin\Field\FieldType\FormEmbedFormItem;
use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that the Form embed component's form prop matches the Canvas hook.
 *
 * The Canvas hook in az_form_embed looks for FormEmbedFormItem::PROP_SHAPE. If
 * the component's YAML drifts away from it, Canvas quietly leaves the
 * component out of its library. This test fails first.
 */
#[CoversClass(FormEmbedFormItem::class)]
#[Group('az_form_embed')]
class FormEmbedPropShapeTest extends UnitTestCase {

  /**
   * The keys Canvas drops before it compares prop shapes.
   *
   * @see \Drupal\canvas\PropShape\PropShape::normalizePropSchema()
   */
  private const IGNORED_KEYS = ['title', 'description', 'examples', 'meta:enum', 'default'];

  /**
   * The form prop in the YAML matches PROP_SHAPE, once Canvas's way.
   */
  public function testFormPropMatchesCanvasHookShape(): void {
    $path = dirname(__DIR__, 6) . '/components/form-embed/form-embed.component.yml';
    $metadata = Yaml::decode(file_get_contents($path));
    $form_prop = $this->normalize($metadata['props']['properties']['form']);
    // The hook compares with ==, which ignores key order, so this does too.
    $this->assertTrue($form_prop == FormEmbedFormItem::PROP_SHAPE, 'The form prop in form-embed.component.yml matches FormEmbedFormItem::PROP_SHAPE.');
  }

  /**
   * Drops the keys Canvas ignores, at every level.
   *
   * @param array $schema
   *   A prop's JSON schema.
   *
   * @return array
   *   The schema without those keys.
   */
  private function normalize(array $schema): array {
    $schema = array_diff_key($schema, array_flip(self::IGNORED_KEYS));
    if (isset($schema['properties'])) {
      $schema['properties'] = array_map(fn (array $property) => $this->normalize($property), $schema['properties']);
    }
    return $schema;
  }

}
