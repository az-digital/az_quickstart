<?php

declare(strict_types=1);

use Drupal\google_tag\Entity\TagContainer;
use Drupal\TestSite\TestSetupInterface;

/**
 * Site install setup.
 */
final class TestSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritDoc}
   */
  public function setup() {
    // @phpstan-ignore-next-line
    \Drupal::service('module_installer')->install([
      'test_page_test',
      'token',
      'google_tag',
      'google_tag_test',
    ]);
    // @todo don't always create one, write command to do so.
    TagContainer::create([
      'id' => 'foo',
      'tag_container_ids' => [
        'GT-XXXXXX',
        'G-XXXXXX',
        'AW-XXXXXX',
        'DC-XXXXXX',
        'UA-XXXXXX',
      ],
      'events' => [
        'login' => [],
        'sign_up' => [],
        'route_name' => [],
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
  }

}
