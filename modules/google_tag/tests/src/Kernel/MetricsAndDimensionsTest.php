<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel;

use Drupal\google_tag\Entity\TagContainer;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Tests metrics and dimensions.
 *
 * @group google_tag
 */
final class MetricsAndDimensionsTest extends GoogleTagTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['token'];

  /**
   * Tests configured metrics and dimensions in google_tag entity.
   */
  public function testConfiguredMetricsAndDimensions(): void {
    TagContainer::create([
      'id' => 'foo',
      // https://developers.google.com/tag-platform/gtagjs/configure#:~:text=What%20is%20a%20tag%20ID%20and%20where%20to%20find%20it%3F
      // @todo need unit test on config entity for this and the methods of default and additional.
      'tag_container_ids' => [
        'GT-XXXXXX',
        'G-XXXXXX',
        'AW-XXXXXX',
        'DC-XXXXXX',
        'UA-XXXXXX',
      ],
      'dimensions_metrics' => [
        [
          'type' => 'metric',
          'name' => 'foo',
          'value' => '6',
        ],
        [
          'type' => 'dimension',
          'name' => 'langcode',
          'value' => '[language:langcode]',
        ],
      ],
    ])->save();

    $page = [];
    $this->container
      ->get('main_content_renderer.html')
      ->invokePageAttachmentHooks($page);
    self::assertArrayHasKey('gtag', $page['#attached']['drupalSettings']);
    self::assertArrayHasKey('additionalConfigInfo', $page['#attached']['drupalSettings']['gtag']);
    self::assertEquals(
      ['foo' => 6.0, 'langcode' => 'en'],
      $page['#attached']['drupalSettings']['gtag']['additionalConfigInfo']
    );
  }

  /**
   * Tests violations on google_tag form.
   *
   * @dataProvider dimensionMetricsData
   */
  public function testValidation(array $dimensions_metrics, array $expected_violations): void {
    $config = TagContainer::create([
      'id' => 'foo',
      // https://developers.google.com/tag-platform/gtagjs/configure#:~:text=What%20is%20a%20tag%20ID%20and%20where%20to%20find%20it%3F
      // @todo need unit test on config entity for this and the methods of default and additional.
      'tag_container_ids' => [
        'GT-XXXXXX',
        'G-XXXXXX',
        'AW-XXXXXX',
        'DC-XXXXXX',
        'UA-XXXXXX',
      ],
      'dimensions_metrics' => $dimensions_metrics,
    ]);
    $violations = $config->getTypedData()->validate();
    self::assertEquals(
      $expected_violations,
      array_map(
        static fn (ConstraintViolationInterface $constraint) => $constraint->getPropertyPath() . ' ' . $constraint->getMessage(),
        iterator_to_array($violations)
      )
    );
  }

  /**
   * Data provider for validations.
   */
  public static function dimensionMetricsData() {
    // Symfony 4 and 5 allowed empty strings for the Length constraint. This was
    // removed in Symfony 6. The `allowEmptyString` flag was not configureable
    // in Drupal.
    $drupal10 = version_compare(\Drupal::VERSION, '10.0', '>=');

    $invalid_metric_type_violations = [
      'dimensions_metrics.0.type The value you selected is not a valid choice.',
    ];
    if ($drupal10) {
      $invalid_metric_type_violations[] = 'dimensions_metrics.0.name This value cannot be empty.';
      $invalid_metric_type_violations[] = 'dimensions_metrics.0.value This value cannot be empty.';
    }
    yield 'invalid metric type' => [
      [
        [
          'type' => 'aaa',
          'name' => '',
          'value' => '',
        ],
      ],
      $invalid_metric_type_violations,
    ];

    $empty_name_and_value_violations = [];
    if ($drupal10) {
      $empty_name_and_value_violations = [
        'dimensions_metrics.0.name This value cannot be empty.',
        'dimensions_metrics.0.value This value cannot be empty.',
      ];
    }
    yield 'empty name and value' => [
      [
        [
          'type' => 'metric',
          'name' => '',
          'value' => '',
        ],
      ],
      $empty_name_and_value_violations,
    ];
  }

}
