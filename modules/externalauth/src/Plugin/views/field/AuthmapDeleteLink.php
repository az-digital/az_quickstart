<?php

namespace Drupal\externalauth\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present a link to delete an authmap entry.
 *
 * Depends on "additional fields = [uid, provider]" being defined in views data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("authmap_link_delete")
 */
class AuthmapDeleteLink extends LinkBase {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->redirectDestination = $container->get('redirect.destination');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do not call $this->getEntityTranslationRenderer(), as parent does, which
    // breaks because there's no entity type for this table. (Assume we never
    // need to add extra tables/fields in order to translate this link. As an
    // aside: this could be made into a Core patch + less ugly, but ideally
    // that would need extra work to
    // - move all entity related code out of LinkBase or make it optional;
    // - move all non entity related code from EntityLink into LinkBase.
    // That would make this plugin much smaller.)
    //
    // Set $this->tableAlias; addAdditionalFields() depends on this.
    $this->ensureMyTable();
    // Add 'additional fields' (from views data definition) to the query and
    // record their aliases in $this->aliases. This likely means that those
    // fields are double-added to the query, but otherwise we'd have to
    // hardcode assumptions about other Views code (for deriving/guessing the
    // already-existing fields' aliases).
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row): string {
    // From EntityLink:
    if ($this->options['output_url_as_text']) {
      return $this->getUrlInfo($row) ? $this->getUrlInfo($row)->toString() : '';
    }
    // From LinkBase, minus addLangCode() which needs an entity.
    if ($this->getUrlInfo($row)) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['url'] = $this->getUrlInfo($row);
    }
    $text = !empty($this->options['text']) ? $this->sanitizeValue($this->options['text']) : $this->getDefaultLabel();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row): ?Url {
    $provider_alias = $this->aliases['provider'];
    $uid_alias = $this->aliases['uid'];
    return empty($row->$provider_alias) || empty($row->$uid_alias) ? NULL
      : Url::fromRoute('externalauth.authmap_delete_form', [
        'provider' => $row->$provider_alias,
        'uid' => $row->$uid_alias,
      ],
      ['query' => $this->redirectDestination->getAsArray()]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel(): TranslatableMarkup {
    return $this->t('delete');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    // Copy from EntityLinkBase. Maybe unnecessary, but harmless.
    $options = parent::defineOptions();
    $options['output_url_as_text'] = ['default' => FALSE];
    $options['absolute'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Copy from EntityLinkBase. Maybe unnecessary, but harmless.
    $form['output_url_as_text'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output the URL as text'),
      '#default_value' => $this->options['output_url_as_text'],
    ];
    $form['absolute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use absolute link (begins with "http://")'),
      '#default_value' => $this->options['absolute'],
      '#description' => $this->t('Enable this option to output an absolute link. Required if you want to use the path as a link destination.'),
    ];
    parent::buildOptionsForm($form, $form_state);
    // Only show the 'text' field if we don't want to output the raw URL.
    $form['text']['#states']['visible'][':input[name="options[output_url_as_text]"]'] = ['checked' => FALSE];
  }

}
