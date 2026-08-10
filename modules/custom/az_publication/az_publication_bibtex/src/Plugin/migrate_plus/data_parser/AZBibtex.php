<?php

namespace Drupal\az_publication_bibtex\Plugin\migrate_plus\data_parser;

use Drupal\az_publication_bibtex\Processor\AZDateProcessor;
use Drupal\az_publication_bibtex\Processor\AZLatexProcessor;
use Drupal\az_publication_bibtex\Processor\AZStripHtmlProcessor;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;
use RenanBr\BibTexParser\Exception\ExceptionInterface;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Processor\NamesProcessor;
use RenanBr\BibTexParser\Processor\TagNameCaseProcessor;

/**
 * Obtain BibTeX data for migration.
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
   * Additional arguments for the citation key.
   */
  protected array $suffix;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->suffix = $configuration['citation_key_suffix'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url): bool {

    $bibtex = $this->getDataFetcherPlugin()->getResponseContent($url);
    try {
      $listener = new Listener();
      $listener->addProcessor(new NamesProcessor());
      $listener->addProcessor(new TagNameCaseProcessor(CASE_LOWER));
      $listener->addProcessor(new AZDateProcessor());
      $listener->addProcessor(new AZLatexProcessor());
      // Create processor to strip html, skip only metadata fields and abstract.
      $html = new AZStripHtmlProcessor();
      $html->setTagCoverage(['_original', '_type', 'abstract'], 'blacklist');
      $listener->addProcessor($html);
      $parser = new Parser();
      $parser->addListener($listener);
      $parser->parseString($bibtex);
      $citations = $listener->export();
      $citations = array_filter($citations, function ($citation) {
        return (isset($citation['citation-key']) && isset($citation['title']));
      });
      // Concat configured suffixes onto the citation key.
      // @todo make into a processor.
      foreach ($citations as &$citation) {
        foreach ($this->suffix as $s) {
          if (!empty($citation[$s])) {
            $citation['citation-key'] .= '_' . (string) $citation[$s];
          }
        }
      }
      $this->citations = $citations;
    }
    catch (ExceptionInterface $exception) {
      throw new MigrateException("BibTex could not be parsed.");
    }
    return TRUE;
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
