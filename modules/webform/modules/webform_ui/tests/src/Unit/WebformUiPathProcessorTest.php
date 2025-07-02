<?php

namespace Drupal\Tests\webform_ui\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\webform_ui\PathProcessor\WebformUiPathProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\webform_ui\PathProcessor\WebformUiPathProcessor
 * @group webform_ui
 */
class WebformUiPathProcessorTest extends UnitTestCase {

  /**
   * The path process.
   *
   * @var \Drupal\webform_ui\PathProcessor\WebformUiPathProcessor
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->pathProcessor = new WebformUiPathProcessor();
  }

  /**
   * @covers ::processOutbound
   */
  public function testBasicPathsOnly() {
    $path = $this->pathProcessor->processOutbound('/node');
    $this->assertEquals('/node', $path);

    $path = $this->pathProcessor->processOutbound('/admin/structure/webform/manage/contact');
    $this->assertEquals('/admin/structure/webform/manage/contact', $path);
  }

  /**
   * @covers ::processOutbound
   */
  public function testUnmatchedQueryString() {
    $options = [];
    $request = $this->createMock(Request::class);
    $request->method('getQueryString')
      ->willReturn('foo');

    $path = $this->pathProcessor->processOutbound('/admin/structure/webform/manage/contact', $options, $request);
    $this->assertEquals('/admin/structure/webform/manage/contact', $path);
    $this->assertArrayNotHasKey('query', $options);
  }

  /**
   * @covers ::processOutbound
   */
  public function testMatchedQueryString() {
    $options = [];
    $request = $this->createMock(Request::class);
    $request->method('getQueryString')
      ->willReturn('_wrapper_format=drupal_dialog&destination=/admin/structure/webform');

    $path = $this->pathProcessor->processOutbound('/admin/structure/webform/manage/contact', $options, $request);
    $this->assertEquals('/admin/structure/webform/manage/contact', $path);
    $this->assertArrayHasKey('query', $options);
    $this->assertArrayHasKey('destination', $options['query']);
    $this->assertEquals('/admin/structure/webform', $options['query']['destination']);
  }

}
