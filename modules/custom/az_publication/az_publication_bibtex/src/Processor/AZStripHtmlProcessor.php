<?php

namespace Drupal\az_publication_bibtex\Processor;

use Drupal\Component\Utility\Html;
use RenanBr\BibTexParser\Processor\TagCoverageTrait;

/**
 * Processor to strip html from a bibtex entry.
 */
class AZStripHtmlProcessor {
  use TagCoverageTrait;

  /**
   * Create a new AZLatexProcessor.
   */
  public function __construct() {
  }

  /**
   * @return array
   *   The associative citation fields.
   */
  public function __invoke(array $entry) {
    $covered = $this->getCoveredTags(array_keys($entry));
    foreach ($covered as $tag) {
      // Translate string.
      if (is_string($entry[$tag])) {
        $entry[$tag] = $this->stripHtml($entry[$tag]);
        continue;
      }

      // Translate array.
      if (is_array($entry[$tag])) {
        array_walk_recursive($entry[$tag], function (&$text) {
          if (is_string($text)) {
            $text = $this->stripHtml($text);
          }
        });
      }
    }

    return $entry;
  }

  /**
   * Removes html and entities.
   *
   * @param string $text
   *   Value to remove html from.
   *
   * @return string
   *   The string with html removed.
   */
  private function stripHtml($text) {
    $text = Html::decodeEntities($text);
    $text = strip_tags($text);
    return $text;
  }

}
