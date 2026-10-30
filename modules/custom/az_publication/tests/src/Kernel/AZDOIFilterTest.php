<?php

namespace Drupal\Tests\az_publication\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the DOI filter plugin.
 */
#[Group('az_publication')]
#[RunTestsInSeparateProcesses]
class AZDOIFilterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'az_publication',
    'filter',
    'system',
    'user',
  ];

  /**
   * The DOI filter plugin.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected FilterInterface $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $manager = $this->container->get('plugin.manager.filter');
    $filters = new FilterPluginCollection($manager, []);
    $this->filter = $filters->get('az_doi_filter');
  }

  /**
   * Tests that DOI identifiers are converted to links.
   *
   * @param string $input
   *   The text to filter.
   * @param string $expected
   *   The expected filtered text.
   */
  #[DataProvider('providerProcess')]
  public function testProcess(string $input, string $expected): void {
    $this->assertSame($expected, $this->filter->process($input, 'en')->getProcessedText());
  }

  /**
   * Data provider for testProcess().
   *
   * @return array<string, array{string, string}>
   *   Test cases of input text and expected output.
   */
  public static function providerProcess(): array {
    return [
      'bare DOI' => [
        'See 10.1000/xyz123 for details.',
        'See <a href="https://doi.org/10.1000/xyz123">10.1000/xyz123</a> for details.',
      ],
      'prefixed DOI' => [
        'doi:10.1000/xyz123',
        '<a href="https://doi.org/10.1000/xyz123">doi:10.1000/xyz123</a>',
      ],
      'existing link is skipped' => [
        '<a href="https://example.com">10.1000/xyz123</a>',
        '<a href="https://example.com">10.1000/xyz123</a>',
      ],
      'code is skipped' => [
        '<code>10.1000/xyz123</code>',
        '<code>10.1000/xyz123</code>',
      ],
      'comments are preserved' => [
        '<!-- 10.1000/abc --> 10.1000/xyz123',
        '<!-- 10.1000/abc --> <a href="https://doi.org/10.1000/xyz123">10.1000/xyz123</a>',
      ],
    ];
  }

  /**
   * Tests that the filter exposes no settings.
   */
  public function testSettingsForm(): void {
    $this->assertSame([], $this->filter->settingsForm([], new FormState()));
  }

  /**
   * Tests that the filter tip describes DOI linking.
   */
  public function testTips(): void {
    $this->assertStringContainsString('DOI', (string) $this->filter->tips());
  }

  /**
   * Tests that the text format form builds with the DOI filter available.
   *
   * Regression test for missing filter_url_length settings.
   *
   * @see https://github.com/az-digital/az_quickstart/issues/6185
   */
  public function testFilterFormatForm(): void {
    $this->installConfig(['filter']);
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
    ])->save();

    // Reload so the form builds instances of all available filters.
    $format = FilterFormat::load('test_format');
    $form = $this->container->get('entity.form_builder')->getForm($format, 'edit');
    $this->assertArrayHasKey('az_doi_filter', $form['filters']['status']);
    // Filters without settings do not get a vertical tab.
    $this->assertArrayNotHasKey('az_doi_filter', $form['filters']['settings'] ?? []);
  }

}
