<?php

namespace Drupal\devel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\devel\Render\FilteredMarkup;
use Drupal\devel\Twig\Extension\Debug;

/**
 * Defines a base devel dumper implementation.
 *
 * @see \Drupal\devel\Annotation\DevelDumper
 * @see \Drupal\devel\DevelDumperInterface
 * @see \Drupal\devel\DevelDumperPluginManager
 * @see plugin_api
 */
abstract class DevelDumperBase extends PluginBase implements DevelDumperInterface {

  /**
   * {@inheritdoc}
   */
  public function dump($input, $name = NULL): void {
    echo (string) $this->export($input, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function exportAsRenderable($input, $name = NULL): array {
    return ['#markup' => $this->export($input, $name)];
  }

  /**
   * Wrapper for \Drupal\Core\Render\Markup::create().
   *
   * @param mixed $input
   *   The input to mark as a safe string.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The unaltered input value.
   */
  protected function setSafeMarkup(mixed $input): MarkupInterface|string {
    return FilteredMarkup::create($input);
  }

  /**
   * Returns a list of internal functions.
   *
   * The list returned from this method can be used to exclude internal
   * functions from the backtrace output.
   *
   * @return array
   *   An array of internal functions.
   */
  protected function getInternalFunctions(): array {
    $class_name = static::class;
    $manager_class_name = DevelDumperManager::class;

    return [
      [$class_name, 'dump'],
      [$class_name, 'export'],
      [$manager_class_name, 'dump'],
      [$manager_class_name, 'export'],
      [$manager_class_name, 'exportAsRenderable'],
      [$manager_class_name, 'message'],
      [Debug::class, 'dump'],
      'dpm',
      'dvm',
      'dsm',
      'dpr',
      'dvr',
      'kpr',
      'dargs',
      'dcp',
      'dfb',
      'dfbt',
      'dpq',
      'kint',
      'ksm',
      'ddebug_backtrace',
      'kdevel_print_object',
      'backtrace_error_handler',
    ];
  }

}
