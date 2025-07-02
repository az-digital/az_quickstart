<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Functional\Controller;

use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\Tests\linkit\Functional\LinkitBrowserTestBase;

/**
 * Tests Linkit controller.
 *
 * @group linkit
 */
class LinkitControllerTest extends LinkitBrowserTestBase {

  use ProfileCreationTrait;

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->linkitProfile = $this->createProfile();
  }

  /**
   * Tests the profile route title callback.
   */
  public function testProfileTitle() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id());

    $this->assertSession()->pageTextContains('Edit ' . $this->linkitProfile->label() . ' profile');
  }

  /**
   * Tests the matcher route title callback.
   */
  public function testMatcherTitle() {
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->container->get('plugin.manager.linkit.matcher')->createInstance('configurable_dummy_matcher');
    $matcher_uuid = $this->linkitProfile->addMatcher($plugin->getConfiguration());
    $this->linkitProfile->save();

    $this->drupalGet('/admin/config/content/linkit/manage/' . $this->linkitProfile->id() . '/matchers/' . $matcher_uuid);

    $this->assertSession()->pageTextContains('Edit ' . $plugin->getLabel() . ' matcher');
  }

}
