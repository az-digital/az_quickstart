<?php

namespace Drupal\Tests\auto_entitylabel\Functional;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests prefilled option.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class PrefilledOptionTest extends BrowserTestBase {
  /**
   * Node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Config factory service variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * User variable.
   *
   * @var bool|\Drupal\user\Entity\User|false
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'filter',
    'token',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser([], '', TRUE);
    $this->drupalLogin($this->user);

    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Tests that prefilled option works correctly when adding node.
   */
  public function testPrefilledOption() {
    $webAssert = $this->assertSession();
    $this->configFactory
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}")
      ->set('status', AutoEntityLabelManager::PREFILLED)
      ->set('pattern', 'Test node [current-user:account-name]')
      ->save();
    $this->drupalGet('/node/add/page');
    $webAssert->fieldExists('Title');
    $webAssert->fieldValueEquals('Title', 'Test node ' . $this->user->getAccountName());
  }

}
