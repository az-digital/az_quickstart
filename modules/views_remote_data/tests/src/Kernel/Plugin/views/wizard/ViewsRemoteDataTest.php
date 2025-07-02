<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Plugin\views\wizard;

use Drupal\Core\Form\FormState;
use Drupal\Tests\views_remote_data\Kernel\Plugin\views\ViewsPluginTestBase;
use Drupal\views\Views;
use Drupal\views_remote_data\Plugin\views\wizard\ViewsRemoteData;

/**
 * Tests the wizard plugin.
 *
 * @group remote_views_data
 */
final class ViewsRemoteDataTest extends ViewsPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views_remote_data_test',
  ];

  /**
   * Test the plugin.
   */
  public function testPlugin(): void {
    $instance = $this->container
      ->get('plugin.manager.views.wizard')
      ->createInstance('views_remote_data_standard:views_remote_data_test_simple');
    self::assertInstanceOf(ViewsRemoteData::class, $instance);

    $form = [];
    $form_state = new FormState();
    $instance->validateView($form, $form_state);

    $view = $instance->createView($form, $form_state);
    $display = $view->getDisplay('default');
    self::assertEquals([
      'type' => 'none',
      'options' => [],
    ], $display['display_options']['cache']);
  }

  /**
   * Tests the generated derivatives.
   */
  public function testDeriver(): void {
    $manager = $this->container->get('plugin.manager.views.wizard');
    $views_data = Views::viewsData();
    $valid_bases = array_filter($views_data->getAll(), static function (array $data): bool {
      $query_id = $data['table']['base']['query_id'] ?? '';
      return $query_id === 'views_remote_data_query';
    });
    foreach (array_keys($valid_bases) as $table) {
      self::assertTrue(
        $manager->hasDefinition("views_remote_data_standard:$table")
      );
    }
  }

}
