<?php

namespace Drupal\Tests\field_group_migrate\Traits;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Trait for testing migrated Field Group settings.
 */
trait FieldGroupMigrationAssertionsTrait {

  /**
   * Tests article node form display's field group settings.
   */
  protected function assertNodeArticleDefaultForm() {
    $form_display_default = EntityFormDisplay::load('node.article.default');
    assert($form_display_default instanceof EntityDisplayInterface);
    $this->assertEquals([
      'group_article' => [
        'children' => ['field_image'],
        'parent_name' => 'group_article_htabs',
        'weight' => 2,
        'label' => 'htab form group',
        'format_settings' => [
          'classes' => '',
          'id' => '',
          'formatter' => 'closed',
          'description' => '',
          'required_fields' => TRUE,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tab',
        'region' => 'content',
      ],
      'group_article_htabs' => [
        'children' => ['group_article'],
        'parent_name' => '',
        'weight' => 1,
        'label' => 'Horizontal tabs',
        'format_settings' => [
          'direction' => 'horizontal',
          'classes' => 'group-article-htabs field-group-htabs',
          'id' => '',
          'width_breakpoint' => 640,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tabs',
        'region' => 'content',
      ],
    ], $form_display_default->getThirdPartySettings('field_group'));
  }

  /**
   * Tests page node form display's field group settings.
   */
  protected function assertNodePageDefaultForm() {
    $form_display_default = EntityFormDisplay::load('node.page.default');
    assert($form_display_default instanceof EntityDisplayInterface);
    $this->assertEquals([
      'group_page' => [
        'children' => ['group_page_tab'],
        'parent_name' => '',
        'weight' => 0,
        'label' => 'Node form group',
        'format_settings' => [
          'direction' => 'horizontal',
          'classes' => '',
          'id' => '',
          'width_breakpoint' => 640,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tabs',
        'region' => 'content',
      ],
      'group_page_tab' => [
        'children' => ['field_text_plain'],
        'parent_name' => 'group_page',
        'weight' => 17,
        'label' => 'Horizontal tab',
        'format_settings' => [
          'classes' => 'group-page-tab field-group-htab',
          'id' => '',
          'formatter' => 'open',
          'description' => '',
          'required_fields' => TRUE,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tab',
        'region' => 'content',
      ],
    ], $form_display_default->getThirdPartySettings('field_group'));
  }

  /**
   * Tests node teaser display's field group settings.
   */
  protected function assertNodeArticleTeaserDisplay() {
    $view_display_default = EntityViewDisplay::load('node.article.teaser');
    assert($view_display_default instanceof EntityDisplayInterface);
    $this->assertEquals([
      'group_article' => [
        'children' => ['field_image'],
        'parent_name' => 'group_article_htabs',
        'weight' => 2,
        'label' => 'htab group',
        'format_settings' => [
          'classes' => 'htab-group',
          'id' => '',
          'formatter' => 'closed',
          'description' => '',
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tab',
        'region' => 'content',
      ],
      'group_article_htabs' => [
        'children' => ['group_article'],
        'parent_name' => '',
        'weight' => 1,
        'label' => 'Horizontal tabs',
        'format_settings' => [
          'classes' => '',
          'id' => '',
          'direction' => 'horizontal',
          'width_breakpoint' => 640,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tabs',
        'region' => 'content',
      ],
    ], $view_display_default->getThirdPartySettings('field_group'));
  }

  /**
   * Tests page default display's field group settings.
   */
  protected function assertNodePageDefaultDisplay() {
    $view_display_default = EntityViewDisplay::load('node.page.default');
    assert($view_display_default instanceof EntityDisplayInterface);
    $this->assertEquals([
      'group_page' => [
        'children' => [],
        'parent_name' => '',
        'weight' => 0,
        'label' => 'Node group',
        'format_settings' => [
          'direction' => 'horizontal',
          'classes' => '',
          'id' => '',
          'width_breakpoint' => 640,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tabs',
        'region' => 'content',
      ],
    ], $view_display_default->getThirdPartySettings('field_group'));
  }

  /**
   * Tests user default display's field group settings.
   */
  protected function assertUserDefaultDisplay() {
    $view_display_default = EntityViewDisplay::load('user.user.default');
    assert($view_display_default instanceof EntityDisplayInterface);
    $this->assertEquals([
      'group_user' => [
        'children' => [
          'group_user_child',
        ],
        'parent_name' => '',
        'weight' => 1,
        'label' => 'User group parent',
        'format_settings' => [
          'element' => 'div',
          'show_label' => FALSE,
          'label_element' => 'h3',
          'label_element_classes' => '',
          'attributes' => '',
          'effect' => 'none',
          'speed' => 'fast',
          'id' => '',
          'classes' => '',
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'html_element',
        'region' => 'content',
      ],
      'group_user_child' => [
        'children' => ['group_user_tab1', 'group_user_tab2'],
        'parent_name' => 'group_user',
        'weight' => 99,
        'label' => 'User group child',
        'format_settings' => [
          'classes' => 'user-group-child',
          'id' => 'group_article_node_article_teaser',
          'direction' => 'vertical',
          'width_breakpoint' => 640,
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tabs',
        'region' => 'content',
      ],
      'group_user_tab1' => [
        'children' => ['field_file'],
        'parent_name' => 'group_user_child',
        'weight' => 99,
        'label' => 'User tab 1',
        'format_settings' => [
          'classes' => 'vtab vtab--open',
          'id' => '',
          'formatter' => 'open',
          'description' => '',
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tab',
        'region' => 'content',
      ],
      'group_user_tab2' => [
        'children' => ['field_integer'],
        'parent_name' => 'group_user_child',
        'weight' => 100,
        'label' => 'User tab 2',
        'format_settings' => [
          'classes' => 'vtab vtab--closed',
          'id' => '',
          'formatter' => 'closed',
          'description' => '',
          'show_empty_fields' => FALSE,
          'label_as_html' => FALSE,
        ],
        'format_type' => 'tab',
        'region' => 'content',
      ],
    ], $view_display_default->getThirdPartySettings('field_group'));
  }

}
