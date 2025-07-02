<?php

namespace Drupal\Tests\webform_node\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\webform_node\WebformNodeUninstallValidator
 * @group webform_node
 */
class WebformNodeUninstallValidatorTest extends UnitTestCase {

  /**
   * A mock webform node uninstall validator.
   *
   * @var \Drupal\webform_node\WebformNodeUninstallValidator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $webformNodeUninstallValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->webformNodeUninstallValidator = $this->createPartialMock('Drupal\webform_node\WebformNodeUninstallValidator', ['hasWebformNodes']);
    $this->webformNodeUninstallValidator->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNotWebformNode() {
    $this->webformNodeUninstallValidator->expects($this->never())
      ->method('hasWebformNodes');

    $module = 'not_webform_node';
    $expected = [];
    $reasons = $this->webformNodeUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateEntityQueryWithoutResults() {
    $this->webformNodeUninstallValidator->expects($this->once())
      ->method('hasWebformNodes')
      ->willReturn(FALSE);

    $module = 'webform_node';
    $expected = [];
    $reasons = $this->webformNodeUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateEntityQueryWithResults() {
    $this->webformNodeUninstallValidator->expects($this->once())
      ->method('hasWebformNodes')
      ->willReturn(TRUE);

    $module = 'webform_node';
    $expected = ['To uninstall Webform node, delete all content that has the Webform content type.'];
    $reasons = $this->webformNodeUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

}
