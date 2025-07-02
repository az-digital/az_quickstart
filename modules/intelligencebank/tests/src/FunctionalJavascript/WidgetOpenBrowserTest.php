<?php

namespace Drupal\Tests\intelligencebank\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;


/**
 * Tests that renaming intelligencebank button via hook_form_FORM_ID_alter() works.
 *
 * @group intelligencebank
 * @group intelligencebankJS
 *
 */
class WidgetOpenBrowserTest extends WebDriverTestBase {

  /**
   * A user with permission to work with pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'intelligencebank_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';


  protected function setUp(): void {
    parent::setUp();

    // Create a user that can add media of type image,document,audio,video.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'access content',
      'create content_with_media content',
      'create image media',
      'create document media',
      'create audio media',
      'create video media',
      'access media overview',
      'view media',
    ]);
    $this->drupalLogin($user);

  }

  /**
   * Tests that uploads in the Media library's widget works as expected.
   */
  public function testWidgetOverride() {
    $assert_session = $this->assertSession();
    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/content_with_media');
    $this->openMediaLibraryForField('field_media');
    // Assert the upload form is visible for image.
    $this->switchToMediaType('Image');
    $assert_session->fieldExists('Add file');
    $button_selector = 'a.js-button-add-ib-asset';
    $assert_session->elementExists('css', $button_selector);
    $assert_session->elementContains('css', $button_selector, 'Add Custom Assets');
    $assert_session->linkExists('Add Custom Assets');

    $this->switchToMediaType('Document');
    $assert_session->fieldExists('Add file');
    $assert_session->elementExists('css', $button_selector);

    $this->switchToMediaType('Image');
    $assert_session->fieldExists('Add file');
    $assert_session->elementExists('css', $button_selector);

    $this->getSession()->getPage()->clickLink('Add Custom Assets');
    $this->waitForElementTextContains('.ib-dam-browser-dialog','Custom Asset Browser');

  }

  /**
   * Asserts that text appears in an element after a wait.
   *
   * @param string $selector
   *   The CSS selector of the element to check.
   * @param string $text
   *   The text that should appear in the element.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForElementTextContains($selector, $text, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement('css', "$selector:contains('$text')", $timeout);
    $this->assertNotEmpty($element);
  }

  /**
   * Clicks a button that opens a media widget and confirms it is open.
   *
   * @param string $field_name
   *   The machine name of the field for which to open the media library.
   * @param string $after_open_selector
   *   The selector to look for after the button is clicked.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The NodeElement found via $after_open_selector.
   */
  protected function openMediaLibraryForField($field_name, $after_open_selector = '.js-media-library-menu') {
    $this->assertElementExistsAfterWait('css', "#$field_name-media-library-wrapper.js-media-library-widget")
      ->pressButton('Add media');
    $this->waitForText('Add or select media');

    return $this->assertElementExistsAfterWait('css', $after_open_selector);
  }

  /**
   * Waits for the specified selector and returns it if not empty.
   *
   * @param string $selector
   *   The selector engine name. See ElementInterface::findAll() for the
   *   supported selectors.
   * @param string|array $locator
   *   The selector locator.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The page element node if found. If not found, the test fails.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function assertElementExistsAfterWait($selector, $locator, $timeout = 10000) {
    $element = $this->assertSession()->waitForElement($selector, $locator, $timeout);
    $this->assertNotEmpty($element);
    return $element;
  }

  /**
   * Clicks a media type tab and waits for it to appear.
   */
  protected function switchToMediaType($type) {

    $link = $this->assertSession()
      ->elementExists('named', ['link', "$type"], $this->getTypesMenu());

    if ($link->hasClass('active')) {
      // There is nothing to do as the type is already active.
      return;
    }

    $link->click();
    $result = $link->waitFor(10, function ($link) {
      /** @var \Behat\Mink\Element\NodeElement $link */
      return $link->hasClass('active');
    });
    $this->assertNotEmpty($result);

    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Gets the menu of available media types.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The menu of available media types.
   */
  protected function getTypesMenu() {

    return $this->assertSession()
      ->elementExists('css', '.js-media-library-menu');
  }

  /**
   * Asserts that text appears on page after a wait.
   *
   * @param string $text
   *   The text that should appear on the page.
   * @param int $timeout
   *   Timeout in milliseconds, defaults to 10000.
   *
   * @todo replace with whatever gets added in
   *   https://www.drupal.org/node/3061852
   */
  protected function waitForText($text, $timeout = 10000) {
    $result = $this->assertSession()->waitForText($text, $timeout);
    $this->assertNotEmpty($result, "\"$text\" not found");
  }

}
