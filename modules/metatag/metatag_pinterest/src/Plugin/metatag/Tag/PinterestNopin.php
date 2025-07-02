<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Pinterest "nopin" meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest_nopin",
 *   label = @Translation("No pin"),
 *   description = @Translation("Do not pin anything from this page. When selected, this option will take precedence over all options below."),
 *   name = "pinterest",
 *   group = "pinterest",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class PinterestNopin extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    $form = [
      '#type' => 'checkbox',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#default_value' => ($this->value === 'nopin') ?: '',
      '#required' => $element['#required'] ?? FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
      '#return_value' => 'nopin',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    return ["//input[@name='{$this->id}' and @type='checkbox']"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputExistsXpath(): array {
    return ["//" . $this->htmlTag . "[@" . $this->htmlNameAttribute . "='{$this->name}' and @content='nopin']"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    return ["//" . $this->htmlTag . "[@" . $this->htmlNameAttribute . "='{$this->name}' and @content='nopin']"];
  }

}
