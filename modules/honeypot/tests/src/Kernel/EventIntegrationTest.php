<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Kernel;

use Drupal\Tests\rules\Kernel\RulesKernelTestBase;

/**
 * Tests for the Symfony event mapping to Rules events.
 *
 * @group honeypot
 */
class EventIntegrationTest extends RulesKernelTestBase {

  /**
   * The entity storage for Rules config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'honeypot',
    'rules',
    'typed_data',
    'field',
    'node',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Tests that rejecting a form submission triggers the Rules event listener.
   */
  public function testHoneypotRejectEvent(): void {
    $rule = $this->expressionManager->createRule();
    $rule->addCondition('rules_test_true');
    $rule->addAction('rules_test_debug_log');

    $config_entity = $this->storage->create([
      'id' => 'test_rule',
      'events' => [['event_name' => 'honeypot.form_submission_rejected']],
      'expression' => $rule->getConfiguration(),
    ]);
    $config_entity->save();

    // The logger instance has changed, refresh it.
    $this->logger = $this->container->get('logger.channel.rules_debug');
    $this->logger->addLogger($this->debugLog);

    // Invoke hook_honeypot_reject() manually, which should trigger the rule.
    $account = $this->container->get('current_user');
    honeypot_honeypot_reject('test_form_id', $account->id(), 'honeypot');

    // Test that the action in the rule logged something.
    $this->assertRulesDebugLogEntryExists('action called');
  }

}
