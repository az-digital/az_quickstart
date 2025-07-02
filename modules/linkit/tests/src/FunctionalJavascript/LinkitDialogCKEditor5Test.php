<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\linkit\MatcherInterface;
use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests the Linkit extensions to the CKEditor 5 Link plugin.
 *
 * @group linkit
 * @group ckeditor5
 */
class LinkitDialogCKEditor5Test extends WebDriverTestBase {

  use ProfileCreationTrait;
  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'filter',
    'linkit',
    'entity_test',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An instance of the "CKEditor" text editor plugin.
   *
   * @var \Drupal\ckeditor\Plugin\Editor\CKEditor
   */
  protected $ckeditor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $matcher_manager = $this->container->get('plugin.manager.linkit.matcher');
    $linkit_profile = $this->createProfile();
    $plugin = $matcher_manager->createInstance('entity:entity_test_mul');
    assert($plugin instanceof MatcherInterface);
    $linkit_profile->addMatcher($plugin->getConfiguration());
    $linkit_profile->save();

    // Create text format, associate CKEditor 5, validate.
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <a href data-entity-type data-entity-uuid data-entity-substitution>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [
            'link',
          ],
        ],
        'plugins' => [
          'linkit_extension' => [
            'linkit_enabled' => TRUE,
            'linkit_profile' => $linkit_profile->id(),
          ],
        ],
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

    // Create a node type for testing.
    $this->drupalCreateContentType(['type' => 'page']);

    $account = $this->drupalCreateUser([
      'create page content',
      'use text format test_format',
      'view test entity',
    ]);

    $this->drupalLogin($account);
  }

  /**
   * Test the link dialog.
   */
  public function testLinkDialog() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    // Adds additional languages.
    $langcodes = ['sv', 'da', 'fi'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Create a test entity.
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = EntityTestMul::create(['name' => 'Foo']);
    $entity->save();

    $this->drupalGet('node/add/page');
    $this->waitForEditor();
    $this->pressEditorButton('Link');

    // Find the href field.
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $autocomplete_field = $balloon->find('css', '.ck-input-text');

    // Make sure all fields are empty.
    $this->assertEmpty($autocomplete_field->getValue(), 'Autocomplete field is empty.');

    // Make sure the autocomplete result container is hidden.
    $autocomplete_container = $assert_session->elementExists('css', '.ck-link-form .linkit-ui-autocomplete');
    $this->assertFalse($autocomplete_container->isVisible());

    // Trigger a keydown event to activate a autocomplete search.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-balloon-panel .ck-input'));
    $autocomplete_field->setValue('f');
    $this->assertTrue($this->getSession()->wait(5000, "document.querySelectorAll('.linkit-result-line.ui-menu-item').length > 0"));

    // Make sure the autocomplete result container is visible.
    $this->assertTrue($autocomplete_container->isVisible());

    // Make sure the autocomplete result container is excluded from CKEditor5 CSS resets:
    $assert_session->elementExists('css', '.ck-link-form .linkit-ui-autocomplete.ck-reset_all-excluded');

    // Find all the autocomplete results.
    $results = $page->findAll('css', '.linkit-result-line.ui-menu-item');
    $this->assertCount(1, $results);

    // Find the first result and click it.
    $results[0]->click();

    // Make sure the linkit field field is populated with the test entity's URL.
    $expected_url = base_path() . 'entity_test_mul/manage/1';
    $this->assertSame($expected_url, $autocomplete_field->getValue());
    $balloon->pressButton('Save');
    // Assert balloon was closed by pressing its "Save" button.
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-button-save'));

    // Make sure all attributes are populated.
    $linkit_link = $assert_session->waitForElementVisible('css', '.ck-content a');
    $this->assertNotNull($linkit_link);
    $this->assertSame($expected_url, $linkit_link->getAttribute('href'));
    $this->assertSame('entity_test_mul', $linkit_link->getAttribute('data-entity-type'));
    $this->assertSame($entity->uuid(), $linkit_link->getAttribute('data-entity-uuid'));
    $this->assertSame('canonical', $linkit_link->getAttribute('data-entity-substitution'));

    // Open the edit link dialog by moving selection to the link, verifying the
    // "Link" button is off before and on after, and then pressing that button.
    $this->assertFalse($this->getEditorButton('Link')->hasClass('ck-on'));
    $this->selectTextInsideElement('a');
    $this->assertTrue($this->getEditorButton('Link')->hasClass('ck-on'));
    $this->pressEditorButton('Link');
    $this->assertVisibleBalloon('.ck-link-actions');
    $edit_button = $this->getBalloonButton('Edit link');
    $edit_button->click();
    $link_edit_balloon = $this->assertVisibleBalloon('.ck-link-form');
    $autocomplete_field = $link_edit_balloon->find('css', '.ck-input-text');
    $this->assertSame($expected_url, $autocomplete_field->getValue());
    // Click to trigger the reset of the the autocomplete status.
    $autocomplete_field->click();
    // Enter a URL and verify that no link suggestions are found.
    $autocomplete_field->setValue('http://example.com');
    $autocomplete_field->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForElementVisible('css', '.linkit-result-line.ui-menu-item');
    $results = $page->findAll('css', '.linkit-result-line.ui-menu-item');
    $this->assertCount(1, $results);
    $this->assertSame('http://example.com', $results[0]->find('css', '.linkit-result-line--title')->getText());
    $this->assertSame('No content suggestions found. This URL will be used as is.', $results[0]->find('css', '.linkit-result-line--description')->getText());
    // Decline the autocomplete suggestion.
    $link_edit_balloon->pressButton('Cancel');
    // Accept the link as-is.
    $link_edit_balloon->pressButton('Save');
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-button-save'));
    // Assert balloon is still visible, but now it's again the link actions one.
    $this->assertVisibleBalloon('.ck-link-actions');
    // Assert balloon can be closed by clicking elsewhere in the editor.
    $page->find('css', '.ck-editor__editable')->click();
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-button-save'));

    $changed_link = $assert_session->waitForElementVisible('css', '.ck-content [href="http://example.com"]');
    $this->assertNotNull($changed_link);
    foreach ([
      'data-entity-type',
      'data-entity-uuid',
      'data-entity-substitution',
    ] as $attribute_name) {
      $this->assertFalse($changed_link->hasAttribute($attribute_name), "Link should no longer have $attribute_name");
    }
  }

}
