<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_parser;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginInterface;

/**
 * Test of the data_parser SimpleXml migrate_plus plugin.
 *
 * @group migrate_plus
 */
class SimpleXmlTest extends BaseXml {

  /**
   * Test reading non-standard conforming XML.
   *
   * XML file with lots of different white spaces before the starting tag.
   */
  public function testReadNonStandardXmlWhitespace(): void {
    $url = $this->path . '/tests/data/simple_xml_invalid_multi_whitespace.xml';
    $this->configuration['urls'][0] = $url;
    $this->assertResults($this->expected, $this->getParser());
  }

  /**
   * Test reading non-standard conforming XML .
   *
   * XML file with one empty line before the starting tag.
   */
  public function testReadNonStandardXml2(): void {
    $url = $this->path . '/tests/data/simple_xml_invalid_single_line.xml';
    $this->configuration['urls'][0] = $url;
    $this->assertResults($this->expected, $this->getParser());
  }

  /**
   * Test reading broken XML (missing closing tag).
   */
  public function testReadBrokenXmlMissingTag(): void {
    $url = $this->path . '/tests/data/simple_xml_broken_missing_tag.xml';
    $this->configuration['urls'][0] = $url;
    $this->expectException(MigrateException::class);
    // Newer versions of libxml mark it as an error 76, older ones as 73.
    $this->expectExceptionMessageMatches('/^Fatal Error 7[0-9]/');
    $this->getParser()->next();
  }

  /**
   * Test reading broken XML (tag mismatch).
   */
  public function testReadBrokenXmlTagMismatch(): void {
    $url = $this->path . '/tests/data/simple_xml_broken_tag_mismatch.xml';
    $this->configuration['urls'][0] = $url;

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessageMatches('/^Fatal Error 76/');
    $this->getParser()->next();
  }

  /**
   * Test reading non XML.
   */
  public function testReadNonXml(): void {
    $url = $this->path . '/tests/data/simple_xml_non_xml.xml';
    $this->configuration['urls'][0] = $url;

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessageMatches('/^Fatal Error 46/');
    $this->getParser()->next();
  }

  /**
   * Tests reading non-existing XML.
   */
  public function testReadNonExistingXml(): void {
    $url = $this->path . '/tests/data/xml_non_existing.xml';
    $this->configuration['urls'][0] = $url;

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('file parser plugin: could not retrieve data from');
    $this->getParser()->next();
  }

  /**
   * {@inheritdoc}
   */
  protected function getParser(): DataParserPluginInterface {
    return $this->pluginManager->createInstance('simple_xml', $this->configuration);
  }

}
