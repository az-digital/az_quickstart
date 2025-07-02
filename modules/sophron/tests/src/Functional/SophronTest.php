<?php

declare(strict_types=1);

namespace Drupal\Tests\sophron\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\sophron\MimeMapManagerInterface;
use FileEye\MimeMap\MappingException;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Sophron functionality.
 *
 * @group sophron
 */
#[Group('sophron')]
class SophronTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sophron'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));
  }

  /**
   * Test settings form.
   */
  public function testFormAndSettings(): void {
    // The default map has been set by install.
    $this->assertSame(MimeMapManagerInterface::DRUPAL_MAP, \Drupal::configFactory()->get('sophron.settings')->get('map_option'));
    $this->assertSame('', \Drupal::configFactory()->get('sophron.settings')->get('map_class'));

    // Load the form, and change the default map class.
    $this->drupalGet('admin/config/system/sophron');
    $edit = [
      'map_option' => (string) MimeMapManagerInterface::DEFAULT_MAP,
    ];
    $this->submitForm($edit, 'Save configuration');

    // FileEye map has been set as default, and gaps exists.
    $this->assertSession()->responseContains('Mapping gaps');
    $this->assertSame(MimeMapManagerInterface::DEFAULT_MAP, \Drupal::configFactory()->get('sophron.settings')->get('map_option'));
    $this->assertSame('', \Drupal::configFactory()->get('sophron.settings')->get('map_class'));

    // Set an invalid custom mapping class.
    $edit = [
      'map_option' => (string) MimeMapManagerInterface::CUSTOM_MAP,
      'map_class' => BrowserTestBase::class,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The map class is invalid.');
    $edit = [
      'map_option' => (string) MimeMapManagerInterface::DEFAULT_MAP,
    ];
    $this->submitForm($edit, 'Save configuration');

    try {
      $this->assertEquals('application/octet-stream', \Drupal::service(MimeMapManagerInterface::class)->getExtension('quxqux')->getDefaultType());
      $this->fail('MappingException was expected.');
    }
    catch (MappingException $e) {
      // Expected.
    }
    $this->assertSession()->fieldExists('map_commands');
    $edit = [
      'map_commands' => '- {method: addTypeExtensionMapping, arguments: [foo/bar, quxqux]}',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Mapping errors: wrongly typed commands.
    $edit = [
      'map_commands' => "- {foo: aaa}\n- {method: addTypeExtensionMapping, arguments: [a/c, bbbb]}\n- bar: [bbb, ccc]\n",
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The items at line(s) 1, 3 are wrongly typed.');

    // Mapping errors: YAML syntax.
    $edit = [
      'map_commands' => "- {method: aaa}\n{method: bbb}\n",
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('YAML syntax error');

    // Mapping errors: invalid method.
    $edit = [
      'map_commands' => '- {method: aaa, arguments: [paramA, paramB]}',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('Mapping errors');
    $this->assertEquals([
      [
        'method' => 'aaa',
        'arguments' => ['paramA', 'paramB'],
      ],
    ], \Drupal::configFactory()->get('sophron.settings')->get('map_commands'));
  }

}
