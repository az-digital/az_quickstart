<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\internals\Internals;

/**
 * Plugin implementation of the 'Blazy Title' formatter.
 *
 * @FieldFormatter(
 *   id = "blazy_title",
 *   label = @Translation("Blazy Title"),
 *   field_types = {
 *     "text",
 *     "string",
 *   }
 * )
 */
class BlazyTitleFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'delimiter' => '|',
      'tag'       => 'small',
      'break'     => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements    = [];
    $settings    = $this->getSettings();
    $url         = NULL;
    $entity      = $items->getEntity();
    $entity_type = $entity->getEntityType();

    if ($this->getSetting('link_to_entity')
      && !$entity->isNew()
      && $entity_type->hasLinkTemplate('canonical')) {
      $url = $this->getEntityUrl($entity);
    }

    foreach ($items as $item) {
      if ($item instanceof FieldItemInterface) {
        $class    = get_class($item);
        $property = $class::mainPropertyName();

        if ($value = $item->{$property}) {
          $elements[] = Internals::formatTitle($value, $url, $settings);
        }
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['delimiter'] = [
      '#title' => $this->t('Delimiter'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('delimiter'),
      '#description' => $this->t('When provided, the text will be separated by this delimiter. Use comma to have multiple delimiters, e.g.: <br><code>|,:,/,- , —</code>'),
      '#prefix' => '<br />' . $this->t('Blazy Title will format delimited plain text or string as HTML title with a sub-title. Minor CSS is required. <br>Input: <code>Title | Sub-title; Title: Sub-title</code> <br>Output: TITLE <strong>SUB-TITLE</strong>; <strong>TITLE</strong> SUB-TITLE; TITLE <small>SUB-TITLE</small>'),
    ];

    $form['tag'] = [
      '#title' => $this->t('Sub-title tag'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('tag'),
      '#description' => $this->t('The tag for the second/ last split as sub-title. Only one tag can exist: em, small, span, strong, etc.'),
    ];

    $form['break'] = [
      '#title' => $this->t('Add line break'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('break'),
      '#description' => $this->t('Use CSS <code>display:block</code> on the Sub-title tag to have multiple lines for better display, or enable this to add the ugly line break.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Delimiter: <strong>@delimiter</strong> <br />Sub-title tag: <strong>@tag</strong> <br />Linebreak: <strong>@break</strong><br />Link: <strong>@link</strong>', [
      '@delimiter' => $this->getSetting('delimiter'),
      '@tag' => $this->getSetting('tag'),
      '@break' => $this->getSetting('break') ? $this->t('Yes') : $this->t('No'),
      '@link' => $this->getSetting('link_to_entity') ? $this->t('Yes') : $this->t('No'),
    ]);
    return $summary;
  }

}
