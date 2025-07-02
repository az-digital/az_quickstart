<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\Core\Url;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generate lead event test.
 *
 * @group google_tag
 */
final class GenerateLeadEventTest extends GoogleTagTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['google_tag_test'];

  /**
   * Tests generate lead event.
   */
  public function testEvent(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'generate_lead' => [
          'value' => '12',
          'currency' => 'USD',
        ],
      ],
    ])->save();

    $collector = $this->container->get('google_tag.event_collector');
    $collector->addEvent('generate_lead');
    $events = $collector->getEvents();
    self::assertCount(1, $events);
    self::assertEquals('generate_lead', $events[0]->getName());
    self::assertEquals([
      'value' => '12',
      'currency' => 'USD',
    ], $events[0]->getData());
  }

  /**
   * Tests generate lead event without any event config.
   */
  public function testEventWithoutValue(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'generate_lead' => [],
      ],
    ])->save();

    $collector = $this->container->get('google_tag.event_collector');
    $collector->addEvent('generate_lead');
    $events = $collector->getEvents();
    self::assertCount(1, $events);
    self::assertEquals('generate_lead', $events[0]->getName());
    self::assertEquals([], $events[0]->getData());
  }

  /**
   * Tests generate lead event from leads form.
   */
  public function testEventFromForm(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'generate_lead' => [
          'value' => '12',
          'currency' => 'USD',
        ],
      ],
    ])->save();

    $url = Url::fromRoute('google_tag_test.generate_lead_form');
    $this->doRequest(Request::create($url->toString()));
    $form_data = [
      'form_build_id' => (string) $this->cssSelect('input[name="form_build_id"]')[0]->attributes()->value[0],
      'form_id' => (string) $this->cssSelect('input[name="form_id"]')[0]->attributes()->value[0],
      'op' => (string) $this->cssSelect('input[name="op"]')[0]->attributes()->value[0],
    ];
    $request = Request::create($url->toString(), 'POST', $form_data);
    $response = $this->doRequest($request);
    self::assertEquals(303, $response->getStatusCode());
    $request = Request::create($response->headers->get('Location'));
    $this->doRequest($request);

    $this->assertGoogleTagEvents([
      [
        'name' => 'generate_lead',
        'data' => [
          'currency' => 'USD',
          'value' => '100',
        ],
      ],
    ]);
  }

}
