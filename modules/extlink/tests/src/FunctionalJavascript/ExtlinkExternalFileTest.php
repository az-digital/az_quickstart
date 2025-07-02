<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

/**
 * Testing the basic functionality of External Links when external file enabled.
 *
 * @group Extlink
 */
class ExtlinkExternalFileTest extends ExtlinkTest {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Enable the use of the external JS file.
    $config = $this->container
      ->get('config.factory')
      ->getEditable('extlink.settings');
    $config->set('extlink_use_external_js_file', TRUE)
      ->save();
  }

}
