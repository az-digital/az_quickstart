<?php

namespace Drupal\Tests\devel\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Render\Element\Button;
use Drupal\Core\Render\Element\Form;
use Drupal\Core\Render\Element\Html;
use Drupal\Core\Url;

/**
 * Tests element info pages and links.
 *
 * @group devel
 */
class DevelElementInfoTest extends DevelBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_menu_block:devel');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalLogin($this->develUser);
  }

  /**
   * Tests element info menu link.
   */
  public function testElementInfoMenuLink(): void {
    $this->drupalPlaceBlock('system_menu_block:devel');
    // Ensures that the element info link is present on the devel menu and that
    // it points to the correct page.
    $this->drupalGet('');
    $this->clickLink('Element Info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/devel/elements');
    $this->assertSession()->pageTextContains('Element Info');
  }

  /**
   * Tests element list page.
   */
  public function testElementList(): void {
    $this->drupalGet('/devel/elements');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Element Info');

    $page = $this->getSession()->getPage();

    // Ensures that the element list table is found.
    $table = $page->find('css', 'table.devel-element-list');
    $this->assertNotNull($table);

    // Ensures that the expected table headers are found.
    $headers = $table->findAll('css', 'thead th');
    $this->assertEquals(4, count($headers));

    $expected_headers = ['Name', 'Provider', 'Class', 'Operations'];
    $actual_headers = array_map(static fn(NodeElement $element) => $element->getText(), $headers);
    $this->assertSame($expected_headers, $actual_headers);

    // Tests the presence of some (arbitrarily chosen) elements in the table.
    $expected_elements = [
      'button' => [
        'class' => Button::class,
        'provider' => 'core',
      ],
      'form' => [
        'class' => Form::class,
        'provider' => 'core',
      ],
      'html' => [
        'class' => Html::class,
        'provider' => 'core',
      ],
    ];

    foreach ($expected_elements as $element_name => $element) {
      $row = $table->find('css', sprintf('tbody tr:contains("%s")', $element_name));
      $this->assertNotNull($row);

      $cells = $row->findAll('css', 'td');
      $this->assertEquals(4, count($cells));

      $cell = $cells[0];
      $this->assertEquals($element_name, $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[1];
      $this->assertEquals($element['provider'], $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[2];
      $this->assertEquals($element['class'], $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[3];
      $actual_href = $cell->findLink('Devel')->getAttribute('href');
      $expected_href = Url::fromRoute('devel.elements_page.detail', ['element_name' => $element_name])->toString();
      $this->assertEquals($expected_href, $actual_href);
    }

    // Ensures that the page is accessible only to the users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('devel/elements');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests element detail page.
   */
  public function testElementDetail(): void {
    $element_name = 'button';

    // Ensures that the page works as expected.
    $this->drupalGet('/devel/elements/' . $element_name);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Element ' . $element_name);

    // Ensures that the page returns a 404 error if the requested element is
    // not defined.
    $this->drupalGet('/devel/elements/not_exists');
    $this->assertSession()->statusCodeEquals(404);

    // Ensures that the page is accessible ony to users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('/devel/elements/' . $element_name);
    $this->assertSession()->statusCodeEquals(403);
  }

}
