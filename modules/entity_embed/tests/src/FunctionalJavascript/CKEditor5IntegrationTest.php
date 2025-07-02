<?php

namespace Drupal\Tests\entity_embed\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\entity_embed\Plugin\CKEditor5Plugin\DrupalEntity
 * @group ckeditor5
 * @internal
 */
class CKEditor5IntegrationTest extends WebDriverTestBase {

  use CKEditor5TestTrait;
  use TestFileCreationTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The sample Node entity to embed.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $snippet;

  /**
   * A host entity with a body field to embed entities in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'entity_embed',
    'text',
    'ckeditor5_test',
    // Because modules/contrib/entity_embed/config/optional/embed.button.node.yml
    // will only then get installed.
    'node',
    // For the `data-entity-embed-test-active-theme` attribute and the ability
    // to simulate preview failures.
    // @see testPreviewUsesDefaultThemeAndIsClientCacheable()
    // @see testErrorMessages()
    'entity_embed_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (version_compare(\Drupal::VERSION, '10.1', '<')) {
      $this->markTestSkipped('Tests covering CKEditor 5 only run on Drupal >= 10.1.');
    }

    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <em> <a href> <drupal-entity alt title data-align data-caption data-view-mode data-entity-embed-display data-entity-embed-display-settings data-entity-uuid data-langcode data-embed-button="node test_node test_media_entity_embed" data-entity-type="node media">',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
        'entity_embed' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
            'link',
            'bold',
            'italic',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);

    // Create a sample node entity to be embedded.
    $this->drupalCreateContentType(['type' => 'snippet']);
    $this->snippet = $this->createNode([
      'type' => 'snippet',
      'title' => 'Vacationing in Belgium',
      'body' => [
        'value' => '<p>Vacationing in Belgium is recommended if you like rain.</p>',
        'format' => 'test_format',
      ],
    ]);
    $this->snippet->save();

    // Create a sample host entity to embed entities in.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->host = $this->createNode([
      'type' => 'blog',
      'title' => 'Travel tips',
      'body' => [
        'value' => '<drupal-entity data-caption="Surrealism is a favorite pastime of Belgians." data-entity-type="node" data-entity-uuid="' . $this->snippet->uuid() . '" data-view-mode="teaser"></drupal-entity>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that `<drupal-entity>` is converted into a block element.
   */
  public function testConversion() {
    // Wrap the `<drupal-entity>` markup in a `<p>`.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<p>foo' . $original_value . '</p>';
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', 'article.node--type-snippet', 1000);
    $editor_html = $this->getEditorDataAsHtmlString();
    // Observe that `<drupal-entity>` was moved into its own block element.
    $this->assertEquals('<p>foo</p>' . $original_value, str_replace('&nbsp;', '', $editor_html));
  }

  /**
   * Tests that only <drupal-entity> tags are processed.
   *
   * @see \Drupal\Tests\entity_embed\Kernel\EntityEmbedFilterTest::testOnlyDrupalEntityTagProcessed()
   */
  public function testOnlyDrupalEntityTagProcessed() {
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('drupal-entity', 'p', $original_value);
    $this->host->save();

    // Assert that `<p data-* …>` is not upcast into a CKEditor Widget.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'article.node--type-snippet', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-entity');

    $this->host->body->value = $original_value;
    $this->host->save();

    // Assert that `<drupal-entity data-* …>` is upcast into a CKEditor Widget.
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.node--type-snippet.node--view-mode-teaser > h2 > a > span.field--name-title'));
    $assert_session->elementExists('css', '.ck-widget.drupal-entity');
  }

