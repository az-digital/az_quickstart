<?php

declare(strict_types=1);

namespace Drupal\Tests\embed\Functional;

use Drupal\editor\EditorInterface;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests EmbedButtonEditorAccessCheck.
 *
 * @coversDefaultClass \Drupal\embed\Access\EmbedButtonEditorAccessCheck
 * @group embed
 */
class EmbedButtonEditorAccessCheckTest extends EmbedTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
  ];

  /**
   * Sets up the editor for testing.
   *
   * @param string $editor_id
   *   The editor plugin ID.
   *
   * @return \Drupal\editor\EditorInterface
   *   The saved editor entity.
   */
  public function setupEditor(string $editor_id): EditorInterface {
    $editor = Editor::create([
      'format' => 'embed_test',
      'editor' => $editor_id,
    ]);

    switch ($editor_id) {
      case 'ckeditor':
        $editor->setSettings([
          'toolbar' => [
            'rows' => [
              [
                [
                  'name' => 'Embed',
                  'items' => [
                    'embed_test_default',
                  ],
                ],
              ],
            ],
          ],
        ]);
        break;

      case 'ckeditor5':
        $editor->setSettings([
          'plugins' => [],
          'toolbar' => [
            'items' => [
              'embed_test_default',
            ],
          ],
        ]);
        break;

      default:
        $this->fail("Unsupported editor plugin $editor_id.");
    }

    $editor->save();
    return $editor;
  }

  /**
   * Tests \Drupal\embed\Access\EmbedButtonEditorAccessCheck.
   *
   * @covers ::access
   * @dataProvider providerEmbedbuttonEditorAccessCheck
   */
  public function testEmbedButtonEditorAccessCheck(string $editor) {
    $this->setUpEditor($editor);

    // The anonymous user should have access to the plain_text format, but it
    // hasn't been configured to use an editor yet.
    $this->getRoute('plain_text', 'embed_test_default');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:editor.editor.embed_test');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');

    // The anonymous user should not have permission to use embed_test format.
    $this->getRoute('embed_test', 'embed_test_default');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:editor.editor.embed_test');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');

    // Now login a user that can use the embed_test format.
    $this->drupalLogin($this->webUser);

    $this->getRoute('plain_text', 'embed_test_default');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:editor.editor.plain_text');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');

    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => $editor,
    ]);
    $editor->save();
    $this->getRoute('plain_text', 'embed_test_default');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:editor.editor.plain_text');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');

    $this->getRoute('embed_test', 'embed_test_default');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:editor.editor.embed_test');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');
    $this->assertSession()->pageTextContains('Success!');

    // Test route with an empty request.
    $this->getRoute('embed_test', 'embed_test_default', '');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:editor.editor.embed_test');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');

    // Test route with an invalid text format.
    $this->getRoute('invalid_editor', 'embed_test_default');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:editor.editor.invalid_editor');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:embed.button.embed_test_default');

    // Test route with an invalid embed button.
    $this->getRoute('embed_test', 'invalid_button');
    $this->assertSession()->statusCodeEquals(404);
    $this->assertCacheContext('route');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:editor.editor.embed_test');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:embed.button.invalid_button');
  }

  /**
   * Data provider for testEmbedbuttonEditorAccessCheck().
   */
  public static function providerEmbedbuttonEditorAccessCheck(): array {
    return array_map(static function (string $editor_id) {
      return [$editor_id];
    }, ['ckeditor5']);
  }

  /**
   * Performs a request to the embed_test.test_access route.
   *
   * @param string $editor_id
   *   ID of the editor.
   * @param string $embed_button_id
   *   ID of the embed button.
   * @param string $value
   *   The query string value to include.
   *
   * @return string
   *   The retrieved HTML string.
   */
  public function getRoute($editor_id, $embed_button_id, $value = NULL) {
    $url = 'embed-test/access/' . $editor_id . '/' . $embed_button_id;
    if (!isset($value)) {
      $value = 'Success!';
    }
    return $this->drupalGet($url, ['query' => ['value' => $value]]);
  }

}
