<?php

namespace Drupal\blazy\Plugin\Filter;

/**
 * Provides minimal shortcode filter utilities.
 *
 * The idea is rather than full regex works, this converts [BLAH] into <blah>,
 * and uses \DOMDocument from there, including parsing attributes, etc.
 */
class Shortcode {

  /**
   * Returns string between delimiters, or empty if not found.
   */
  public static function getStringBetween($string, $start = '[', $end = ']'): ?string {
    $string = ' ' . $string;
    $ini = mb_strpos($string, $start);

    if ($ini == 0) {
      return '';
    }

    $ini += strlen($start);
    $len = mb_strpos($string, $end, $ini) - $ini;
    return trim(substr($string, $ini, $len) ?: '');
  }

  /**
   * Converts [BLAH] into <blah>.
   */
  public static function parse($string, $container = 'blazy', $item = 'item'): string {
    // Might not be available with self-closing [TAG data="BLAH" /].
    if (stristr($string, "[$item") !== FALSE) {
      $string = self::process($string, $item);

      // @todo remove into self::replace().
      $string = str_replace("<p><$item>", "<$item>", $string);
      $string = str_replace("<p><$item ", "<$item ", $string);
      $string = str_replace("</$item></p>", "</$item>", $string);
    }

    $text = self::process($string, $container);

    // @todo remove into self::replace().
    $text = str_replace("<p><$container>", "<$container>", $text);
    $text = str_replace("<p><$container ", "<$container ", $text);
    $text = str_replace("</$container></p>", "</$container>", $text);
    return $text;
  }

  /**
   * Returns the WP regex pattern.
   */
  protected static function pattern($item) {
    // @todo return '/\\[(\\[?)(' . $item . ')(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*+(?:\\[(?!\\/\\2\\])[^\\[]*+)*+)\\[\\/\\2\\])?)(\\]?)/s';
    return '/\[(\[?)(' . $item . ')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)/is';
  }

  /**
   * Processes the shortcode tags.
   *
   * @todo recheck any reliable regex.
   */
  private static function process($string, $item): string {
    $pattern = static::pattern($item);
    // @todo preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);
    // preg_match_all($pattern, $string, $matches, PREG_UNMATCHED_AS_NULL);
    // return self::replace($string, $item);
    return preg_replace_callback($pattern, function ($matches) use ($item) {
      return self::processCallback($matches, $item);
    }, $string);
  }

  /**
   * Process callback to work with the matches.
   */
  private static function processCallback($matches, $item): string {
    if ($found = $matches[0] ?? '') {
      return self::replace($found, $item);
    }
    return '';
  }

  /**
   * Replaces [BLAH] into <blah>.
   *
   * @todo recheck any reliable regex, currently orders important.
   */
  private static function replace($string, $item): string {
    $patterns = [
      // Opening: [TAG data="BLAH" /]</p>.
      "~\[(/)?$item(.*?)\](<\/p>)~i",
      // Closing: <p>[/TAG].
      "~(<p\>)\[(/)?$item(.*?)\]~i",
      // Self closing: [TAG data="BLAH" /].
      "~\[(/)?$item(.*?)\]~i",
    ];

    $replacements = [
      "<$1$item$2>",
      "<$2$item$3>",
      "<$1$item$2>",
    ];

    return preg_replace($patterns, $replacements, $string) ?: '';
  }

  /**
   * Converts [BLAH] into <blah>.
   *
   * @todo deprecated in 2.17 and is removed from 3.x. Use self::shortcode()
   * instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function unwrap($string, $container = 'blazy', $item = 'item'): string {
    // @todo @trigger_error('unwrap is deprecated in blazy:8.x-2.17 and is removed from blazy:3.0.0. Use self::parse() instead. See https://www.drupal.org/node/3367291', E_USER_DEPRECATED);
    return self::parse($string, $container, $item);
  }

}
