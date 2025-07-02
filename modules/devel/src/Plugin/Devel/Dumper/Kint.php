<?php

namespace Drupal\devel\Plugin\Devel\Dumper;

use Drupal\Component\Render\MarkupInterface;
use Drupal\devel\DevelDumperBase;
use Kint\Kint as KintOriginal;
use Kint\Parser\BlacklistPlugin;
use Kint\Parser\ClassMethodsPlugin;
use Kint\Parser\ClassStaticsPlugin;
use Kint\Parser\IteratorPlugin;
use Kint\Renderer\RichRenderer;
use Psr\Container\ContainerInterface;

/**
 * Provides a Kint dumper plugin.
 *
 * @DevelDumper(
 *   id = "kint",
 *   label = @Translation("Kint"),
 *   description = @Translation("Wrapper for <a href='https://github.com/kint-php/kint'>Kint</a> debugging tool."),
 * )
 */
class Kint extends DevelDumperBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configure();
  }

  /**
   * Configures kint with more sane values.
   */
  protected function configure() {
    // Remove resource-hungry plugins.
    \Kint::$plugins = array_diff(\Kint::$plugins, [
      ClassMethodsPlugin::class,
      ClassStaticsPlugin::class,
      IteratorPlugin::class,
    ]);
    \Kint::$aliases = $this->getInternalFunctions();

    RichRenderer::$folder = FALSE;
    BlacklistPlugin::$shallow_blacklist[] = ContainerInterface::class;
  }

  /**
   * {@inheritdoc}
   */
  public function export(mixed $input, ?string $name = NULL): MarkupInterface|string {
    ob_start();
    if ($name == '__ARGS__') {
      call_user_func_array(['Kint', 'dump'], $input);
      $name = NULL;
    }
    elseif ($name !== NULL) {
      // In order to get the correct access path information returned from Kint
      // we have to give a second parameter here. This is due to a fault in
      // Kint::getSingleCall which returns no info when the number of arguments
      // passed to Kint::dump does not match the number in the original call
      // that invoked the export (such as dsm). However, this second parameter
      // is just treated as the next variable to dump, it is not used as the
      // label. So we give a placeholder value that we can remove below.
      // @see https://gitlab.com/drupalspoons/devel/-/issues/252
      \Kint::dump($input, '---temporary-fix-see-issue-252---');
    }
    else {
      \Kint::dump($input);
    }

    $dump = ob_get_clean();
    if ($name !== NULL && $name !== '') {
      // Kint no longer treats an additional parameter as a custom title, but we
      // can add the required $name as a label at the top of the output.
      $dump = str_replace('<div class="kint-rich">', '<div class="kint-rich">' . $name . ': ', $dump);

      // Remove the output from the second dummy parameter. The pattern in [ ]
      // matches the minimum to ensure we get just the string to be removed.
      $pattern = '/(<dl><dt>[\w\d\s<>\/()]*"---temporary-fix-see-issue-252---"<\/dt><\/dl>)/';
      preg_match($pattern, $dump, $matches);
      if (preg_last_error() === 0 && isset($matches[1])) {
        $dump = str_replace($matches[1], '', $dump);
      }
    }

    return $this->setSafeMarkup($dump);
  }

  /**
   * {@inheritdoc}
   */
  protected function getInternalFunctions(): array {
    return array_merge(parent::getInternalFunctions(), KintOriginal::$aliases);
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements(): bool {
    return class_exists('Kint', TRUE);
  }

}
