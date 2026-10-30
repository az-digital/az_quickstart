<?php

namespace Drupal\az_publication\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a filter to convert DOI identifiers to links.
 */
#[Filter(
  id: "az_doi_filter",
  title: new TranslatableMarkup("Convert DOI (Digital Object Identifiers) to links"),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
)]
class AZDOIFilter extends FilterBase {

  /**
   * Temporary storage for HTML comments.
   *
   * @var array<string, string>
   */
  protected array $htmlComments = [];

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Based upon \Drupal\filter\Plugin\Filter\FilterUrl::process().
    // Store the current text in case any of the preg_* functions fail.
    $saved_text = $text;

    // Tags to skip and not recurse into.
    $ignore_tags = 'a|script|style|code|pre';

    $text = is_null($text) ? '' : preg_replace_callback('`<!--(.*?)-->`s', [$this, 'escapeComments'], $text);

    // Split at all tags; ensures that no tags or attributes are processed.
    $chunks = is_null($text) ? [
      '',
    ] : preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Do not attempt to convert links into URLs if preg_split() fails.
    if ($chunks !== FALSE) {

      // PHP ensures that the array consists of alternating delimiters and
      // literals, and begins and ends with a literal (inserting NULL as
      // required). Therefore, the first chunk is always text:
      $chunk_type = 'text';

      // If a tag of $ignore_tags is found, it is stored in $open_tag and only
      // removed when the closing tag is found. Until the closing tag is found,
      // no replacements are made.
      $open_tag = '';
      for ($i = 0; $i < count($chunks); $i++) {
        if ($chunk_type === 'text') {

          // Only process this text if there are no unclosed $ignore_tags.
          if ($open_tag === '') {

            // If there is a match, inject a link into this chunk.
            // Match DOI identifiers.
            $pattern = "/(doi:\s*)?(10\.\d{4,9}\/[-._;()\/:A-Z0-9]*[-_;()\/:A-Z0-9]+)/i";
            $chunks[$i] = preg_replace_callback($pattern, [static::class, 'filterDoi'], $chunks[$i]);
          }

          // Text chunk is done, so next chunk must be a tag.
          $chunk_type = 'tag';
        }
        else {

          // Only process this tag if there are no unclosed $ignore_tags.
          if ($open_tag === '') {

            // Check whether this tag is contained in $ignore_tags.
            if (preg_match("`<({$ignore_tags})(?:\\s|>)`i", $chunks[$i], $matches)) {
              $open_tag = $matches[1];
            }
          }
          else {
            if (preg_match("`<\\/{$open_tag}>`i", $chunks[$i], $matches)) {
              $open_tag = '';
            }
          }

          // Tag chunk is done, so next chunk must be text.
          $chunk_type = 'text';
        }
      }
      $text = implode($chunks);
    }

    // Revert to the original comment contents.
    $text = $text ? preg_replace_callback('`<!--(.*?)-->`', [$this, 'unescapeComments'], $text) : $text;

    // Make sure our regex chunking didn't eat the text due to a broken tag.
    $text = strlen((string) $text) > 0 ? $text : $saved_text;

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('DOI identifiers (e.g. doi:10.1000/xyz123) turn into links to doi.org automatically.');
  }

  /**
   * Escapes the contents of HTML comments.
   *
   * Callback for preg_replace_callback() within self::process().
   *
   * @param array<string> $match
   *   Matches from preg_replace_callback(), where $match[1] is the comment
   *   content.
   *
   * @return string
   *   The comment with its contents replaced by a hash placeholder.
   */
  protected function escapeComments(array $match): string {
    $hash = hash('sha256', $match[1]);
    $this->htmlComments[$hash] = $match[1];
    return "<!-- $hash -->";
  }

  /**
   * Unescapes the contents of HTML comments.
   *
   * Callback for preg_replace_callback() within self::process().
   *
   * @param array<string> $match
   *   Matches from preg_replace_callback(), where $match[1] is the hash
   *   placeholder.
   *
   * @return string
   *   The original comment.
   */
  protected function unescapeComments(array $match): string {
    $hash = trim($match[1]);
    $content = $this->htmlComments[$hash] ?? '';
    return "<!--$content-->";
  }

  /**
   * Task preg_replace_callback for digital object identifers.
   *
   * Escapes and wraps the identifier in an anchor.
   *
   * @param array $match
   *   Preg_replace match.
   *
   * @return string
   *   Resultant wrapped anchor.
   */
  public static function filterDoi($match) {
    // The $i:th parenthesis in the regexp contains the URL.
    $i = 2;
    $full = 0;
    $match[$i] = Html::decodeEntities($match[$i]);
    $caption = Html::escape($match[$full]);
    $match[$i] = Html::escape($match[$i]);
    return '<a href="https://doi.org/' . $match[$i] . '">' . $caption . '</a>';
  }

}
