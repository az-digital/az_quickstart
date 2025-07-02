<?php

declare(strict_types=1);

namespace Drupal\imagemagick;

/**
 * Enumeration of the possible modes of an Imagemagick command line argument.
 */
enum ArgumentMode {

  case PreSource;
  case PostSource;
  case Internal;

}
