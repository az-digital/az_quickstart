<?php

namespace Drupal\upgrade_status;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Template\TwigEnvironment;
use Twig\Error\SyntaxError;
use Twig\Source;
use Twig\Util\TemplateDirIterator;

class TwigDeprecationAnalyzer {

  /**
   * The Twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twigEnvironment;

  public function __construct(TwigEnvironment $twig_environment) {
    $this->twigEnvironment = $twig_environment;
  }

  /**
   * Analyzes theme functions in an extension.
   *
   * This is based on Twig\Util\DeprecationCollector which is a final class
   * and thus cannot be extended. While it did find non-twig runtime deprecated
   * errors, it did not gave us the file/line information, so we needed to copy
   * and modify that behavior. We folded in our twig file/line parsing inline
   * then to make it simpler.
   * 
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to be analyzed.
   *
   * @return \Drupal\upgrade_status\DeprecationMessage[]
   */
  public function analyze(Extension $extension): array {
    $deprecations = [];

    set_error_handler(function ($type, $msg, $file, $line) use (&$deprecations) {
      if (\E_USER_DEPRECATED === $type) {
        if (preg_match('!([a-zA-Z0-9\_\-\/]+.html\.twig)!', $msg, $file_matches)) {
          // Caught a Twig syntax based deprecation, record file name and line
          // number from the message we caught.
          preg_match('/(\d+).?$/', $msg, $line_matches);
          $msg = preg_replace('! in (.+)\.twig at line \d+\.!', '.', $msg);
          $msg .= ' See https://drupal.org/node/3071078.';
          $deprecations[] = new DeprecationMessage(
            $msg,
            $file_matches[1],
            $line_matches[1] ?? 0,
            'TwigDeprecationAnalyzer'
          );
        }
        else {
          // Otherwise record the deprecation from the original caught error.
          $deprecations[] = new DeprecationMessage(
            $msg,
            $file,
            $line,
            'TwigDeprecationAnalyzer'
          );
        }
      }
    });

    $iterator = new TemplateDirIterator(
      new TwigRecursiveIterator($extension->getPath())
    );
    foreach ($iterator as $name => $contents) {
      try {
        $this->twigEnvironment->parse($this->twigEnvironment->tokenize(new Source($contents, $name)));
      } catch (SyntaxError $e) {
        // Report twig syntax error which stops us from parsing it.
        $deprecations[] = new DeprecationMessage(
          'Twig template ' . $name . ' contains a syntax error and cannot be parsed.',
          $name,
          $e->getTemplateLine(),
         'TwigDeprecationAnalyzer'
        );
      }
    }
    restore_error_handler();

    // Ensure files are sorted properly.
    usort($deprecations, static function (DeprecationMessage $a, DeprecationMessage $b) {
      return strcmp($a->getFile(), $b->getFile());
    });
    return $deprecations;
  }

}
