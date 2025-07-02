<?php

namespace Drupal\devel\Plugin\Devel\Dumper;

use Drupal\Component\Render\MarkupInterface;
use Drupal\devel\DevelDumperBase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Provides a Symfony VarDumper dumper plugin.
 *
 * @DevelDumper(
 *   id = "var_dumper",
 *   label = @Translation("Symfony var-dumper"),
 *   description = @Translation("Wrapper for <a href='https://github.com/symfony/var-dumper'>Symfony var-dumper</a> debugging tool."),
 * )
 */
class VarDumper extends DevelDumperBase {

  /**
   * {@inheritdoc}
   */
  public function export(mixed $input, ?string $name = NULL): MarkupInterface|string {
    $cloner = new VarCloner();
    $dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();

    $output = fopen('php://memory', 'r+b');
    $dumper->dump($cloner->cloneVar($input), $output);
    $output = stream_get_contents($output, -1, 0);

    if ($name !== NULL && $name !== '') {
      $output = $name . ' => ' . $output;
    }

    return $this->setSafeMarkup($output);
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements(): bool {
    return class_exists(VarCloner::class, TRUE);
  }

}
