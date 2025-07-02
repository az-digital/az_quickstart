<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\redirect\Plugin\Field\FieldWidget\RedirectSourceWidget;
use Drupal\Tests\UnitTestCase;

/**
 * Redirect source widget should only be applicable on redirect entities.
 *
 * @group redirect
 */
class RedirectSourceWidgetTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'path_alias',
    'redirect',
  ];

  /**
   * Tests the isApplicable method for redirect entities.
   */
  public function testIsApplicableForRedirectEntity(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('redirect');

    $this->assertTrue(RedirectSourceWidget::isApplicable($field_definition));
  }

  /**
   * Tests the isApplicable method for non-redirect entities.
   */
  public function testIsApplicableForNonRedirectEntity(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');

    $this->assertFalse(RedirectSourceWidget::isApplicable($field_definition));
  }

}
