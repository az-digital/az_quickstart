<?php

declare(strict_types=1);

namespace Drupal\imagemagick;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The supported package bundles, ImageMagick and GraphicsMagick.
 */
enum PackageSuite: string {

  case Imagemagick = 'imagemagick';
  case Graphicsmagick = 'graphicsmagick';

  /**
   * Gets a translated label of the package bundle.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A translated label of the package bundle.
   */
  public function label(): TranslatableMarkup {
    return match($this) {
      self::Imagemagick => new TranslatableMarkup('ImageMagick'),
      self::Graphicsmagick => new TranslatableMarkup('GraphicsMagick'),
    };
  }

  /**
   * Gets the cases in an array suitable for selection in forms.
   *
   * @return array<string,\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   An array suitable for selection in forms.
   */
  public static function forSelect(): array {
    return iterator_to_array(self::forSelectIterable());
  }

  /**
   * Gets the cases as an iterable suitable for selection in forms.
   *
   * @return iterable<string,\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   An iterable suitable for selection in forms.
   */
  private static function forSelectIterable(): iterable {
    foreach (self::cases() as $case) {
      yield $case->value => $case->label();
    }
  }

}
