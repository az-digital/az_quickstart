<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests event info pages and links.
 *
 * @group devel
 */
class DevelEventInfoTest extends DevelBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalLogin($this->develUser);
  }

  /**
   * Tests event info menu link.
   */
  public function testEventsInfoMenuLink(): void {
    $this->drupalPlaceBlock('system_menu_block:devel');
    // Ensures that the events info link is present on the devel menu and that
    // it points to the correct page.
    $this->drupalGet('');
    $this->clickLink('Events Info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/devel/events');
    $this->assertSession()->pageTextContains('Events');
  }

  /**
   * Tests event info page.
   */
  public function testEventList(): void {
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = $this->container->get('event_dispatcher');

    $this->drupalGet('/devel/events');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Events');

    $page = $this->getSession()->getPage();

    // Ensures that the event table is found.
    $table = $page->find('css', 'table.devel-event-list');
    $this->assertNotNull($table);

    // Ensures that the expected table headers are found.
    $headers = $table->findAll('css', 'thead th');
    $this->assertEquals(3, count($headers));

    $expected_headers = ['Event Name', 'Callable', 'Priority'];
    $actual_headers = array_map(static fn($element) => $element->getText(), $headers);
    $this->assertSame($expected_headers, $actual_headers);

    // Ensures that all the events are listed in the table.
    $events = $event_dispatcher->getListeners();
    $event_header_row = $table->findAll('css', 'tbody tr th.devel-event-name-header');
    $this->assertEquals(count($events), count($event_header_row));

    // Tests the presence of some (arbitrarily chosen) events and related
    // listeners in the table. The event items are tested dynamically so no
    // test failures are expected if listeners change.
    $expected_events = [
      'config.delete',
      'kernel.request',
      'routing.route_alter',
    ];

    foreach ($expected_events as $event_name) {
      // Ensures that the event header is present in the table.
      $event_header_row = $table->findAll('css', sprintf('tbody tr th:contains("%s")', $event_name));
      $this->assertTrue(count($event_header_row) >= 1);

      // Ensures that all the event listener are listed in the table.
      $event_rows = $table->findAll('css', sprintf('tbody tr:contains("%s")', $event_name));
      // Remove the header row.
      array_shift($event_rows);
      $listeners = $event_dispatcher->getListeners($event_name);
      foreach ($listeners as $index => $listener) {
        $cells = $event_rows[$index]->findAll('css', 'td');
        $this->assertEquals(3, count($cells));

        $cell_event_name = $cells[0];
        $this->assertEquals($event_name, $cell_event_name->getText());
        $this->assertTrue($cell_event_name->hasClass('table-filter-text-source'));
        $this->assertTrue($cell_event_name->hasClass('visually-hidden'));

        $cell_callable = $cells[1];
        is_callable($listener, TRUE, $callable_name);
        $this->assertEquals($callable_name, $cell_callable->getText());

        $cell_methods = $cells[2];
        $priority = $event_dispatcher->getListenerPriority($event_name, $listener);
        $this->assertEquals($priority, $cell_methods->getText());
      }
    }

    // Ensures that the page is accessible only to the users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('devel/events');
    $this->assertSession()->statusCodeEquals(403);
  }

}
