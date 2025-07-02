<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsLastEntityQueryTrait;

/**
 * Test paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsClientsideButtonsClaroTest extends ParagraphsClientsideButtonsTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function placeDefaultBlocks() {
    // Claro has all blocks by default, prevent them from being placed twice.
  }

}
