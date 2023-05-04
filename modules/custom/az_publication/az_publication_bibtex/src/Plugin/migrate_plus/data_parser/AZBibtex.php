<?php

namespace Drupal\az_publication_bibtex\Plugin\migrate_plus\data_parser;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Exception\ExceptionInterface;
use RenanBr\BibTexParser\Processor\NamesProcessor;
use Drupal\az_publication_bibtex\Processor\AZDateProcessor;

/**
 * Obtain BibTeX data for migration..
 *
 * @DataParser(
 *   id = "az_bibtex",
 *   title = @Translation("Quickstart BibTex")
 * )
 */
class AZBibtex extends DataParserPluginBase {

  /**
   * @var array
   */
  protected $citations = [];

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url): bool {

    $bibtex = $this->getDataFetcherPlugin()->getResponseContent($url);
    try {
      $listener = new Listener();
      $listener->addProcessor(new NamesProcessor());
      $listener->addProcessor(new AZDateProcessor());
      $parser = new Parser();
      $parser->addListener($listener);
      $parser->parseString($bibtex);
      $citations = $listener->export();
      $citations = array_filter($citations, function ($citation) {
        return (isset($citation['citation-key']) && isset($citation['title']));
      });
      $citations = array_map('self::detex', $citations);
      $this->citations = $citations;
    }
    catch (ExceptionInterface $exception) {
      throw new MigrateException("BibTex could not be parsed.");
    }
    return TRUE;
  }

  /**
   * Removes some TeX markup. TBD determine full requirements of TeX support.
   *
   * @param mixed $value
   *   Value to remove TeX markup from. Supports strings or arrays.
   *
   * @return mixed
   *   The deTeX-ified array or string.
   */
  public static function detex($value) {
    // Recursion for arrays.
    if (is_array($value)) {
      return array_map('self::detex', $value);
    }

    // TBD determine extent of TeX rendering.
    $search = ['\"a', '\"A', '\"o', '\"O', '\"u', '\U"', '\ss', '\`e',
      '\´e', '\url{', '{', '}', '--', '\"', '\'', '`', '\textbackslash',
    ];
    $replace = ['ä', 'Ä', 'ö', 'Ö', 'ü', 'Ü', 'ß', 'è', 'é', '', '', '',
      '—', ' ', ' ', ' ', '\\',
    ];
    $value = str_replace($search, $replace, $value);

    $value = rtrim($value, '}, ');
    return trim($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    $citation = array_shift($this->citations);

    // If we've found the desired element, populate the currentItem and
    // currentId with its data.
    if ($citation !== FALSE && !is_null($citation)) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        if (!empty($citation[$selector])) {
          $this->currentItem[$field_name] = $citation[$selector];
        }
      }
    }
  }

}
