<?php

namespace Drupal\Tests\devel\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Url;

/**
 * Tests entity type info pages and links.
 *
 * @group devel
 */
class DevelEntityTypeInfoTest extends DevelBrowserTestBase {

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
   * Tests entity info menu link.
   */
  public function testEntityInfoMenuLink(): void {
    $this->drupalPlaceBlock('system_menu_block:devel');
    // Ensures that the entity type info link is present on the devel menu and
    // that it points to the correct page.
    $this->drupalGet('');
    $this->clickLink('Entity Info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/devel/entity/info');
    $this->assertSession()->pageTextContains('Entity Info');
  }

  /**
   * Tests entity type list page.
   */
  public function testEntityTypeList(): void {
    $this->drupalGet('/devel/entity/info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Entity Info');

    $page = $this->getSession()->getPage();

    // Ensures that the entity type list table is found.
    $table = $page->find('css', 'table.devel-entity-type-list');
    $this->assertNotNull($table);

    // Ensures that the expected table headers are found.
    $headers = $table->findAll('css', 'thead th');
    $this->assertEquals(5, count($headers));

    $expected_headers = ['ID', 'Name', 'Provider', 'Class', 'Operations'];
    $actual_headers = array_map(static fn(NodeElement $element) => $element->getText(), $headers);
    $this->assertSame($expected_headers, $actual_headers);

    // Tests the presence of some (arbitrarily chosen) entity types in the
    // table.
    $expected_types = [
      'date_format' => [
        'name' => 'Date format',
        'class' => DateFormat::class,
        'provider' => 'core',
      ],
      'block' => [
        'name' => 'Block',
        'class' => 'Drupal\block\Entity\Block',
        'provider' => 'block',
      ],
      'entity_view_mode' => [
        'name' => 'View mode',
        'class' => EntityViewMode::class,
        'provider' => 'core',
      ],
    ];

    foreach ($expected_types as $entity_type_id => $entity_type) {
      $row = $table->find('css', sprintf('tbody tr:contains("%s")', $entity_type_id));
      $this->assertNotNull($row);

      $cells = $row->findAll('css', 'td');
      $this->assertEquals(5, count($cells));

      $cell = $cells[0];
      $this->assertEquals($entity_type_id, $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[1];
      $this->assertEquals($entity_type['name'], $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[2];
      $this->assertEquals($entity_type['provider'], $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[3];
      $this->assertEquals($entity_type['class'], $cell->getText());
      $this->assertTrue($cell->hasClass('table-filter-text-source'));

      $cell = $cells[4];
      $actual_href = $cell->findLink('Devel')->getAttribute('href');
      $expected_href = Url::fromRoute('devel.entity_info_page.detail', ['entity_type_id' => $entity_type_id])->toString();
      $this->assertEquals($expected_href, $actual_href);

      $actual_href = $cell->findLink('Fields')->getAttribute('href');
      $expected_href = Url::fromRoute('devel.entity_info_page.fields', ['entity_type_id' => $entity_type_id])->toString();
      $this->assertEquals($expected_href, $actual_href);
    }

    // Ensures that the page is accessible only to the users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('devel/entity/info');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests entity type detail page.
   */
  public function testEntityTypeDetail(): void {
    $entity_type_id = 'date_format';

    // Ensures that the page works as expected.
    $this->drupalGet('/devel/entity/info/' . $entity_type_id);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Entity type ' . $entity_type_id);

    // Ensures that the page returns a 404 error if the requested entity type is
    // not defined.
    $this->drupalGet('/devel/entity/info/not_exists');
    $this->assertSession()->statusCodeEquals(404);

    // Ensures that the page is accessible ony to users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('/devel/entity/info/' . $entity_type_id);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests entity type fields page.
   */
  public function testEntityTypeFields(): void {
    $entity_type_id = 'date_format';

    // Ensures that the page works as expected.
    $this->drupalGet('/devel/entity/fields/' . $entity_type_id);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Entity fields ' . $entity_type_id);

    // Ensures that the page returns a 404 error if the requested entity type is
    // not defined.
    $this->drupalGet('/devel/entity/fields/not_exists');
    $this->assertSession()->statusCodeEquals(404);

    // Ensures that the page is accessible ony to users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('/devel/entity/fields/' . $entity_type_id);
    $this->assertSession()->statusCodeEquals(403);
  }

}
