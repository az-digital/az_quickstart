<?php

declare(strict_types=1);

namespace Drupal\imagemagick;

/**
 * The supported package commands.
 */
enum PackageCommand: string {

  case Identify = 'identify';
  case Convert = 'convert';

}
