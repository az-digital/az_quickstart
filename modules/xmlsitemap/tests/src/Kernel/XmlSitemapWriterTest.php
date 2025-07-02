<?php

namespace Drupal\Tests\xmlsitemap\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\xmlsitemap\Entity\XmlSitemap;
use Drupal\xmlsitemap\XmlSitemapWriter;

/**
 * Tests \Drupal\xmlsitemap\XmlSitemapWriter.
 *
 * @group xmlsitemap
 *
 * @coversDefaultClass \Drupal\xmlsitemap\XmlSitemapWriter
 */
class XmlSitemapWriterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->sitemap = XmlSitemap::create();
  }

  /**
   * Test that an invalid page cannot be passed to the constructor.
   *
   * @covers ::__construct
   */
  public function testInvalidPage() {
    $this->expectExceptionObject(new \InvalidArgumentException("Invalid XML Sitemap page invalid."));
    new XmlSitemapWriter($this->sitemap, 'invalid');
  }

  /**
   * Tests the writeElement() method.
   *
   * @covers ::writeElement
   * @covers ::formatXmlElements
   * @covers ::toString
   */
  public function testWriteElement() {
    $writer = new XmlSitemapWriter($this->sitemap, '1');
    $writer->openMemory();
    $writer->writeElement('url', [
      'item1' => 'value1',
      [
        'key' => 'item2',
        'value' => '<value2>',
      ],
      [
        'key' => 'item3',
        'value' => [
          'subkey' => 'subvalue',
        ],
        'attributes' => [
          'attr1key' => 'attr1value',
          'attr2key' => '<attr2value>',
        ],
      ],
    ]);
    $output = $writer->outputMemory();
    $expected = '<url><item1>value1</item1><item2>&lt;value2&gt;</item2><item3 attr1key="attr1value" attr2key="&lt;attr2value&gt;"><subkey>subvalue</subkey></item3></url>' . PHP_EOL;
    $this->assertEquals($expected, $output);

    $writer->writeElement('url', [
      'loc' => 'https://www.example.com/test',
      'image:image' => [
        'image:loc' => Url::fromUri('https://www.example.com/test.jpg'),
        'image:title' => new TranslatableMarkup('The image title'),
        'image:caption' => "'The image & its \"caption.\"'",
      ],
    ]);
    $output = $writer->outputMemory();
    $expected = '<url><loc>https://www.example.com/test</loc><image:image><image:loc>https://www.example.com/test.jpg</image:loc><image:title>The image title</image:title><image:caption>&#039;The image &amp; its &quot;caption.&quot;&#039;</image:caption></image:image></url>' . PHP_EOL;
    $this->assertSame($expected, $output);
  }

}
