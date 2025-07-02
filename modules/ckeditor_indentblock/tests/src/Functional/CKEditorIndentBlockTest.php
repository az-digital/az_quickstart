<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor_indentblock\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test ckeditor indentblock module.
 *
 * @group ckeditor_indentblock
 */
final class CKEditorIndentBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'ckeditor_indentblock',
    'editor',
    'filter',
    'file',
    'field',
  ];

  /**
   * Test module installation.
   */
  public function testModuleInstall(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('CKEditor IndentBlock');
  }

}
