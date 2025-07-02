<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Unit\Integration\Event;

/**
 * Tests the definition of the "honeypot.form_submission_rejected" event.
 *
 * @coversDefaultClass \Drupal\honeypot\Event\HoneypotRejectEvent
 *
 * @group honeypot
 */
class HoneypotRejectEventTest extends EventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testHoneypotRejectEvent(): void {
    $plugin_definition = $this->eventManager->getDefinition('honeypot.form_submission_rejected');
    $this->assertSame('After rejecting a form submission', (string) $plugin_definition['label']);

    $event = $this->eventManager->createInstance('honeypot.form_submission_rejected');

    $form_id_context_definition = $event->getContextDefinition('form_id');
    $this->assertSame('string', $form_id_context_definition->getDataType());
    $this->assertSame(
      'Rejected form ID',
      $form_id_context_definition->getLabel()
    );

    $uid_context_definition = $event->getContextDefinition('uid');
    $this->assertSame('integer', $uid_context_definition->getDataType());
    $this->assertSame(
      'Rejected user ID',
      $uid_context_definition->getLabel()
    );

    $type_context_definition = $event->getContextDefinition('type');
    $this->assertSame('string', $type_context_definition->getDataType());
    $this->assertSame(
      'Reason for rejection',
      $type_context_definition->getLabel()
    );
  }

}
