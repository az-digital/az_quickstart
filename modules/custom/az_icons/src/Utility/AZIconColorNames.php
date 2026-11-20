<?php

namespace Drupal\az_icons\Utility;

use Drupal\az_core\Utility\AZBootstrapColorNames;

/**
 * Renames the Arizona Bootstrap color names saved in icons.
 *
 * An icon in formatted text saves its color as JSON in an HTML attribute, for
 * example <drupal-icon data-icon-id="material_symbols:home"
 * data-icon-settings='{"color":"sky"}'>. Arizona Bootstrap 5.3.0 renamed the
 * brand colors, so sky is now rain.
 *
 * @see az_icons_post_update_rename_icon_colors()
 */
final class AZIconColorNames {

  /**
   * The icon packs az_icons provides, which use the Arizona Bootstrap colors.
   */
  const PACK_IDS = ['arizona_icons', 'material_symbols'];

  /**
   * Matches an opening <drupal-icon> tag.
   *
   * A quoted attribute value can contain a >, so the pattern skips over
   * quoted values instead of stopping at the first >. The group is possessive
   * (*+). Without this, a broken tag with no > for thousands of characters
   * makes PCRE run out of stack.
   */
  const ICON_TAG_PATTERN = '/<drupal-icon\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*+>/i';

  /**
   * Matches a data-icon-settings attribute and its quoted value.
   */
  const SETTINGS_ATTRIBUTE_PATTERN = '/(\sdata-icon-settings\s*=\s*)("[^"]*"|\'[^\']*\')/i';

  /**
   * Renames the old color names in every az_icons icon in a piece of HTML.
   *
   * Only the color value changes. The rest of the HTML stays byte for byte as
   * it is. Rationale: parsing the HTML and writing it back out, as
   * AZBootstrapMarkupConverter does, can change other markup too.
   *
   * @param string $html
   *   HTML that can contain <drupal-icon> tags.
   *
   * @return string
   *   The HTML, with each old color name in data-icon-settings replaced by its
   *   new name. For example, "color":"sky" becomes "color":"rain". Icons from
   *   other packs keep their colors: in a pack a site added, sky can be a
   *   valid color. If a regex fails, the HTML is returned as it is, and the
   *   templates still render the old name.
   */
  public static function updateMarkup(string $html): string {
    if (stripos($html, '<drupal-icon') === FALSE) {
      return $html;
    }
    $pack_ids = implode('|', array_map(static fn ($pack_id) => preg_quote($pack_id, '/'), self::PACK_IDS));
    $pack_pattern = '/\sdata-icon-id\s*=\s*["\'](?:' . $pack_ids . '):/i';
    return preg_replace_callback(self::ICON_TAG_PATTERN, static function ($tag) use ($pack_pattern) {
      if (!preg_match($pack_pattern, $tag[0])) {
        return $tag[0];
      }
      return preg_replace_callback(
        self::SETTINGS_ATTRIBUTE_PATTERN,
        static fn ($attribute) => $attribute[1] . self::updateSettings($attribute[2]),
        $tag[0],
      ) ?? $tag[0];
    }, $html) ?? $html;
  }

  /**
   * Renames the old color name in one data-icon-settings value.
   *
   * @param string $value
   *   The attribute value, with its quotes. The JSON quotes inside it are "
   *   when the attribute is in single quotes, and &quot; (or a numeric
   *   character reference, such as &#34;) when it's in double quotes.
   *
   * @return string
   *   The attribute value, with the old color name replaced.
   */
  protected static function updateSettings(string $value): string {
    $new_names = AZBootstrapColorNames::DEPRECATED_COLOR_NAMES;
    $old_names = implode('|', array_map(static fn ($name) => preg_quote($name, '/'), array_keys($new_names)));
    // \1 is the JSON quote, so the color name has to be the whole value.
    // For example, "sky" matches, but "sky-blue" doesn't.
    $pattern = '/("|&quot;|&#0*34;|&#[xX]0*22;)color\1(\s*:\s*)\1(' . $old_names . ')\1/';
    return preg_replace_callback(
      $pattern,
      static fn ($match) => $match[1] . 'color' . $match[1] . $match[2] . $match[1] . $new_names[$match[3]] . $match[1],
      $value,
    ) ?? $value;
  }

}
