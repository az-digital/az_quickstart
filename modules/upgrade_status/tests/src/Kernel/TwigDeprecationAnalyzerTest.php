<?php

namespace Drupal\Tests\upgrade_status\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\upgrade_status\DeprecationMessage;
use DrupalFinder\DrupalFinder;

/**
 * Tests analysing Twig templates.
 *
 * @group upgrade_status
 */
final class TwigDeprecationAnalyzerTest extends KernelTestBase {

  protected static $modules = [
    'upgrade_status',
    'upgrade_status_test_twig',
  ];

  public function testDeprecationReport() {
    $extension = $this->container->get('module_handler')->getModule('upgrade_status_test_twig');
    $templates_directory = $extension->getPath() . '/templates';

    $sut = $this->container->get('upgrade_status.twig_deprecation_analyzer');
    $twig_deprecations = $sut->analyze($extension);

    $this->assertCount(3, $twig_deprecations, var_export($twig_deprecations, TRUE));
    $this->assertContainsEquals(new DeprecationMessage(
      'Since  1: Twig Filter "deprecatedfilter" is deprecated. See https://drupal.org/node/3071078.',
      $templates_directory . '/test.html.twig',
      '10',
     'TwigDeprecationAnalyzer'
    ), $twig_deprecations);

    // Later than Drupal 10.0.0.
    if (version_compare('10.0.0', \Drupal::VERSION) === -1) {
      // Twig 3.15 issues with deprecated options.
      $finder = new DrupalFinder();
      $finder->locateRoot(DRUPAL_ROOT);
      $vendor_path = $finder->getVendorDir();
      $this->assertContainsEquals(new DeprecationMessage(
        'Since twig/twig 3.15: Using the "deprecated", "deprecating_package", and "alternative" options is deprecated, pass a "deprecation_info" one instead.',
        $vendor_path . '/symfony/deprecation-contracts/function.php',
        '25',
        'TwigDeprecationAnalyzer'
      ), $twig_deprecations);
      // Use of spaceless leads to syntax error in Drupal 10.
      $this->assertContainsEquals(new DeprecationMessage(
        sprintf('Twig template %s/spaceless.html.twig contains a syntax error and cannot be parsed.', $templates_directory),
        $templates_directory . '/spaceless.html.twig',
        '2',
        'TwigDeprecationAnalyzer'
      ), $twig_deprecations);
    }
    else {
      // Spaceless deprecation exists in Twig 2 which is in Drupal 9.
      $this->assertContainsEquals(new DeprecationMessage(
        sprintf('The spaceless tag in "%s/spaceless.html.twig" at line 2 is deprecated since Twig 2.7, use the "spaceless" filter with the "apply" tag instead. See https://drupal.org/node/3071078.', $templates_directory),
        $templates_directory . '/spaceless.html.twig',
        0,
        'TwigDeprecationAnalyzer'
      ), $twig_deprecations);
    }
  }

}
