<?php

namespace Drupal\blazy\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\internals\Internals;

/**
 * Provides very few common sanitization wrapper methods.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Please use the public method instead.
 *
 * @todo checks for core equivalents, Xss::filter() is causing 404, etc.
 * @see https://www.drupal.org/project/drupal/issues/3109650
 * @see https://www.drupal.org/node/2489544
 */
class Sanitize {

  /**
   * All attributes that may contain URIs, copied from core Html.
   *
   * @var string[]
   *
   * - The attributes 'code' and 'codebase' are omitted, because they only exist
   *   for the <applet> tag. The time of Java applets has passed.
   * - The attribute 'icon' is omitted, because no browser implements the
   *   <command> tag anymore.
   *   See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/command.
   * - The 'manifest' attribute is omitted because it only exists for the <html>
   *   tag. That tag only makes sense in an HTML-served-as-HTML context, in
   *   which case relative URLs are guaranteed to work.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes
   * @see https://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value
   */
  protected static $uriAttributes = [
    'about',
    'action',
    'cite',
    'data',
    'formaction',
    'href',
    'poster',
    'src',
    'srcset',
  ];

  /**
   * Returns the sanitized attributes for user-defined (UGC Blazy Filter).
   *
   * This is just scratching the surface. Only concerns about UCG. Not AGC, TCG
   * nor DGC, who can always hack their own websites for fun, or profit.
   * When IMG and IFRAME are allowed for untrusted users, trojan horses are
   * welcome. Hence sanitize attributes relevant for BlazyFilter. The rest
   * should be taken care of by HTML filters before/ after Blazy. Blazy is not
   * responsible for iframes/ images put into text filters, nor managing them.
   *
   * @param array $attributes
   *   The given attributes to sanitize.
   * @param bool $escaped
   *   Sets to FALSE to avoid double escapes, for further processing.
   * @param bool $lowercase
   *   Sets to TRUE to have the values lowercased, such as tags, titles, etc.
   *   This option doesn't respect space-delimited string value, use array.
   *
   * @return array
   *   The sanitized $attributes suitable for UGC, such as Blazy filter.
   */
  public static function attribute(array $attributes, $escaped = TRUE, $lowercase = FALSE): array {
    $list = static::$uriAttributes;
    $output = [];

    if (empty($attributes)) {
      return $output;
    }

    foreach ($attributes as $key => $value) {
      // Since Blazy is lazyloading known URLs, sanitize attributes which
      // make no sense to stick around within IMG or IFRAME tags.
      // The most obvious (HREF and SRC) are done downstream, not upstream.
      // PHP8.0.0 numeric with whitespace ("42 ") will now return true.
      $kid = FALSE;
      $key = trim($key);

      // @todo use is_int() instead after another check.
      if (!is_numeric($key)) {
        $key = Html::escape($key);
        $check = strtolower($key);
        $kid = mb_substr($check, 0, 2) === 'on' || in_array($check, $list);
        $key = $kid ? 'data-' . $key : $key;
      }

      // Only key class is known as array.
      if (is_array($value)) {
        // Respects array item containing space delimited classes: aaa bbb ccc.
        if ($value) {
          $value = implode(' ', $value);
          if ($lowercase) {
            $value = mb_strtolower($value);
          }
          $value = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', explode(' ', $value));
        }

        $output[$key] = $value;
      }
      else {
        // Makes abused IMG title/ alt HTML usable for captions and attributes.
        if ($value) {
          $value = strip_tags($value);
          if ($lowercase) {
            $value = mb_strtolower($value);
          }

          $kid = $kid || self::kid($value);
          $escaped_value = $escaped ? Html::escape($value) : $value;
          $clean = $kid || $lowercase || in_array($key, ['class', 'id']);

          $value = $clean ? Html::cleanCssIdentifier($value) : $escaped_value;
        }

        $output[$key] = $value;
      }
    }
    return $output;
  }

  /**
   * Returns the supported caption to avoid broken HTML against containers.
   *
   * @param string $input
   *   The given string input.
   * @param array $options
   *   The options relevant to common caption container HTML tags.
   *
   * @return string
   *   The relatively non-broken $input.
   */
  public static function caption($input, array $options = []): string {
    $admin = $options['admin'] ?? FALSE;
    $check = $input;
    if (!$check) {
      return '';
    }

    /*
    // @todo recheck in case a breaking change.
    $containers = $options['containers'] ?? ['h2', 'p', 'div'];
    // Image alt and title might be abused, check them:
    preg_match("/<[^<]+>/", $check, $matches);

    if ($match = $matches[0] ?? NULL) {
    $match = strtolower($match);
    $match = array_map('trim', explode(' ', $match));
    $match = str_replace(['<', '>'], '', $match[0]);

    // To avoid broken HTML if anything match containers.
    // Few were preserved for blazy and its sub-modules caption containers.
    if (in_array($match, $containers)) {
    $check = strip_tags($check);
    }
    }
     */
    $tags = array_merge(BlazyDefault::TAGS, Xss::getHtmlTagList());
    return $admin ? Xss::filterAdmin($check) : Xss::filter($check, $tags);
  }

  /**
   * Returns all available attributes which may contain URI.
   */
  public static function getUriAttributes(): array {
    return static::$uriAttributes;
  }

