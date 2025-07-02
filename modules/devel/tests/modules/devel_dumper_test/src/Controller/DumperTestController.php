<?php

namespace Drupal\devel_dumper_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for dumper_test module.
 *
 * @package Drupal\devel_dumper_test\Controller
 */
class DumperTestController extends ControllerBase {

  /**
   * The dumper manager.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->dumper = $container->get('devel.dumper');

    return $instance;
  }

  /**
   * Returns the dump output to test.
   *
   * @return array
   *   The render array output.
   */
  public function dump(): array {
    $this->dumper->dump('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the message output to test.
   *
   * @return array
   *   The render array output.
   */
  public function message(): array {
    $this->dumper->message('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the debug output to test.
   *
   * @return array
   *   The render array output.
   */
  public function debug(): array {
    $this->dumper->debug('Test output');

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * Returns the export output to test.
   *
   * @return array
   *   The render array output.
   */
  public function export(): array {
    return [
      '#markup' => $this->dumper->export('Test output'),
    ];
  }

  /**
   * Returns the renderable export output to test.
   *
   * @return array
   *   The render array output.
   */
  public function exportRenderable(): array {
    return $this->dumper->exportAsRenderable('Test output');
  }

}
