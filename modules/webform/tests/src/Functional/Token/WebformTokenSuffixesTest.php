<?php

namespace Drupal\Tests\webform\Functional\Token;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform token suffixes.
 *
 * @group webform
 */
class WebformTokenSuffixesTest extends WebformBrowserTestBase {

  /**
   * Test webform token suffixes.
   */
  public function testTokenSuffixes() {
    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');

    $tests = [
      // Default.
      [
        'site_name' => 'Testing',
        'text' => '[site:name]',
        'expected' => 'Testing',
        'message' => 'Basic token',
      ],
      // :clear.
      [
        'text' => '[missing]',
        'expected' => '[missing]',
        'message' => 'Missing token',
      ],
      [
        'text' => '[missing:clear]',
        'expected' => '',
        'message' => 'Missing token cleared',
      ],
      [
        'text' => '[missing:clear]',
        'expected' => '[missing:clear]',
        'message' => 'Clear disabled',
        'options' => ['suffixes' => ['clear' => FALSE]],
      ],
      // :htmldecode.
      [
        'site_name' => '<b>Testing</b>',
        'text' => '[site:name]',
        'expected' => '&lt;b&gt;Testing&lt;/b&gt;',
        'message' => 'Basic token with encoded HTML markup',
      ],
      [
        'site_name' => '<b>Testing</b>',
        'text' => '[site:name:htmldecode]',
        'expected' => '<b>Testing</b>',
        'message' => 'Basic token with decoded HTML markup',
      ],
      // :striptags.
      [
        'site_name' => '<b>Testing</b>',
        'text' => '[site:name:htmldecode:striptags]',
        'expected' => 'Testing',
        'message' => 'Basic token with decoded HTML markup',
        'options' => [],
      ],
      // :urlencode.
      [
        'site_name' => 'Two words',
        'text' => '[site:name:urlencode]',
        'expected' => 'Two+words',
        'message' => 'Basic token with url encode',
        'options' => [],
      ],
      // :rawurlencode.
      [
        'site_name' => 'Two words',
        'text' => '[site:name:rawurlencode]',
        'expected' => 'Two%20words',
        'message' => 'Basic token with raw url encode',
        'options' => [],
      ],
      // :xmlencode.
      [
        'site_name' => '<b>Testing</b>',
        'text' => '[site:name:xmlencode]',
        'expected' => '&amp;lt;b&amp;gt;Testing&amp;lt;/b&amp;gt;',
        'message' => 'XML encode',
      ],
      [
        'site_name' => '<b>Testing</b>',
        'text' => '[site:name:htmldecode:xmlencode]',
        'expected' => '&lt;b&gt;Testing&lt;/b&gt;',
        'message' => 'HTML decode and then XML encode',
      ],
      // :base64encode
      [
        'site_name' => 'Testing',
        'text' => '[site:name:base64encode]',
        'expected' => 'VGVzdGluZw==',
        'message' => 'Base64 encode',
      ],
    ];
    foreach ($tests as $test) {
      // Set default options.
      $test += [
        'options' => [],
      ];

      // Set site name.
      if (!empty($test['site_name'])) {
        \Drupal::configFactory()
          ->getEditable('system.site')
          ->set('name', $test['site_name'])
          ->save();
      }
      $result = $token_manager->replace($test['text'], NULL, [], $test['options']);
      $this->assertEquals($result, $test['expected'], $test['message']);
    }

  }

}
