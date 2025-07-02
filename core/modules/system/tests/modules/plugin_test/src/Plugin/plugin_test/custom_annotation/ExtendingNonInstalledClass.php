<?php

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\non_installed_module\NonExisting;

/**
 * This class does not have a plugin attribute or plugin annotation on purpose.
 */
#[\Attribute]
class ExtendingNonInstalledClass extends NonExisting {

  #[TrustedCallback]
  public function testMethod() {}

}
