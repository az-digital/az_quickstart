<?php

namespace Drupal\Tests\antibot\Functional;

use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Cache\PageCacheTagsTestBase;

/**
 * Tests cache tags invalidation.
 *
 * @group antibot
 */
class AntibotCacheTagsTest extends PageCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['antibot'];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that form caches is invalidated when Antibot settings are updated.
   */
  public function testFormCacheInvalidation() {
    $antibot_settings = $this->config('antibot.settings');

    // Unprotect all forms.
    $antibot_settings->set('form_ids', [])->save();

    // Warm the page cache.
    $this->verifyPageCache(Url::fromRoute('user.pass'), 'MISS');
    $this->verifyPageCache(Url::fromRoute('user.pass'), 'HIT');

    // Protect the user password form.
    $antibot_settings->set('form_ids', ['user_pass'])->save();

    // Check that the cache has been invalidated.
    $this->verifyPageCache(Url::fromRoute('user.pass'), 'MISS');
    // Check that the Antibot protection has been added.
    $this->assertSession()->hiddenFieldExists('antibot_key');

    // Reverse user password form protection.
    $antibot_settings->set('form_ids', [])->save();
    $this->verifyPageCache(Url::fromRoute('user.pass'), 'MISS');
    // Check that the Antibot protection has been removed.
    $this->assertSession()->hiddenFieldNotExists('antibot_key');
  }

}
