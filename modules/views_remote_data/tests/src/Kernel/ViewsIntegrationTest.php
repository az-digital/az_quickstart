<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;

/**
 * Views integration testing.
 *
 * @group views_remote_data
 */
final class ViewsIntegrationTest extends ViewsRemoteDataTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views_remote_data_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['views_remote_data_test']);
  }

  /**
   * Tests that base tables can be used as check in subscriber.
   *
   * @param string $view_id
   *   The view ID.
   * @param array $args
   *   The arguments.
   * @param array $filters
   *   The filters.
   * @param int $expected_count
   *   The expected result count.
   *
   * @dataProvider simpleViews
   */
  public function testSimple(string $view_id, array $args, array $filters, int $expected_count): void {
    $view = Views::getView($view_id);
    self::assertNotNull($view);
    $this->executeView($view, 'default', $args, $filters);
    // Verify both events fired.
    $this->assertCount(2, $this->caughtEvents);
    // Verify the result count.
    self::assertCount($expected_count, $view->result);
  }

  /**
   * Data set for testing simple views (no entity integration.)
   *
   * @return \Generator
   *   The test data.
   */
  public static function simpleViews(): \Generator {
    yield 'views_remote_data_test' => [
      'views_remote_data_test',
      [],
      [],
      2,
    ];
    yield 'views_remote_data_test with arg' => [
      'views_remote_data_test',
      ['name' => 'Alpaca'],
      [],
      1,
    ];
    yield 'views_remote_data_test with filter' => [
      'views_remote_data_test',
      [],
      ['property_foo_bar' => 'baz'],
      1,
    ];
    yield 'views_remote_data_test with arg and filter 1' => [
      'views_remote_data_test',
      ['name' => 'Alpaca'],
      ['property_foo_bar' => 'baz'],
      0,
    ];
    yield 'views_remote_data_test with arg and filter 2' => [
      'views_remote_data_test',
      ['name' => 'Alpaca'],
      ['property_foo_bar' => 'wahoo'],
      1,
    ];
    yield 'views_remote_data_other_test' => [
      'views_remote_data_other_test',
      [],
      [],
      2,
    ];
    yield 'views_remote_data_none_test' => [
      // This view's base table is ignored.
      'views_remote_data_none_test',
      [],
      [],
      0,
    ];
  }

  /**
   * Tests views with stubbed entities attached.
   */
  public function testEntityViews(): void {
    // Setup a user to the entities get rendered.
    // @todo drupalSetUpCurrentUser has a bug when creating the first user.
    // it ends up calling createUser with null permissions.
    $user = $this->createUser([], ['view test entity']);
    $this->drupalSetCurrentUser($user);

    $view = Views::getView('views_remote_data_test_entity_test');
    self::assertNotNull($view);
    $output = $this->executeView($view);
    // Verify both events fired.
    $this->assertCount(2, $this->caughtEvents);
    // Verify the result count.
    self::assertCount(2, $view->result);
    foreach ($view->result as $result) {
      self::assertInstanceOf(EntityTest::class, $result->_entity);
    }

    $cacheability = CacheableMetadata::createFromRenderArray($output);
    self::assertEquals([
      'config:views.view.views_remote_data_test_entity_test',
      'entity_test_list',
      'entity_test:2',
      'entity_test:1',
      'views_remote_data',
      'test_additional_cache_tag',
      'entity_test_view',
    ], $cacheability->getCacheTags());
    self::assertEquals([
      'entity_test_view_grants',
      'languages:language_interface',
      'url.query_args',
      'user.permissions',
      'theme',
    ], array_values($cacheability->getCacheContexts()));
    self::assertEquals(0, $cacheability->getCacheMaxAge());

    $this->render($output);
    $this->assertRaw('<div class="views-row"><div class="views-field views-field-rendered-entity"><span class="field-content">default | Llama
            <div>Llama</div>
      </span></div></div>');
  }

  /**
   * Verifies views result cache.
   */
  public function testViewsCaching(): void {
    $view_entity = View::load('views_remote_data_test_entity_test');
    self::assertNotNull($view_entity);
    $view_entity->addDisplay('default', 'with_cache', 'with_cache');
    $with_cache_display =& $view_entity->getDisplay('with_cache');
    $with_cache_display['display_options']['cache']['type'] = 'views_remote_data_tag';
    $with_cache_display['display_options']['cache']['options'] = [];
    $view_entity->save();

    $view = Views::getView('views_remote_data_test_entity_test');
    self::assertNotNull($view);
    $this->executeView($view, 'with_cache');
    // Verify both events fired.
    $this->assertCount(2, $this->caughtEvents);

    // Verify the previous results were used.
    $view = Views::getView('views_remote_data_test_entity_test');
    self::assertNotNull($view);
    $output = $this->executeView($view, 'with_cache');
    // Only the results are cached, but entities are always attached to the
    // result.
    // @see \Drupal\views\Plugin\views\cache\CachePluginBase::cacheGet().
    $this->assertCount(3, $this->caughtEvents);
    self::assertInstanceOf(RemoteDataQueryEvent::class, $this->caughtEvents[0]);
    self::assertInstanceOf(RemoteDataLoadEntitiesEvent::class, $this->caughtEvents[1]);
    self::assertInstanceOf(RemoteDataLoadEntitiesEvent::class, $this->caughtEvents[2]);

    $cacheability = CacheableMetadata::createFromRenderArray($output);
    self::assertEquals(-1, $cacheability->getCacheMaxAge());
  }

  /**
   * Executes a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param string $display_id
   *   The display ID.
   * @param array $args
   *   The args.
   * @param array $filters
   *   The filters.
   *
   * @return array
   *   The render array for the view output.
   */
  private function executeView(ViewExecutable $view, string $display_id = 'default', array $args = [], array $filters = []): array {
    $view->setDisplay($display_id);
    $view->setExposedInput($filters);
    return $view->executeDisplay($display_id, $args);
  }

}
