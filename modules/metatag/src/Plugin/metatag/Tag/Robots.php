<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The basic "Robots" meta tag.
 *
 * @MetatagTag(
 *   id = "robots",
 *   label = @Translation("Robots"),
 *   description = @Translation("Provides search engines with specific directions for what to do when this page is indexed."),
 *   name = "robots",
 *   group = "advanced",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Robots extends MetaNameBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function setValue($value): void {
    if (is_array($value)) {
      $value = array_filter($value);
      $value = implode(', ', array_keys($value));
    }
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    $form = [];
    $form['robots'] = [
      '#type' => 'checkboxes',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => $this->formValues(),
      'index' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[noindex]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][noindex]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      'noindex' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[index]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][index]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      'follow' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[nofollow]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][nofollow]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      'nofollow' => [
        '#states' => [
          'disabled' => [
            [':input[name="robots[follow]"]' => ['checked' => TRUE]],
            'or',
            [':input[name*="[robots][follow]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
      '#required' => $element['#required'] ?? FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    $form['robots-keyed'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['robots-keyed']['max-snippet'] = [
      '#type' => 'number',
      '#min' => -1,
      '#title' => $this->t('Max Snippet'),
      '#description' => $this->t('Use a number character as a textual snippet for this search result. "0" equals "nosnippet". "-1" will let the search engine decide the most effective length.'),
    ];

    $form['robots-keyed']['max-video-preview'] = [
      '#type' => 'number',
      '#min' => -1,
      '#title' => $this->t('Max Video Preview'),
      '#description' => $this->t('Use a maximum of number seconds as a video snippet for videos on this page in search results. "0" will use a static a image. "-1" means there is no limit.'),
    ];

    $form['robots-keyed']['max-image-preview'] = [
      '#type' => 'select',
      '#title' => $this->t('Max Image Preview'),
      '#description' => $this->t('Set the maximum size of an image preview for this page in a search results.'),
      '#options' => [
        'none' => $this->t('None - no image preview is to be shown.'),
        'standard' => $this->t('Standard - a default image preview may be shown.'),
        'large' => $this->t('Large - a larger image preview, up to the width of the viewport, may be shown.'),
      ],
      '#empty_option' => $this->t('Select'),
    ];

    $form['robots-keyed']['unavailable_after'] = [
      '#type' => 'date',
      '#title' => $this->t('Unavailable after date'),
      '#description' => $this->t('Do not show this page in search results after the specified date'),
    ];

    // Prepare the default value as it is stored as a string.
    if (!empty($this->value)) {
      $default_value = explode(', ', $this->value);
      $form['robots']['#default_value'] = $default_value;
      foreach ($default_value as $value) {
        $key_value = explode(':', $value);
        if (!empty($key_value[1]) && isset($form['robots-keyed'][$key_value[0]])) {
          $form['robots-keyed'][$key_value[0]]['#default_value'] = $key_value[1];
        }
      }
    }

    return $form;
  }

  /**
   * The list of select values.
   *
   * @return array
   *   A list of values available for this select tag.
   */
  protected function formValues(): array {
    return [
      'index' => $this->t('index - Allow search engines to index this page (assumed).'),
      'follow' => $this->t('follow - Allow search engines to follow links on this page (assumed).'),
      'noindex' => $this->t('noindex - Prevents search engines from indexing this page.'),
      'nofollow' => $this->t('nofollow - Prevents search engines from following links on this page.'),
      'noarchive' => $this->t('noarchive - Prevents cached copies of this page from appearing in search results.'),
      'nosnippet' => $this->t('nosnippet - Prevents descriptions from appearing in search results, and prevents page caching.'),
      'noimageindex' => $this->t('noimageindex - Prevent search engines from indexing images on this page.'),
      'notranslate' => $this->t('notranslate - Prevent search engines from offering to translate this page in search results.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    $paths = [];
    foreach ($this->formValues() as $key => $value) {
      $paths[] = "//input[@name='robots[{$key}]' and @type='checkbox']";
    }
    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormData(): array {
    return [
      // @todo Expand this?
      'robots[index]' => TRUE,
      'robots[nofollow]' => TRUE,
      // 'robots[follow]',
      'robots-keyed[max-snippet]' => 10,
      'robots-keyed[max-video-preview]' => 20,
      'robots-keyed[max-image-preview]' => 'none',
      'robots-keyed[unavailable_after]' => '2022-12-31',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    // This tag outputs its multiple possible values as a comma-separated string
    // so just use the standard test output once the values are joined together
    // as a single string.
    $new_values = [];
    foreach ($values as $form_field_name => $value) {
      // The strings are stored as e.g. "robots[index]", "robots[nofollow]",
      // etc. So in order to get the value names we need to remove the first
      // part and the wrapping brackets.
      if (strpos($form_field_name, 'robots[') !== FALSE) {
        $new_values[] = substr($form_field_name, 7, -1);
      }
      // Newer strings are stored with the form name "robots-keyed[something]",
      // so those need the substring to be extracted and then.
      elseif (strpos($form_field_name, 'robots-keyed[') !== FALSE) {
        $new_values[] = substr($form_field_name, 13, -1) . ':' . $value;
      }
    }
    return parent::getTestOutputValuesXpath([implode(', ', $new_values)]);
  }

  /**
   * {@inheritdoc}
   */
  public static function validateTag(array &$element, FormStateInterface $form_state): void {
    $robots_combined_value = $form_state->getValue($element['#parents']);
    $robots_root_parents = array_slice($element['#parents'], 0, -1);
    $robots_keyed = $form_state->getValue(array_merge($robots_root_parents, ['robots-keyed']));
    if (is_array($robots_keyed)) {
      foreach ($robots_keyed as $key => $value) {
        if (!empty($value)) {
          $option = "$key:$value";
          $robots_combined_value[$option] = $option;
        }
      }
      $form_state->setValue($robots_root_parents ?: $element['#parents'], $robots_combined_value);
    }
  }

}