  /**
   * Returns the minimally sanitized input for UGC.
   *
   * @param array|string $input
   *   The given input to sanitize.
   * @param string $name
   *   The given input name, or key, to check for protocols.
   * @param array $options
   *   The options: admin, paths, striptags, tags.
   *
   * @return array|string
   *   The relatively sanitized $input suitable for UGC.
   */
  public static function input($input, $name, array $options) {
    $admin = $options['admin'] ?? FALSE;
    $paths = $options['paths'] ?? [];
    $striptags = $options['striptags'] ?? TRUE;
    $protocol = $paths && in_array($name, $paths);

    // PHP8.0.0 allows nullable tags. PHP7.4.0 accepts array.
    // The minimum D8.8 is PHP7.4, not recommended.
    // Everything learns, even a widely used language.
    // See https://www.php.net/manual/en/function.strip-tags.php
    // See https://www.drupal.org/node/2891690
    // When you see sumthing stupid like below, you know why.
    $tags = ($options['tags'] ?? NULL) ?: [];
    $xsstags = $tags ?: NULL;
    $value = $input;

    if (is_string($value)) {
      if ($striptags) {
        $value = strip_tags($value, $tags);
      }
      if ($protocol) {
        $value = UrlHelper::filterBadProtocol($value);
      }
      $value = $admin ? Xss::filterAdmin($value) : Xss::filter($value, $xsstags);
    }
    elseif (is_array($value)) {
      if ($striptags) {
        $value = array_map(function ($val) use ($tags) {
          return $val && is_string($val) ? strip_tags($val, $tags) : $val;
        }, $value);
      }

      if ($protocol) {
        $value = array_map(function ($val) {
          if ($val && is_string($val)) {
            return UrlHelper::filterBadProtocol($val);
          }
          return $val;
        }, $value);
      }

      $value = array_map(function ($val) use ($xsstags, $admin) {
        if ($val && is_string($val)) {
          return $admin ? Xss::filterAdmin($val) : Xss::filter($val, $xsstags);
        }
        return $val;
      }, $value);
    }
    return $value;
  }

  /**
   * Returns the media input URL relevant for UGC.
   *
   * @param string $input
   *   The given url.
   *
   * @return string
   *   The sanitized input url.
   */
  public static function inputUrl($input): ?string {
    // @todo move it out of here at 3.x:
    if ($input = Internals::youtube($input)) {
      $input = self::url($input);
    }
    return $input;
  }

  /**
   * Returns the unstripped content after being stripped.
   *
   * Xss::filter() stripped a few useful and assumed safe attributes and its
   * values. This method corrects very few known safe ones while still keeping
   * safety in mind.
   *
   * @param string $content
   *   The given string content.
   * @param array $options
   *   The options.
   *
   * @return string
   *   The content after corrections.
   *
   * @see https://www.drupal.org/project/drupal/issues/3109650
   * @see https://learn.microsoft.com/en-us/previous-versions//cc848897(v=vs.85)?redirectedfrom=MSDN
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img
   */
  public static function unstrip($content, array $options): string {
    $prestyle = $options['prestyle'] ?? '';
    $style = $options['style'] ?? '';

    // @todo remove when local videos are generated dynamically like remote.
    if (Blazy::has($content, 'src="blank"')) {
      $content = str_replace('src="blank"', 'src="about:blank"', $content);
    }

    // Fixed for 404 images when data URI is enabled via UI, or trusted.
    // @todo recheck if data:image is tweakable, a trojan carrier, based on some
    // limited info, browsers prevent embedded scripts from being executable.
    if (Blazy::has($content, 'src="image/')) {
      $data_uri = Blazy::has($content, 'base64')
        || Blazy::has($content, 'svg+xml');

      if ($data_uri) {
        $content = str_replace('src="image/', 'src="data:image/', $content);
      }
    }

    // The $prestyle is the only known barrier to limit scopes.
    if ($style && Blazy::has($content, $prestyle)) {
      $content = str_replace($prestyle, $prestyle . ' style="' . $style . '"', $content);
    }

    return $content;
  }

  /**
   * Returns the required URL relevant for UGC.
   *
   * The image itself can be a trojan horse, this is scratching the surface.
   * Blazy is not managing, nor uploading images. It just works with them.
   *
   * @param string $url
   *   The given url.
   * @param bool $use_data_uri
   *   Whether to trust data URI.
   *
   * @return string
   *   The required url.
   *
   * @todo re-check to completely remove data URI option.
   */
  public static function url($url, $use_data_uri = FALSE): string {
    // This should be enough, unless data:image is tweakable.
    $allow = Blazy::isDataUri($url) && $use_data_uri;

    // @todo remove if data:image is known untweakable.
    if (self::kid($url)) {
      $allow = FALSE;
    }
    return $allow ? $url : UrlHelper::stripDangerousProtocols($url);
  }

  /**
   * Returns true if it is another scary joke, relevant for UGC.
   *
   * @param string $value
   *   The given value to check for.
   *
   * @return bool
   *   Whether an attempted kidding, or normal input.
   *
   * @see https://en.wikipedia.org/wiki/List_of_XML_and_HTML_character_entity_references
   * @see https://en.wikipedia.org/wiki/ASCII
   */
  public static function kid($value): bool {
    // Should use the proper filter before/after Blazy, not this naive.
    // At least useless when already passed to self::attribute() upstream.
    return Blazy::has($value, 'data:text/html')
      || Blazy::has($value, 'script:');
    // @todo recheck, the last suspects might be innocent, just being cryptic
    // for common attribute values, normally readable. OK to strip since it
    // tests against attribute values, not HTML content after Xss::filter().
    // However useless checks after self::attribute() for now.
    // The Dec is represented with &#.
    // || Blazy::has($value, ';&#')
    // The Hex is represented with &#x0.
    // || Blazy::has($value, '&#x');
  }

}
