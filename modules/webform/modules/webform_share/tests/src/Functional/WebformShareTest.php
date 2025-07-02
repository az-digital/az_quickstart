<?php

namespace Drupal\Tests\webform_share\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform_share\Element\WebformShareIframe;

/**
 * Webform share test.
 *
 * @group webform_share
 */
class WebformShareTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_share',
  ];

  /**
   * Test share.
   */
  public function testShare() {
    global $base_url;

    $assert_session = $this->assertSession();

    $library = WebformShareIframe::LIBRARY;
    $version = WebformShareIframe::VERSION;

    $config = \Drupal::configFactory()->getEditable('webform.settings');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    /** @var \Drupal\Core\Render\RendererInterface $render */
    $renderer = \Drupal::service('renderer');

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check share page access denied.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(403);

    // Check share script access denied.
    $this->drupalGet('/webform/contact/share.js');
    $assert_session->statusCodeEquals(403);

    // Check share page with javascript access denied.
    $this->drupalGet("/webform/contact/share/$library/$version");
    $assert_session->statusCodeEquals(403);

    // Check share preview access denied.
    $this->drupalGet('/admin/structure/webform/manage/contact/share/preview');
    $assert_session->statusCodeEquals(403);

    // Enable enable share for all webforms.
    $config->set('settings.default_share', TRUE)->save();

    // Check share enabled for all webforms.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/webform/contact/share.js');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/webform/contact/share/$library/$version");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/structure/webform/manage/contact/share/preview');
    $assert_session->statusCodeEquals(200);

    // Enable disable share for all webforms.
    $config->set('settings.default_share', FALSE)->save();

    // Enable share for contact webform.
    $webform->setSetting('share', TRUE)->save();

    // Check share enabled for a single webform.
    $this->drupalGet('/webform/contact/share');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/webform/contact/share.js');
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/webform/contact/share/$library/$version");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet('/admin/structure/webform/manage/contact/share/preview');
    $assert_session->statusCodeEquals(200);

    // Check that query string parameters are included in embed/iframe code.
    $this->drupalGet('/admin/structure/webform/manage/contact/share', ['query' => ['test' => '123']]);
    $assert_session->responseContains("/webform/contact/share?test=123");
    $assert_session->responseContains('/webform/contact/share.js?test=123');
    $assert_session->responseContains("/webform/contact/share/$library/$version?test=123");

    // Check that iframe page is using the default theme.
    $this->drupalGet('/webform/contact/share');
    $assert_session->responseContains('"theme":"' . \Drupal::config('system.theme')->get('default') . '"');

    // Enable the bartik theme and apply to share page.
    \Drupal::service('theme_installer')->install(['olivero']);
    $webform->setSetting('share_theme_name', 'olivero')->save();

    // Check that iframe page is using the bartik theme.
    $this->drupalGet('/webform/contact/share');
    $assert_session->responseContains('"theme":"olivero"');

    // Get the share page.
    $this->drupalGet('/webform/contact/share');

    // Check iframe page html has .webform-share-page-html class.
    $this->assertCssSelect('html.webform-share-page-html');

    // Check iframe page body has .webform-share-page-body class.
    $this->assertCssSelect('body.webform-share-page-body');

    // Check page title.
    $assert_session->responseContains('<h1 class="title page-title">Contact</h1>');

    // Disable the bartik and add custom body attributes.
    $webform
      ->setSetting('share_theme_name', '')
      ->setSetting('share_page_body_attributes', ['class' => ['my-custom-class']])
      ->save();

    // Check iframe page body custom attributes.
    $this->drupalGet('/webform/contact/share');
    $this->assertCssSelect('body.my-custom-class');

    // Disable custom body attributes and hide page title.
    $webform
      ->setSetting('share_page_body_attributes', [])
      ->setSetting('share_title', FALSE)
      ->save();

    // Check no page title.
    $this->drupalGet('/webform/contact/share');
    $assert_session->responseNotContains('<h1>Contact</h1>');

    // Check iframe page iFrame-resizer script.
    $this->drupalGet("/webform/contact/share/$library/$version");
    $assert_session->responseContains('<script src="//cdn.jsdelivr.net/gh/davidjbradshaw/' . $library . '@v' . $version . '/js/iframeResizer.contentWindow.min.js"></script>');

    // Check share.js.
    $this->drupalGet("/webform/contact/share.js");
    $assert_session->responseContains('document.write("');

    // Check share script tag.
    $build = [
      '#type' => 'webform_share_script',
      '#webform' => $webform,
    ];
    $actual_script_tag = $renderer->renderPlain($build);

    $src = $base_url . "/webform/contact/share.js";
    $src = preg_replace('#^https?:#', '', $src);
    $expected_script_tag = '<script src="' . $src . '"></script>' . PHP_EOL;

    $this->assertEquals($expected_script_tag, $actual_script_tag);
  }

}
