<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\DataParserPluginInterface;

/**
 * Test of the data_parser Xml migrate_plus plugin.
 *
 * @group migrate_plus
 */
final class XmlTest extends BaseXml {

  /**
   * {@inheritdoc}
   */
  protected function getParser(): DataParserPluginInterface {
    return $this->pluginManager->createInstance('xml', $this->configuration);
  }

}