  /**
   * Tests that arbitrary attributes are allowed via GHS.
   */
  public function testEntityArbitraryHtml() {
    $assert_session = $this->assertSession();

    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Allow the data-foo attribute in drupal-entity via GHS. Also, add support
    // for div's with data-foo attribute to ensure that drupal-entity elements
    // can be wrapped with other block elements.
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<drupal-entity data-foo>', '<div data-bar>'];
    $editor->setSettings($settings);
    $editor->save();

    $filter_format = $editor->getFilterFormat();
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => '<p> <br> <strong> <em> <a href> <drupal-entity alt title data-align data-caption data-view-mode data-entity-embed-display data-entity-embed-display-settings data-entity-uuid data-langcode data-embed-button="node test_node test_media_entity_embed" data-entity-type="node media" data-foo> <div data-bar>',
      ],
    ]);
    $filter_format->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Add data-foo use to an existing drupal-entity tag.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<div data-bar="baz">' . str_replace('drupal-entity', 'drupal-entity data-foo="bar" ', $original_value) . '</div>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->getSession()->reload();

    // Confirm data-foo is present in the drupal-entity preview.
    $this->assertNotEmpty($upcasted_entity_embed = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-entity'));
    $this->assertFalse($upcasted_entity_embed->hasAttribute('data-foo'));
    $this->assertNotEmpty($preview = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-entity > [data-drupal-entity-preview="ready"] .embedded-entity', 30000));
    $this->assertEquals('bar', $preview->getAttribute('data-foo'));

    // Confirm that the entity is wrapped by the div on the editing view.
    $assert_session->elementExists('css', 'div[data-bar="baz"] div[data-foo="bar"] > article.node--type-snippet');

    // Confirm data-foo is not stripped from source.
    $this->assertSourceAttributeSame('data-foo', 'bar');

    // Confirm that drupal-entity is wrapped by the div.
    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($editor_dom->query('//div[@data-bar="baz"]/drupal-entity'));
  }

  /**
   * Ensures arbitrary attributes can be added on links wrapping entity via GHS.
   *
   * @dataProvider providerLinkability
   */
  public function testLinkedEntityArbitraryHtml(bool $unrestricted): void {
    $assert_session = $this->assertSession();

    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $filter_format = $editor->getFilterFormat();
    if ($unrestricted) {
      $filter_format
        ->setFilterConfig('filter_html', ['status' => FALSE]);
    }
    else {
      // Allow the data-foo attribute in <a> via GHS. Also, add support for div's
      // with data-foo attribute to ensure that linked drupal-entity elements can
      // be wrapped with <div>.
      $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<a data-foo>', '<div data-bar>'];
      $editor->setSettings($settings);
      $filter_format->setFilterConfig('filter_html', [
        'status' => TRUE,
        'settings' => [
          'allowed_html' => '<p> <br> <strong> <em> <a href data-foo> <drupal-entity alt title data-align data-caption data-view-mode data-entity-embed-display data-entity-embed-display-settings data-entity-uuid data-langcode data-embed-button="node test_node test_media_entity_embed" data-entity-type="node media"> <div data-bar>',
        ],
      ]);
    }
    $editor->save();
    $filter_format->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Wrap the existing drupal-entity tag with a div and an a that include
    // attributes allowed via GHS.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<div data-bar="baz"><a href="https://example.com" data-foo="bar">' . $original_value . '</a></div>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    // Confirm data-foo is present in the editing view.
    $this->assertNotEmpty($link = $assert_session->waitForElementVisible('css', 'a[href="https://example.com"]'));
    $this->assertEquals('bar', $link->getAttribute('data-foo'));

    // Confirm that the entity is wrapped by the div on the editing view.
    $assert_session->elementExists('css', 'div[data-bar="baz"] > .drupal-entity > a[href="https://example.com"] > div[data-drupal-entity-preview]');

    // Confirm that drupal-entity is wrapped by the div and a, and that GHS has
    // retained arbitrary HTML allowed by source editing.
    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($editor_dom->query('//div[@data-bar="baz"]/a[@data-foo="bar"]/drupal-entity'));
  }

  /**
   * Tests that failed entity embed preview requests inform the end user.
   */
  public function testErrorMessages() {
    // Assert that a request to the `embed.preview` route that does not
    // result in a 200 response (due to server error or network error) is
    // handled in the JavaScript by displaying the expected error message.
    // @see js/ckeditor5_plugins/drupalentity/src/editing.js
    $this->container->get('state')->set('entity_embed_test.preview.throw_error', TRUE);
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', '.ck-widget.drupal-entity');
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'article.node--type-snippet', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-entity article.node--type-snippet');
    $this->assertNotEmpty($assert_session->waitForText('An error occurred while trying to preview the embedded content. Please save your work and reload this page.'));
    // Now assert that the error doesn't appear when the override to force an
    // error is removed.
    $this->container->get('state')->set('entity_embed_test.preview.throw_error', FALSE);
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.node--type-snippet', 1000));

    // There's a second kind of error message that comes from the back end
    // that happens when the entity uuid can't be converted to an entity
    // preview.
    // @see \Drupal\entity_embed\Plugin\Filter\EntityEmbedFilter::process()
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace($this->snippet->uuid(), 'invalid_uuid', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-entity > [data-drupal-entity-preview="ready"][aria-label="Entity Embed widget"] img[alt="Missing content item."][title="Missing content item."]'));

    // Test that restoring a valid UUID results in the entity embed preview
    // displaying.
    $this->host->body->value = $original_value;
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.node--type-snippet', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-entity > [data-drupal-entity-preview="ready"][aria-label="Preview failed"]');
  }

  /**
   * The CKEditor Widget must load a preview generated using the default theme.
   */
  public function testPreviewUsesDefaultThemeAndIsClientCacheable() {
    // Make the node edit form use the admin theme, like on most Drupal sites.
    $this->config('node.settings')
      ->set('use_admin_theme', TRUE)
      ->save();

    // Allow the test user to view the admin theme.
    $this->adminUser->addRole($this->drupalCreateRole(['view the administration theme']));
    $this->adminUser->save();

    // Configure a different default and admin theme, like on most Drupal sites.
    $this->config('system.theme')
      ->set('default', 'stable9')
      ->set('admin', 'starterkit_theme')
      ->save();

    // Assert that when looking at an embedded entity in the CKEditor Widget,
    // the preview is generated using the default theme, not the admin theme.
    // @see entity_embed_test_entity_view_alter()
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.embedded-entity'));
    $element = $assert_session->elementExists('css', '[data-entity-embed-test-active-theme]');
    $this->assertSame('stable9', $element->getAttribute('data-entity-embed-test-active-theme'));
    // Assert that the first preview request transferred >500 B over the wire.
    // Then toggle source mode on and off. This causes the CKEditor widget to be
    // destroyed and then reconstructed. Assert that during this reconstruction,
    // a second request is sent. This second request should have transferred 0
    // bytes: the browser should have cached the response, thus resulting in a
    // much better user experience.
    $this->assertGreaterThan(500, $this->getLastPreviewRequestTransferSize());
    $this->pressEditorButton('Source');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-source-editing-area'));
    // CKEditor 5 is very smart: if no changes were made in the Source Editing
    // Area, it will not rerender the contents. In this test, we want to verify
    // the that entity preview responses are cached on the client side, so it is
    // essential that rerendering occurs. To achieve this, we append a single
    // space.
    $source_text_area = $this->getSession()->getPage()->find('css', '[name="body[0][value]"] + .ck-editor textarea');
    $source_text_area->setValue($source_text_area->getValue() . ' ');
    $this->pressEditorButton('Source');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.embedded-entity'));
    $this->assertSame(0, $this->getLastPreviewRequestTransferSize());
  }

  /**
   * Tests linkability of the entity CKEditor widget.
   *
   * Due to the very different HTML markup generated for the editing view and
   * the data view, this is explicitly testing the "editingDowncast" and
   * "dataDowncast" results. These are CKEditor 5 concepts.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   *
   * @dataProvider providerLinkability
   */
  public function testLinkability(bool $unrestricted) {
    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

    $page = $this->getSession()->getPage();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();

    // Initial state: the Drupal Entity CKEditor Widget is not selected.
    $drupalentity = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-entity');
    $this->assertNotEmpty($drupalentity);
    $this->assertFalse($drupalentity->hasClass('.ck-widget_selected'));

    // Assert the "editingDowncast" HTML before making changes.
    $assert_session->elementExists('css', '.ck-content .ck-widget.drupal-entity > [data-drupal-entity-preview]');

    // Assert the "dataDowncast" HTML before making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-entity'));
    $this->assertEmpty($xpath->query('//a'));

    // Assert the link button is present and not pressed.
    $link_button = $this->getEditorButton('Link');
    $this->assertSame('false', $link_button->getAttribute('aria-pressed'));

    // Wait for the preview to load.
    $preview = $assert_session->waitForElement('css', '.ck-content .ck-widget.drupal-entity [data-drupal-entity-preview="ready"]');
    $this->assertNotEmpty($preview);

    // Tests linking Drupal entity.
    $drupalentity->click();
    $this->assertTrue($drupalentity->hasClass('ck-widget_selected'));
    $this->assertEditorButtonEnabled('Link');
    // Assert structure of image toolbar balloon.
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Entity Embed toolbar"]');
    $link_entity_embed_button = $this->getBalloonButton('Link entity embed');
    $this->assertSame('false', $link_entity_embed_button->getAttribute('aria-pressed'));
    $link_entity_embed_button->press();

    // Assert structure of link form balloon.
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text');
    // Fill in link form balloon's <input> and hit "Save".
    $url_input->setValue('http://linking-embedded-entity.com');
    $balloon->pressButton('Save');

    // Assert the "editingDowncast" HTML after making changes. Assert the link
    // exists, then assert the link exists. Then assert the expected DOM
    // structure in detail.
    $assert_session->elementExists('css', '.ck-content a[href="http://linking-embedded-entity.com"]');
    $paragraph = $assert_session->elementExists('css', '.ck-content .drupal-entity.ck-widget > a[href="http://linking-embedded-entity.com"] > div[aria-label] article p');
    $this->assertEquals('Vacationing in Belgium is recommended if you like rain.', $paragraph->getText());

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-entity'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-entity.com"]'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-entity.com"]/drupal-entity'));
    // Ensure that the embed caption is retained and not linked.
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-entity.com"]/drupal-entity[@data-caption="Surrealism is a favorite pastime of Belgians."]'));

    // Add `class="trusted"` to the link.
    $this->assertEmpty($xpath->query('//a[@href="http://linking-embedded-entity.com" and @class="trusted"]'));
    $this->pressEditorButton('Source');
    $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
    $this->assertNotEmpty($source_text_area);
    $new_value = str_replace('<a ', '<a class="trusted" ', $source_text_area->getValue());
    $source_text_area->setValue('<p>temp</p>');
    $source_text_area->setValue($new_value);
    $this->pressEditorButton('Source');

    // When unrestricted, additional attributes on links should be retained.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertCount($unrestricted ? 1 : 0, $xpath->query('//a[@href="http://linking-embedded-entity.com" and @class="trusted"]'));

    // Save the entity whose text field is being edited.
    $page->pressButton('Save');

    // Assert the HTML the end user sees.
    $assert_session->elementExists('css', $unrestricted
      ? 'a[href="http://linking-embedded-entity.com"].trusted'
      : 'a[href="http://linking-embedded-entity.com"]');

    // Go back to edit the now *linked* <drupal-entity>. Everything from this
    // point onwards is effectively testing "upcasting" and proving there is no
    // data loss.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    // Assert the "dataDowncast" HTML before making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-entity'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-entity.com"]'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-entity.com"]/drupal-entity'));

    // Tests unlinking.
    $drupalentity->click();
    $this->assertEditorButtonEnabled('Link');
    $this->assertSame('true', $this->getEditorButton('Link')->getAttribute('aria-pressed'));
    // Assert structure of Entity Embed toolbar balloon.
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Entity Embed toolbar"]');
    $link_embed_button = $this->getBalloonButton('Link entity embed');
    $this->assertSame('true', $link_embed_button->getAttribute('aria-pressed'));
    $link_embed_button->click();
    // Assert structure of link actions balloon.
    $this->getBalloonButton('Edit link');
    $unlink_image_button = $this->getBalloonButton('Unlink');
    // Click the "Unlink" button.
    $unlink_image_button->click();
    $this->assertSame('false', $this->getEditorButton('Link')->getAttribute('aria-pressed'));

    // Assert the "editingDowncast" HTML after making changes. Assert the link
    // exists, then assert no link exists. Then assert the expected DOM
    // structure in detail.
    $assert_session->elementNotExists('css', '.ck-content > a');
    $paragraph = $assert_session->elementExists('css', '.ck-content .drupal-entity.ck-widget > div[aria-label] article p');
    $this->assertEquals('Vacationing in Belgium is recommended if you like rain.', $paragraph->getText());

    // Ensure that figcaption exists.
    // @see https://www.drupal.org/project/drupal/issues/3268318
    $assert_session->elementExists('css', '.ck-content .drupal-entity.ck-widget .caption > figcaption');

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-entity'));
    $this->assertEmpty($xpath->query('//a'));
  }

  public static function providerLinkability(): array {
    return [
      'restricted' => [FALSE],
      'unrestricted' => [TRUE],
    ];
  }

  /**
   * Ensure that manual link decorators work with linkable entity embeds.
   *
   * @dataProvider providerLinkability
   */
  public function testLinkManualDecorator(bool $unrestricted) {
    \Drupal::service('module_installer')->install(['ckeditor5_manual_decorator_test']);
    $this->resetAll();

    $decorator = 'Open in a new tab';
    $decorator_attributes = '[@target="_blank"][@rel="noopener noreferrer"][@class="link-new-tab"]';

    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
      $decorator = 'Pink color';
      $decorator_attributes = '[@style="color:pink;"]';
    }

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->assertNotEmpty($drupalentity = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-entity'));
    $drupalentity->click();

    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Entity Embed toolbar"]');
    $this->getBalloonButton('Link entity embed')->click();

    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text');
    $url_input->setValue('http://linking-embedded-entity.com');
    $this->getBalloonButton($decorator)->click();
    $balloon->pressButton('Save');

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-entity > a'));
    $this->assertVisibleBalloon('.ck-link-actions');

    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-entity.com']$decorator_attributes"));
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-entity.com']$decorator_attributes/drupal-entity"));

    // Ensure that manual decorators upcast correctly.
    $page->pressButton('Save');
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->assertNotEmpty($drupalentity = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-entity'));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-entity.com']$decorator_attributes"));
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-entity.com']$decorator_attributes/drupal-entity"));

    // Finally, ensure that entity can be unlinked.
    $drupalentity->click();
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Entity Embed toolbar"]');
    $this->getBalloonButton('Link entity embed')->click();
    $this->assertVisibleBalloon('.ck-link-actions');
    $this->getBalloonButton('Unlink')->click();

    $this->assertTrue($assert_session->waitForElementRemoved('css', '.drupal-entity > a'));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertEmpty($xpath->query('//a'));
    $this->assertNotEmpty($xpath->query('//drupal-entity'));
  }

  /**
   * Tests preview route access.
   *
   * @param bool $entity_embed_enabled
   *   Whether to test with entity_embed filter enabled on the text format.
   * @param bool $can_use_format
   *   Whether the logged in user is allowed to use the text format.
   *
   * @dataProvider previewAccessProvider
   */
  public function testEmbedPreviewAccess(bool $entity_embed_enabled, bool $can_use_format): void {
    // Reconfigure the host entity's text format to suit our needs.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load($this->host->body->format);
    $format->set('filters', [
      'filter_align' => ['status' => TRUE],
      'filter_caption' => ['status' => TRUE],
      'entity_embed' => ['status' => $entity_embed_enabled],
    ]);
    $format->save();

    $permissions = [
      'bypass node access',
    ];
    if ($can_use_format) {
      $permissions[] = $format->getPermissionName();
    }
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet($this->host->toUrl('edit-form'));

    $assert_session = $this->assertSession();
    if ($can_use_format) {
      $this->waitForEditor();
      if ($entity_embed_enabled) {
        // The preview rendering will fail without the CSRF token/header.
        $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.node'));
      }
      else {
        // If the filter isn't enabled, there won't be an error, but the
        // preview shouldn't be rendered.
        $assert_session->elementNotExists('css', 'article.node');
      }
    }
    else {
      $assert_session->pageTextContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    }
  }

  /**
   * Data provider for ::testEmbedPreviewAccess.
   */
  public static function previewAccessProvider(): array {
    return [
      'entity_embed filter enabled' => [
        TRUE,
        TRUE,
      ],
      'entity_embed filter disabled' => [
        FALSE,
        TRUE,
      ],
      'entity_embed filter enabled, user not allowed to use text format' => [
        TRUE,
        FALSE,
      ],
    ];
  }

  /**
   * Ensure entity preview isn't clickable.
   */
  public function testEntityPointerEvent() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $url = $this->host->toUrl('edit-form');
    $this->drupalGet($url);
    $this->waitForEditor();
    $assert_session->waitForText('Vacationing in Belgium');
    $page->find('css', '.ck .drupal-entity')->click();
    // Assert that the entity preview is not clickable by comparing the URL.
    $this->assertEquals($url->toString(), $this->getUrl());
  }

  /**
   * Confirms that a caption can include HTML tags.
   */
  public function testHtmlInCaption() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $embed_with_tags_in_caption = '<drupal-entity data-caption="Alpacas &lt;em&gt;are&lt;/em&gt; cute&lt;br&gt;really!" data-entity-type="node" data-entity-uuid="' . $this->snippet->uuid() . '" data-view-mode="teaser"></drupal-entity>';
    $this->host->body->value = $embed_with_tags_in_caption;
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.ck-content .drupal-entity.ck-widget .caption-drupal-entity > figcaption'));
    $this->assertSame('Alpacas <em>are</em> cute<br>really!', $figcaption->getHtml());
    $this->assertSourceAttributeSame('data-caption', 'Alpacas <em>are</em> cute<br>really!');
    $page->pressButton('Save');
    $this->assertSame('Alpacas <em>are</em> cute<br>really!', $page->find('css', 'figcaption')->getHtml());
    $assert_session->responseNotContains('data-caption');
  }

  /**
   * Tests the "edit embedded entity" balloon button functionality.
   */
  public function testEditEmbeddedEntity() {
    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($drupalentity = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-entity'));
    $drupalentity->click();
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Entity Embed toolbar"]');
    $this->getBalloonButton('Edit the embedded entity (opens in new tab)')->click();

    // The edit form opens in a new tab, so we need to first move to that tab.
    $session = $this->getSession();
    $window_names = $session->getWindowNames();
    $session->switchToWindow($window_names[1]);
    $this->assertSame($this->snippet->toUrl('edit-form', ['absolute' => TRUE])->toString(), $this->getUrl());
  }

  /**
   * Confirm align classes are added to the <figure> wrapper during editing.
   */
  public function testAlignClassAddedToWrapperWhileEditing() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $embed_aligned_right = '<drupal-entity data-align="right" data-entity-type="node" data-entity-uuid="' . $this->snippet->uuid() . '" data-view-mode="teaser"></drupal-entity>';
    $this->host->body->value = $embed_aligned_right;
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $this->assertNotEmpty($assert_session->waitForElement('css', 'figure.drupal-entity.align-right'));
    $this->assertSourceAttributeSame('data-align', 'right');
  }

  /**
   * Verifies value of an attribute on the downcast <drupal-entity> element.
   *
   * Assumes CKEditor is in source mode.
   *
   * @param string $attribute
   *   The attribute to check.
   * @param string|null $value
   *   Either a string value or if NULL, asserts that <drupal-entity> element
   *   doesn't have the attribute.
   *
   * @internal
   */
  protected function assertSourceAttributeSame(string $attribute, ?string $value): void {
    $dom = $this->getEditorDataAsDom();
    $drupal_entity = (new \DOMXPath($dom))->query('//drupal-entity');
    $this->assertNotEmpty($drupal_entity);
    if ($value === NULL) {
      $this->assertFalse($drupal_entity[0]->hasAttribute($attribute));
    }
    else {
      $this->assertSame($value, $drupal_entity[0]->getAttribute($attribute));
    }
  }

  /**
   * Gets the transfer size of the last preview request.
   *
   * @return int
   *   The size of the bytes transferred.
   */
  protected function getLastPreviewRequestTransferSize() {
    $javascript = <<<JS
(function(){
  return window.performance
    .getEntries()
    .filter(function (entry) {
      return entry.initiatorType == 'fetch' && entry.name.indexOf('/embed/preview/test_format') !== -1;
    })
    .pop()
    .transferSize;
})()
JS;
    return $this->getSession()->evaluateScript($javascript);
  }

}
