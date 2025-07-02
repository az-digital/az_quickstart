<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Unit;

use Drupal\linkit\Plugin\Linkit\Matcher\ExternalMatcher;
use Drupal\Tests\UnitTestCase;

/**
 * Ensure that the "External" matcher find only eligible URLs.
 *
 * @group linkit
 */
class ExternalMatcherTest extends UnitTestCase {

  /**
   * @covers Drupal\linkit\Plugin\Linkit\Matcher\ExternalMatcher::canBeUrl;
   */
  public function testValidExternalUrls() {
    $tests = [
      '/node/1' => FALSE,
      '<front>' => FALSE,
      '/sites/default/files/file.pdf' => FALSE,
      'https://drupal.org' => FALSE,
      'http://drupal.org' => FALSE,
      'ftp://drupal.org' => FALSE,
      'www.drupal.org' => TRUE,
      'drupal.org' => TRUE,
      'drupal.org/node/1' => TRUE,
      'drupal.org/node/1?query=1' => TRUE,
      'drupal.org/node/1#anchor' => TRUE,
    ];
    foreach ($tests as $string => $expected) {
      $actual = ExternalMatcher::canBeUrl($string);
      $result = $expected ? " should be " : " should NOT be ";
      $this->assertEquals($expected, $actual, 'The string ' . $string . $result . 'eligible.');
    }

  }

}
