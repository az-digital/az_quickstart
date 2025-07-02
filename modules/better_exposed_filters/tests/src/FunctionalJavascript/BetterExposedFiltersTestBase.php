<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\better_exposed_filters\Traits\BetterExposedFiltersTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Base class for testing better exposed filters.
 */
class BetterExposedFiltersTestBase extends WebDriverTestBase {

  use BetterExposedFiltersTrait;
  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'views',
    'taxonomy',
    'better_exposed_filters',
    'bef_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable AJAX on the test view.
    \Drupal::configFactory()->getEditable('views.view.bef_test')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    // Create a few test nodes.
    $this->createNode([
      'title' => 'Page One',
      'field_bef_boolean' => '',
      'field_bef_email' => '1bef-test@drupal.org',
      'field_bef_integer' => '1',
      'field_bef_letters' => 'Aardvark',
      // Seattle.
      'field_bef_location' => '10',
      'type' => 'bef_test',
    ]);
    $this->createNode([
      'title' => 'Page Two',
      'field_bef_boolean' => '',
      'field_bef_email' => '2bef-test2@drupal.org',
      'field_bef_integer' => '2',
      'field_bef_letters' => 'Bumble & the Bee',
      // Vancouver.
      'field_bef_location' => '15',
      'type' => 'bef_test',
    ]);
  }

}
