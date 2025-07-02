<?php

namespace Drupal\blazy\Utility;

use Drupal\Component\Utility\Xss;
use League\CommonMark\CommonMarkConverter;
use Michelf\MarkdownExtra;

/**
 * Provides markdown utilities only useful for the help text.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Please use the public method instead.
 */
class Markdown {

  /**
   * Processes Markdown text, and convert into HTML suitable for the help text.
   *
   * @param string $text
   *   The text to apply the Markdown filter to.
   * @param bool $help
   *   True, if the text will be used for Help pages.
   * @param bool $sanitize
   *   True, if the text should be sanitized.
   *
   * @return string
   *   The filtered, or raw converted text.
   */
  public static function parse(string $text, $help = TRUE, $sanitize = TRUE): string {
    if (!self::isApplicable()) {
      $text = $sanitize ? Xss::filterAdmin($text) : $text;
      return $help ? '<pre>' . $text . '</pre>' : $text;
    }

    if (class_exists('League\CommonMark\CommonMarkConverter')) {
      $converter = new CommonMarkConverter();

      if (method_exists($converter, 'convert')) {
        $text = (string) $converter->convert($text);
      }
      else {
        // Deprecated since 2.2.
        $method = 'convertToHtml';
        if (is_callable([$converter, $method])) {
          $text = (string) $converter->{$method}($text);
        }
      }
    }
    elseif (class_exists('Michelf\MarkdownExtra')) {
      $text = (string) MarkdownExtra::defaultTransform($text);
    }

    // We do not pass it to FilterProcessResult, as this is meant simple.
    return $sanitize ? Xss::filterAdmin($text) : $text;
  }

  /**
   * Checks if we have the needed classes.
   */
  private static function isApplicable(): bool {
    return class_exists('League\CommonMark\CommonMarkConverter')
      || class_exists('Michelf\MarkdownExtra');
  }

}
