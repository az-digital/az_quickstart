<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests email matcher.
 *
 * @group linkit
 */
class EmailMatcherTest extends LinkitKernelTestBase {

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');
  }

  /**
   * Tests email matcher.
   */
  public function testMatcherWithDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    // Entering a valid email address should work.
    $plugin = $this->manager->createInstance('email', []);
    $suggestions = $plugin->execute('drupal@example.com');
    $this->assertCount(1, $suggestions->getSuggestions());
    $this->assertEquals('E-mail drupal@example.com', $suggestions->getSuggestions()[0]->getLabel());
    $this->assertEquals('mailto:drupal@example.com', $suggestions->getSuggestions()[0]->getPath());

    // Make sure an email address with the mailto: prefix works.
    $suggestions = $plugin->execute('mailto:drupal@example.com');
    $this->assertCount(1, $suggestions->getSuggestions());
    $this->assertEquals('E-mail drupal@example.com', $suggestions->getSuggestions()[0]->getLabel());
    $this->assertEquals('mailto:drupal@example.com', $suggestions->getSuggestions()[0]->getPath());

    // No suggestions when an invalid email address was entered.
    $suggestions = $plugin->execute('/drupal');
    $this->assertEmpty($suggestions->getSuggestions());
  }

}
