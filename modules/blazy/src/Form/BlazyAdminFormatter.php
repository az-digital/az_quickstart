<?php

namespace Drupal\blazy\Form;

use Drupal\blazy\BlazyDefault;

/**
 * Provides admin form specific to Blazy admin formatter.
 */
class BlazyAdminFormatter extends BlazyAdminFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function buildSettingsForm(array &$form, array $definition): void {
    parent::buildSettingsForm($form, $definition);

    $scopes = $this->toScopes($definition);

    $this->openingForm($form, $definition);
    $this->basicImageForm($form, $definition);

    if ($scopes->form('grid') && !isset($form['grid'])) {
      // Blazy doesn't need complex grid with multiple groups.
      if ($scopes->get('namespace') == 'blazy') {
        $scopes->set('is.grid_simple', TRUE);
      }

      $this->gridForm($form, $definition);
    }

    if ($scopes->form('fieldable')) {
      $this->fieldableForm($form, $definition);
    }

    $this->closingForm($form, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function openingForm(array &$form, array &$definition): void {
    parent::openingForm($form, $definition);

    $scopes = $this->toScopes($definition);
    $namespace = static::$namespace;
    $descriptions = $this->formatterDescriptions($scopes);

    if ($scopes->is('vanilla')) {
      $classes = ['full', 'tooltip-wide'];
      $form['vanilla'] = [
        '#type'        => 'checkbox',
        '#title'       => $this->t('Vanilla @namespace', ['@namespace' => $namespace]),
        '#description' => $descriptions['vanilla'],
        '#weight'      => -113,
        '#enforced'    => TRUE,
        '#attributes'  => ['class' => ['form-checkbox--vanilla']],
        '#wrapper_attributes' => $this->getTooltipClasses($classes),
      ];
    }

    if ($optionsets = $scopes->data('optionsets')) {
      $form['optionset'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Optionset'),
        '#options'     => $optionsets,
        '#enforced'    => TRUE,
        '#description' => $descriptions['optionset'],
        '#weight'      => -110,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fieldableForm(array &$form, array $definition): void {
    parent::fieldableForm($form, $definition);

    $scopes = $this->toScopes($definition);
    $data = $scopes->get('data', []);
    $base_image = $this->baseForm($definition)['image'] ?? [];
    $descriptions = $this->formatterDescriptions($scopes);

    if (isset($data['images']) && $base_image) {
      $form['image'] = $base_image;
    }

    if (isset($data['thumbnails'])) {
      $form['thumbnail'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail image'),
        '#options'     => $this->toOptions($data['thumbnails']),
        '#description' => $descriptions['thumbnail'],
      ];
    }

    if (isset($data['overlays'])) {
      $form['overlay'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Overlay media'),
        '#options'     => $this->toOptions($data['overlays']),
        '#description' => $descriptions['overlay'],
      ];
    }

    if (isset($data['titles'])) {
      // Ensures to not override Views content/ entity title, just formatters.
      if ($scopes->data('images') && !$scopes->is('_views')) {
        $scopes->set('data.titles.title', $this->t('Image Title'));
      }

      $form['title'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Title'),
        '#options'     => $this->toOptions($scopes->data('titles')),
        '#description' => $descriptions['title'],
      ];
    }

    $this->linkForm($form, $definition, $scopes);

    // Allows empty options to raise awareness of this option.
    if (isset($data['classes'])) {
      $form['class'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Item class'),
        '#options'     => $this->toOptions($data['classes']),
        '#description' => $descriptions['class'],
      ];
    }

    if (isset($form['caption'])) {
      $form['caption']['#description'] = $descriptions['caption'];
    }

    $weight = -90;
    foreach (BlazyDefault::viewsSettings() as $key) {
      if (isset($form[$key]) && !isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = --$weight;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function closingForm(array &$form, array $definition): void {
    $scopes = $this->toScopes($definition);
    $descriptions = $this->formatterDescriptions($scopes);

    if ($scopes->is('caches')) {
      $form['cache'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Cache'),
        '#options'     => $this->getCacheOptions(),
        '#weight'      => 98,
        '#enforced'    => TRUE,
        '#description' => $descriptions['cache'],
      ];
    }

    parent::closingForm($form, $definition);
  }

  /**
   * Returns formatter descriptions.
   */
  protected function formatterDescriptions($scopes): array {
    $namespace = $scopes->get('namespace', 'blazy');

    $cache = $this->t('Ditch all the logic to cached bare HTML. <ol><li><strong>Permanent</strong>: cached contents will persist (be displayed) till the next cron runs.</li><li><strong>Any number</strong>: expired by the selected expiration time, and fresh contents are fetched till the next cache rebuilt.</li></ol>A working cron job is required to clear stale cache. At any rate, cached contents will be refreshed regardless of the expiration time after the cron hits. <br />Leave it empty to disable caching.<br /><strong>Warning!</strong> Be sure no useless/ sensitive data such as Edit links as they are rendered as is regardless permissions. No permissions are changed, just ugly. Only enable it when all is done, otherwise cached options will be displayed while changing them.');
    $caption = $this->t('Enable any of the following fields as captions. These fields are treated and wrapped as captions.');
    $overlay = $this->t('Overlay is displayed over the main stage. Can be plain image, sliders, etc.');

    if ($scopes->is('_views')) {
      $cache .= ' ' . $this->t('Also disable Views cache (<strong>Advanced &gt; Caching</strong>) temporarily _only if trouble to see updated settings.');
      $overlay .= ' ' . $this->t('Be sure to CHECK "Use field template" under its formatter if using Slick field formatter.');
    }
    else {
      $caption .= $this->t('Be sure to make them visible at their relevant Manage display if View Mode option is provided.');
    }

    return [
      'cache' => $cache,
      'caption' => $caption,
      'class' => $this->t('If provided, individual item will have this class, e.g.: to have different background with transparent images. Be sure its formatter is Key or Label. Accepted field types: list text, string (e.g.: node title), term/entity reference label.'),
      'optionset' => $this->t('Enable the optionset UI module to manage the optionsets.'),
      'overlay' => $overlay,
      'thumbnail' => $this->t('Leave empty to not use thumbnail/ pager.'),
      'title' => $this->t('<strong>Supported types</strong>: basic Image title, and fields like a dedicated field Title, Link, etc. If an entity, be sure its formatter is strings like ID or Label. As opposed to <strong>Caption fields</strong>, it will be positioned and wrapped with H2 (overriden by <code>hook_blazy_item_alter() with blazies.item.title_tag</code>) and a dedicated class: <strong>@class</strong>.', [
        '@class' => $namespace == 'blazy' ? 'blazy__caption--title' : $namespace . '__title',
      ]),
      'vanilla' => $this->t('<strong>Check</strong>:<ul><li>To render individual item as is as without extra logic.</li><li>To disable 99% @module features, and most of the mentioned options here, such as layouts, et al.</li><li>When the @module features can not satisfy the need.</li><li>Things may be broken! You are on your own.</li></ul><strong>Uncheck</strong>:<ul><li>To get consistent markups and its advanced features -- relevant for the provided options as @module needs to know what to style/work with.</li></ul>', ['@module' => $namespace]),
    ];
  }

}
