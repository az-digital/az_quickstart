<?php

namespace Drupal\Tests\media_library_form_element\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\media_library\FunctionalJavascript\MediaLibraryTestBase;

/**
 * Test using the media library element.
 *
 * @group media_library
 */
class SingleItemTest extends MediaLibraryTestBase {

  use TestFileCreationTrait;

  /**
   * The test fixtures to create.
   *
   * @var array
   */
  protected const FIXTURES = [
    'type_one' => [
      'Dog',
      'Cat',
      'Bear',
      'Horse',
    ],
    'type_two' => [
      'Crocodile',
      'Lizard',
      'Snake',
      'Turtle',
    ],
    'type_three' => [
      '1',
      '2',
      '3',
    ],
  ];

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'media_library',
    'media_library_test',
    'media_library_form_element',
    'media_library_form_element_test',
  ];

  /**
   * Specify the theme to be used in testing.
   *
   * @var string
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Bypass the need in the test module to define schema.
    $this->strictConfigSchema = NULL;

    parent::setUp();

    File::create([
      'filename' => 'duck.png',
      'uri' => 'public://duck.png',
      'filemime' => 'image/png',
      'status' => 1,
    ])->save();

    File::create([
      'filename' => 'platypus.png',
      'uri' => 'public://platypus.png',
      'filemime' => 'image/png',
      'status' => 1,
    ])->save();

    File::create([
      'filename' => 'goose.png',
      'uri' => 'public://goose.png',
      'filemime' => 'image/png',
      'status' => 1,
    ])->save();

    $this->createMediaItems(static::FIXTURES);

    // Create a user that can only add media of type one.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create type_one media',
      'view media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Asserts that only allowed entities are listed in the widget.
   *
   * @param string $selector_type
   *   The css selector of the media library form element to test.
   * @param string $selector
   *   The css selector of the media library form element to test.
   * @param array $allowed_bundles
   *   The bundles that are allowed for the element to test.
   */
  protected function assertAllowedBundles($selector_type, $selector, array $allowed_bundles) {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Open the media library.
    $assert->elementExists($selector_type, $selector)->press();

    // Wait for the media library to open.
    $this->assertNotEmpty($assert->waitForText('Add or select media'));

    // Make sure that the bundle menu works as intended.
    if (count($allowed_bundles) === 1) {

      // If a single bundle is allowed, the menu shouldn't be displayed.
      $assert->elementNotExists('css', '.js-media-library-menu');
    }
    else {

      // Make sure that the proper menu items appear.
      foreach (static::FIXTURES as $bundle => $entities) {

        $media_type = MediaType::load($bundle);
        $media_type_label = $media_type->label();
        if (in_array($bundle, $allowed_bundles, TRUE)) {

          // Wait for the new entities to load in.
          $this->assertNotEmpty($assert->waitForElementVisible('css', '.js-media-library-menu'));

          // If the bundle is allowed, it should be contained in the menu.
          $assert->elementTextContains('css', '.js-media-library-menu', $media_type_label);

          // Switch to the proper bundle.
          $page->clickLink($media_type_label);

          $this->assertNotEmpty($assert->waitForText($media_type_label));

          // Make sure all the entities appear.
          foreach ($entities as $entity) {
            $this->assertNotEmpty($assert->waitForText($entity));
          }
        }
        else {

          // If the bundle is not allowed, it should not be contained in the menu.
          $assert->elementNotContains('css', '.js-media-library-menu', $media_type_label);

          // If the bundle is not allowed, make sure none of the entities appear.
          foreach ($entities as $entity) {
            $assert->linkNotExists($entity);
          }
        }
      }
    }

    // Close out of the media library.
    $assert->elementExists('css', '.ui-dialog-titlebar-close')->press();
  }

  /**
   * Asserts that the media library preview contains the provided items.
   *
   * @param array $items
   *   The items to check for.
   * @param \Drupal\Tests\WebAssert $assert
   *   The web assertion.
   */
  protected function assertPreviewContains(array $items, $assert) {
    foreach ($items as $index => $item) {
      $nth = $index + 1;
      $selector = ".js-media-library-item:nth-of-type($nth) .js-media-library-item-preview";
      $this->assertNotEmpty($assert->waitForElementVisible('css', ".js-media-library-item:nth-of-type($nth) .js-media-library-item-preview"));
      $this->assertNotEmpty($assert->waitForText($item));
      $assert->elementContains('css', $selector, $item);
    }
  }

  /**
   * @param string $selector_type
   *   The css selector of the media library form element to test.
   * @param string $selector
   *   The css selector of the media library form element to test.
   * @param string $bundle
   *   The bundle of the media item to insert.
   * @param int $index
   *   The index of the media item to insert.
   * @param \Behat\Mink\Element\DocumentElement $page
   *   The page object.
   * @param \Drupal\Tests\WebAssert $assert
   *   The web assertion.
   */
  protected function insertMediaItem($selector_type, $selector, $bundle, $index, $page, $assert) {
    $media_type = MediaType::load($bundle);
    $media_type_label = $media_type->label();

    // Open the media library.
    $assert->elementExists($selector_type, $selector)->press();

    $this->assertNotEmpty($assert->waitForText('Add or select media'));
    // Select the proper bundle from the menu (if it exists).
    if ($page->hasLink($media_type_label)) {
      $page->clickLink($media_type_label);
    }

    // Select the item.
    sleep(2);
    $this->assertElementExistsAfterWait('css', 'input[name="media_library_select_form[' . $index . ']"]');
    $page->find('css', 'input[name="media_library_select_form[' . $index . ']"]')->setValue('1');

    $assert->checkboxChecked('media_library_select_form[' . $index . ']');

    // Insert the item.
    $insert_button = $page->find('css', '.ui-dialog-buttonset .form-submit');
    $insert_button->press();
  }

  /**
   * Tests the setting form.
   */
  public function testForm() {
    $this->getSession()->resizeWindow(1200, 5000);
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('media-library-form-element-test-form');

    /*************************************************/
    /* Test for the single cardinality form element. */
    /*************************************************/

    // Check the initial element state.
    $assert->elementContains('css', '#media_single-media-library-wrapper--description', 'Upload or select your profile image');
    $assert->elementContains('css', '#media_single-media-library-wrapper--description', 'One media item remaining');

    // Check that only configured bundles are allowed.
    $this->assertAllowedBundles('css', '#edit-media-single-media-library-open-button', ['type_one', 'type_two']);

    // Insert an item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '#edit-media-single-media-library-open-button', 'type_one', 1, $page, $assert);
    $this->assertPreviewContains(['Dog'], $assert);
    $assert->elementContains('css', '#media_single-media-library-wrapper--description', 'The maximum number of media items have been selected.');

    // Save the form and assert that the selection is persisted.
    $page->pressButton('Save configuration');
    $this->assertPreviewContains(['Dog'], $assert);
    $assert->elementContains('css', '#media_single-media-library-wrapper--description', 'The maximum number of media items have been selected.');

    // Remove all selected items.
    $page->pressButton('Remove');
    $this->waitForNoText('Dog');
    $page->pressButton('Save configuration');

    // Check that the form element is reset to its initial state.
    $assert->pageTextNotContains('Dog');
    $assert->elementContains('css', '#media_single-media-library-wrapper--description', 'One media item remaining');

    /*************************************************/
    /* Test for the multiple cardinality form element. */
    /*************************************************/

    // Check the initial element state.
    $assert->elementContains('css', '#media_multiple-media-library-wrapper--description', 'Upload or select multiple images');
    $assert->elementContains('css', '#media_multiple-media-library-wrapper--description', '2 media items remaining');

    // Check that only configured bundles are allowed.
    $this->assertAllowedBundles('css', '#edit-media-multiple-media-library-open-button', ['type_one']);

    // Insert an item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '#edit-media-multiple-media-library-open-button', 'type_one', 1, $page, $assert);
    $this->assertPreviewContains(['Dog'], $assert);
    $assert->elementContains('css', '#media_multiple-media-library-wrapper--description', 'One media item remaining');

    // Insert a second item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '[id^="edit-media-multiple-media-library-open-button"]', 'type_one', 2, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat'], $assert);
    $assert->elementContains('css', '#media_multiple-media-library-wrapper--description', 'The maximum number of media items have been selected.');

    // Remove all of the items.
    foreach (['Dog', 'Cat'] as $item) {
      $page->pressButton('Remove');
      $this->waitForNoText($item);
      $page->pressButton('Save configuration');

      // Check that the item was removed.
      $assert->pageTextNotContains($item);
    }

    // Check that the form element is reset to its initial state.
    $assert->elementContains('css', '#media_multiple-media-library-wrapper--description', 'Upload or select multiple images');

    /****************************************************/
    /* Test for the unlimited cardinality form element. */
    /****************************************************/

    // Check the initial element state.
    $assert->elementTextContains('css', '#media_unlimited-media-library-wrapper--description', 'Upload or select unlimited images.');

    // Check that only configured bundles are allowed.
    $this->assertAllowedBundles('css', '#edit-media-unlimited-media-library-open-button', ['type_two']);

    // Insert an item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '#edit-media-unlimited-media-library-open-button', 'type_one', 1, $page, $assert);
    $this->assertPreviewContains(['Dog'], $assert);
    $assert->elementTextContains('css', '#media_unlimited-media-library-wrapper--description', 'Upload or select unlimited images.');

    // Insert a second item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 2, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat'], $assert);
    $assert->elementTextContains('css', '#media_unlimited-media-library-wrapper--description', 'Upload or select unlimited images.');

    // Insert a third item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 3, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat', 'Bear'], $assert);
    $assert->elementTextContains('css', '#media_unlimited-media-library-wrapper--description', 'Upload or select unlimited images.');

    // Insert a fourth item and assert that the state updates appropriately.
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 4, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat', 'Bear', 'Horse'], $assert);
    $assert->elementTextContains('css', '#media_unlimited-media-library-wrapper--description', 'Upload or select unlimited images.');

    // Remove all of the items.
    foreach (['Dog', 'Cat', 'Bear', 'Horse'] as $item) {
      $page->pressButton('Remove');
      $this->waitForNoText($item);
      $page->pressButton('Save configuration');

      // Check that the item was removed.
      $assert->pageTextNotContains($item);
    }

    // Check that the form element is reset to its initial state.
    $assert->elementContains('css', '#media_unlimited-media-library-wrapper--description', 'Upload or select unlimited images');

    /*******************************************************/
    /* Test for when a referenced media entity is deleted. */
    /*******************************************************/

    // Add a bunch of media entities.
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 1, $page, $assert);
    $this->assertPreviewContains(['Dog'], $assert);
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 2, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat'], $assert);
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 3, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat', 'Bear'], $assert);
    $this->insertMediaItem('css', '[id^="edit-media-unlimited-media-library-open-button"]', 'type_one', 4, $page, $assert);
    $this->assertPreviewContains(['Dog', 'Cat', 'Bear', 'Horse'], $assert);

    // Save the configuration.
    $page->pressButton('Save configuration');

    // Delete a couple media entities that are referenced above.
    \Drupal::entityTypeManager()->getStorage('media')->load(5)->delete();
    \Drupal::entityTypeManager()->getStorage('media')->load(6)->delete();

    // Ensure that there is not a WSOD.
    $this->drupalGet('media-library-form-element-test-form');
    $assert->pageTextContains('Test Form');
  }

  /**
   * Tests webform.
   */
  public function XtestWebform() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('form/media-element');

    $assert->elementContains('css', '#test-media-library-wrapper--description', 'One media item remaining');

    $page->pressButton('Add media');
    $this->assertNotEmpty($assert->waitForText('Add or select media'));
    $assert->elementContains('css', '.js-media-library-menu a', 'Type One');
    $assert->pageTextContains('Type Two');
    $assert->pageTextNotContains('Type Three');

    $assert->pageTextContains('Horse');
    $assert->pageTextContains('Bear');

    $page->find('css', 'input[name="media_library_select_form[0]"]')->setValue('1');

    $assert->checkboxChecked('media_library_select_form[0]');
    $this->assertNotEmpty($assert->waitForText('Insert selected'));
    $assert->elementExists('css', '.ui-dialog-buttonset')->pressButton('Insert selected');
    $this->assertNotEmpty($assert->waitForText('The maximum number of media items have been selected.'));

    $page->pressButton('Remove');
    $this->assertNotEmpty($assert->waitForText('No media item selected.'));
    $assert->pageTextNotContains('Dog');
    $assert->elementContains('css', '#test-media-library-wrapper--description', 'One media item remaining');
  }

}
