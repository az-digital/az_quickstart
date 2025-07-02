<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Common functionality for XML data parsers.
 */
trait XmlTrait {

  /**
   * Registers the iterator's namespaces to a SimpleXMLElement.
   *
   * @param \SimpleXMLElement $xml
   *   The element to apply namespace registrations to.
   */
  protected function registerNamespaces(\SimpleXMLElement $xml): void {
    if (isset($this->configuration['namespaces']) && is_array($this->configuration['namespaces'])) {
      foreach ($this->configuration['namespaces'] as $prefix => $ns) {
        $xml->registerXPathNamespace($prefix, $ns);
      }
    }
  }

  /**
   * Parses a LibXMLError to a error message string.
   *
   * @param \LibXMLError $error
   *   Error thrown by the XML.
   *
   * @return string
   *   Error message
   */
  public static function parseLibXmlError(\LibXMLError $error): TranslatableMarkup {
    $error_code_name = 'Unknown Error';
    switch ($error->level) {
      case LIBXML_ERR_WARNING:
        $error_code_name = t('Warning');
        break;

      case LIBXML_ERR_ERROR:
        $error_code_name = t('Error');
        break;

      case LIBXML_ERR_FATAL:
        $error_code_name = t('Fatal Error');
        break;
    }

    return t(
      "@libxmlerrorcodename @libxmlerrorcode: @libxmlerrormessage\nLine: @libxmlerrorline\nColumn: @libxmlerrorcolumn\nFile: @libxmlerrorfile",
      [
        '@libxmlerrorcodename' => $error_code_name,
        '@libxmlerrorcode' => $error->code,
        '@libxmlerrormessage' => trim((string) $error->message),
        '@libxmlerrorline' => $error->line,
        '@libxmlerrorcolumn' => $error->column,
        '@libxmlerrorfile' => $error->file,
      ]
    );
  }

}
