<?php

namespace Drupal\Tests\calendar_link\Kernel;

use Drupal\calendar_link\Twig\CalendarLinkTwigExtension;
use Drupal\Component\Utility\Html;
use Drupal\KernelTests\KernelTestBase;
use Twig\TwigFunction;

/**
 * Tests Twig extensions.
 *
 * @group calendar_link
 */
class CalendarLinkTwigExtensionTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['calendar_link'];

  /**
   * Tests that Twig extension loads appropriately.
   */
  public function testTwigExtensionLoaded() {
    $twig_service = \Drupal::service('twig');
    $extension = $twig_service->getExtension(CalendarLinkTwigExtension::class);
    $this->assertEquals(
      get_class($extension),
      CalendarLinkTwigExtension::class,
      'Calendar Link extension loaded successfully.'
    );
  }

  /**
   * Tests that the Twig extension functions are registered properly.
   */
  public function testFunctionsRegistered() {
    /** @var \Twig\TwigFunction $functions */
    $registered_functions = \Drupal::service('twig')
      ->getFunctions();

    $functions = ['calendar_link', 'calendar_links'];

    foreach ($functions as $name) {
      $function = $registered_functions[$name];
      $this->assertTrue($function instanceof TwigFunction);
      $this->assertEquals($function->getName(), $name);
      is_callable($function->getCallable(), TRUE);
    }
  }

  /**
   * Tests the "calendar_link" Twig function.
   *
   * @dataProvider calendarLinkDataProvider
   */
  public function testCalendarLinkFunction($template, $expected_output) {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');
    $output = (string) $environment->renderInline($template);
    $this->assertEquals($expected_output, $output);
  }

  /**
   * Tests the "calendar_links" Twig function.
   */
  public function testCalendarLinksFunction() {
    $template = "{% set title = 'title'|t %}{% set startDate = date('2019-02-24 10:00', 'Etc/UTC') %}{% set endDate = date('2019-02-24 12:00', 'Etc/UTC') %}{% set links = calendar_links(title, startDate, endDate, false, 'description', 'address') %}{% for link in links %}<a href=\"{{ link.url }}\" class=\"calendar-type-{{ link.type_key }}\">Add to {{ link.type_name }}</a>{% endfor %}";
    $expected_template_output = '<a href="https://calendar.google.com/calendar/render?action=TEMPLATE&amp;dates=20190223T230000/20190224T010000&amp;ctz=Etc/UTC&amp;text=title&amp;details=description&amp;location=address" class="calendar-type-google">Add to Google</a><a href="data:text/calendar;charset=utf8;base64,QkVHSU46VkNBTEVOREFSDQpWRVJTSU9OOjIuMA0KUFJPRElEOlNwYXRpZSBjYWxlbmRhci1saW5rcw0KQkVHSU46VkVWRU5UDQpVSUQ6ODdiOGU5OTllNjUzYWNkZmZmN2Y2Yzc4MmQ0YWE5MGUNClNVTU1BUlk6dGl0bGUNCkRUU1RBTVA6MjAxOTAyMjNUMjMwMDAwWg0KRFRTVEFSVDoyMDE5MDIyM1QyMzAwMDBaDQpEVEVORDoyMDE5MDIyNFQwMTAwMDBaDQpERVNDUklQVElPTjpkZXNjcmlwdGlvbg0KTE9DQVRJT046YWRkcmVzcw0KRU5EOlZFVkVOVA0KRU5EOlZDQUxFTkRBUg==" class="calendar-type-ics">Add to iCal</a><a href="https://calendar.yahoo.com/?v=60&amp;view=d&amp;type=20&amp;ST=20190223T230000Z&amp;ET=20190224T010000Z&amp;TITLE=title&amp;DESC=description&amp;in_loc=address" class="calendar-type-yahoo">Add to Yahoo!</a><a href="https://outlook.live.com/calendar/action/compose?path=/calendar/action/compose&amp;rru=addevent&amp;startdt=2019-02-23T23:00:00Z&amp;enddt=2019-02-24T01:00:00Z&amp;subject=title&amp;body=description&amp;location=address" class="calendar-type-webOutlook">Add to Outlook.com</a><a href="https://outlook.office.com/calendar/deeplink/compose?path=/calendar/action/compose&amp;rru=addevent&amp;startdt=2019-02-23T23:00:00Z&amp;enddt=2019-02-24T01:00:00Z&amp;subject=title&amp;body=description&amp;location=address" class="calendar-type-webOffice">Add to Office365</a>';

    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');

    $output = (string) $environment->renderInline($template);
    $this->assertEquals(
      Html::escape($expected_template_output),
      Html::escape($output)
    );
  }

  /**
   * Provides example data for `calendar_link` tests.
   *
   * @return \string[][]
   *   Test values and expected output.
   *
   * @see CalendarLinkTwigExtensionTest::testCalendarLinkFunction()
   *
   * @todo Add render array and field item object tests.
   */
  public function calendarLinkDataProvider(): array {
    return [
      // Dates as simple objects.
      [
        "{% set title = 'title'|t %}{% set startDate = date('2019-02-24 10:00', 'Etc/UTC') %}{% set endDate = date('2019-02-24 12:00', 'Etc/UTC') %}{% set link = calendar_link('ics', title, startDate, endDate, false, 'description', 'location') %}<a href=\"{{ link }}\">Add to calendar</a>",
        '<a href="data:text/calendar;charset=utf8;base64,QkVHSU46VkNBTEVOREFSDQpWRVJTSU9OOjIuMA0KUFJPRElEOlNwYXRpZSBjYWxlbmRhci1saW5rcw0KQkVHSU46VkVWRU5UDQpVSUQ6YTc4ZDRjM2NjNzA3YzRjZGU3NjBiYWQzZmJmZjhlYTENClNVTU1BUlk6dGl0bGUNCkRUU1RBTVA6MjAxOTAyMjNUMjMwMDAwWg0KRFRTVEFSVDoyMDE5MDIyM1QyMzAwMDBaDQpEVEVORDoyMDE5MDIyNFQwMTAwMDBaDQpERVNDUklQVElPTjpkZXNjcmlwdGlvbg0KTE9DQVRJT046bG9jYXRpb24NCkVORDpWRVZFTlQNCkVORDpWQ0FMRU5EQVI=">Add to calendar</a>',
      ],
      // Dates as `<time>` HTML elements (Views behavior).
      [
        "{% set link = calendar_link('yahoo', 'title', '<time datetime=\"2022-03-21T16:00:00Z\" class=\"datetime\">Mon, 03/21/2022 - 09:00</time>', '<time datetime=\"2022-03-26T00:00:00Z\" class=\"datetime\">Fri, 03/25/2022 - 17:00</time>', false, 'description', 'location') %}<a href=\"{{ link }}\">Add to calendar</a>",
        '<a href="https://calendar.yahoo.com/?v=60&amp;view=d&amp;type=20&amp;ST=20220321T160000Z&amp;ET=20220326T000000Z&amp;TITLE=title&amp;DESC=description&amp;in_loc=location">Add to calendar</a>',
      ],
    ];
  }

}
