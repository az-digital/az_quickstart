<?php

namespace Drupal\upgrade_status_test_twig\TwigExtension;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

class DeprecatedFilter extends AbstractExtension {
  public function getFilters() {
    return [new TwigFilter('deprecatedfilter', 'strlen', ['deprecated' => TRUE])];
  }
}
