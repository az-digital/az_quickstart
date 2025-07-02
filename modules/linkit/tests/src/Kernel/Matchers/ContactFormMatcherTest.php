<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;

/**
 * Tests contact form matcher.
 *
 * @group linkit
 */
class ContactFormMatcherTest extends LinkitKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contact'];

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

    // Create user 1 who has special permissions.
    $this->createUser();

    \Drupal::currentUser()->setAccount($this->createUser([], ['access site-wide contact form']));

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    ContactForm::create([
      'id' => 'lorem',
      'label' => 'Lorem',
    ])->save();
  }

  /**
   * Tests contact form matcher.
   */
  public function testMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:contact_form', []);
    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(1, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

}
