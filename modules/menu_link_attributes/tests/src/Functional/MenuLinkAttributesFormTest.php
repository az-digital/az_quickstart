<?php

namespace Drupal\Tests\menu_link_attributes\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the menu_link_attributes UI.
 *
 * @group menu_link_attributes
 */
class MenuLinkAttributesFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_link_content',
    'menu_link_attributes',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests attributes are saved correctly when editing a menu link.
   */
  public function testMenuLinkAttributesForm(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer menu',
      'link to any page',
      'use menu link attributes',
    ]));

    $menu_link = MenuLinkContent::create([
      'title' => 'Menu link test',
      'provider' => 'menu_link_content',
      'menu_name' => 'admin',
      'link' => [
        'uri' => 'internal:/user/login',
        'options' => [
          'attributes' => [
            'class' => ['foo'],
          ],
        ],
      ],
    ]);
    $menu_link->save();

    $this->drupalGet($menu_link->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('attributes[class]', 'foo');

    $this->submitForm([
      'attributes[class]' => 'bar',
    ], 'Save');

    // Attributes should be replaced on save.
    $menuLinkContent = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadUnchanged($menu_link->id());
    $options = $menuLinkContent->getUrlObject()->getOptions();
    $this->assertEquals(['bar'], $options['attributes']['class']);
  }

}
