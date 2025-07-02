<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Functional;

use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the functionality of the Redirect module hooks.
 *
 * @ingroup redirect_api_hooks
 *
 * @group redirect
 */
class RedirectHooksTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['redirect_test'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create two redirects.
    $redirect = Redirect::create();
    $redirect->setSource('/test/redirect/1');
    $redirect->setRedirect('/test/redirect/1/successful');
    $redirect->setStatusCode(301);
    $redirect->save();

    $redirect = Redirect::create();
    $redirect->setSource('/test/redirect/2');
    $redirect->setRedirect('/test/redirect/2/successful');
    $redirect->setStatusCode(301);
    $redirect->save();
  }

  /**
   * Test the redirects.
   */
  public function testRedirectResponseHook() {
    $this->drupalGet('test/redirect/1');
    $this->assertSession()->addressEquals('test/redirect/1/successful');

    $this->drupalGet('test/redirect/2');
    $this->assertSession()->addressEquals('test/redirect/other');
  }

}
