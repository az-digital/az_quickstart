<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Kernel\Migrate\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 redirect source plugin.
 *
 * @group redirect
 * @covers Drupal\redirect\Plugin\migrate\source\d7\PathRedirect
 */
class PathRedirectSourceTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['redirect', 'link', 'migrate_drupal', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'redirect_default_status_code',
        'value' => 's:3:"307";',
      ],
    ];

    $tests[0]['source_data']['redirect'] = [
      [
        'rid' => 5,
        'hash' => 'MwmDbnA65ag646gtEdLqmAqTbF0qQerse63RkQmJK_Y',
        'type' => 'redirect',
        'uid' => 5,
        'source' => 'test/source/url',
        'source_options' => '',
        'redirect' => 'test/redirect/url',
        'redirect_options' => '',
        'language' => 'und',
        'status_code' => 301,
        'count' => 2518,
        'access' => 1449497138,
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = $tests[0]['source_data']['redirect'];

    return $tests;
  }

}
