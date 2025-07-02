<?php

namespace Drupal\Tests\devel\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\devel\Element\ClientSideFilterTable;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ClientSideFilterTable element.
 *
 * @coversDefaultClass \Drupal\devel\Element\ClientSideFilterTable
 * @group devel
 */
class DevelClientSideFilterTableTest extends UnitTestCase {

  /**
   * @covers ::getInfo
   */
  public function testGetInfo(): void {
    $translation = $this->getStringTranslationStub();

    $expected_info = [
      '#filter_label' => $translation->translate('Search'),
      '#filter_placeholder' => $translation->translate('Search'),
      '#filter_description' => $translation->translate('Search'),
      '#header' => [],
      '#rows' => [],
      '#empty' => '',
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#attributes' => [],
      '#pre_render' => [
        [ClientSideFilterTable::class, 'preRenderTable'],
      ],
    ];

    $table = new ClientSideFilterTable([], 'test', 'test');
    $table->setStringTranslation($translation);
    $this->assertEquals($expected_info, $table->getInfo());
  }

  /**
   * @covers ::preRenderTable
   * @dataProvider providerPreRenderTable
   */
  public function testPreRenderTable($element, $expected): void {
    $result = ClientSideFilterTable::preRenderTable($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for preRenderHtmlTag test.
   */
  public static function providerPreRenderTable(): array {
    $data = [];
    $filter_label = new TranslatableMarkup('Label 1');
    $filter_label_2 = new TranslatableMarkup('Label 2');
    $filter_placeholder = new TranslatableMarkup('Placeholder 1');
    $filter_placeholder_2 = new TranslatableMarkup('Placeholder 2');
    $filter_description = new TranslatableMarkup('Description 1');
    $filter_description_2 = new TranslatableMarkup('Description 2');
    $empty = new TranslatableMarkup('Empty');
    $empty_2 = new TranslatableMarkup('Empty 2');
    $actual = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $filter_label,
      '#filter_placeholder' => $filter_placeholder,
      '#filter_description' => $filter_description,
      '#header' => [],
      '#rows' => [],
      '#empty' => $empty,
      '#responsive' => TRUE,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-a-list'],
      ],
    ];

    $expected = [];
    $expected['#attached']['library'][] = 'devel/devel-table-filter';
    $expected['filters'] = [
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => ['class' => ['table-filter', 'js-show']],
      'name' => [
        '#type' => 'search',
        '#size' => 30,
        '#title' => $filter_label,
        '#placeholder' => $filter_placeholder,
        '#attributes' => [
          'class' => ['table-filter-text'],
          'data-table' => ".js-devel-table-filter",
          'autocomplete' => 'off',
          'title' => $filter_description,
        ],
      ],
    ];
    $expected['table'] = [
      '#type' => 'table',
      '#header' => [],
      '#rows' => [],
      '#empty' => $empty,
      '#responsive' => TRUE,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => [
          'devel-a-list',
          'js-devel-table-filter',
          'devel-table-filter',
        ],
      ],
    ];

    $data[] = [$actual, $expected];

    $headers = ['Test1', 'Test2', 'Test3', 'Test4', 'Test5'];

    $actual = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $filter_label_2,
      '#filter_placeholder' => $filter_placeholder_2,
      '#filter_description' => $filter_description_2,
      '#header' => $headers,
      '#rows' => [
        [
          ['data' => 'test1', 'filter' => TRUE],
          ['data' => 'test2', 'filter' => TRUE, 'class' => ['test2']],
          ['data' => 'test3', 'class' => ['test3']],
          ['test4'],
          [
            'data' => 'test5',
            'filter' => TRUE,
            'class' => ['devel-event-name-header'],
            'colspan' => '3',
            'header' => TRUE,
          ],
        ],
      ],
      '#empty' => $empty_2,
      '#responsive' => FALSE,
      '#sticky' => FALSE,
      '#attributes' => [
        'class' => ['devel-some-list'],
      ],
    ];

    $expected = [];
    $expected['#attached']['library'][] = 'devel/devel-table-filter';
    $expected['filters'] = [
      '#type' => 'container',
      '#weight' => -1,
      '#attributes' => ['class' => ['table-filter', 'js-show']],
      'name' => [
        '#type' => 'search',
        '#size' => 30,
        '#title' => $filter_label_2,
        '#placeholder' => $filter_placeholder_2,
        '#attributes' => [
          'class' => ['table-filter-text'],
          'data-table' => ".js-devel-table-filter--2",
          'autocomplete' => 'off',
          'title' => $filter_description_2,
        ],
      ],
    ];
    $expected['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => [
        [
          [
            'data' => 'test1',
            'filter' => TRUE,
            'class' => ['table-filter-text-source'],
          ],
          [
            'data' => 'test2',
            'filter' => TRUE,
            'class' => ['test2', 'table-filter-text-source'],
          ],
          ['data' => 'test3', 'class' => ['test3']],
          ['test4'],
          [
            'data' => 'test5',
            'filter' => TRUE,
            'class' => ['devel-event-name-header', 'table-filter-text-source'],
            'colspan' => '3',
            'header' => TRUE,
          ],
        ],
      ],
      '#empty' => $empty_2,
      '#responsive' => FALSE,
      '#sticky' => FALSE,
      '#attributes' => [
        'class' => [
          'devel-some-list',
          'js-devel-table-filter--2',
          'devel-table-filter',
        ],
      ],
    ];

    $data[] = [$actual, $expected];

    return $data;
  }

}
