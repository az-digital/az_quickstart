<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Plugin\Field\FieldFormatter;

use Drupal\az_form_embed\EditingContext;
use Drupal\az_form_embed\FormEmbedVendorManager;
use Drupal\az_form_embed\Plugin\media\Source\FormEmbed;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a Form embed media item's form.
 *
 * This runs when a page shows a Form embed, for example one placed with
 * CKEditor in a Text paragraph. It renders the Form embed component. That
 * component's JavaScript, with the vendor's handler, loads the vendor's
 * script, which fetches the form. Canvas renders the same component, without
 * this formatter.
 *
 * While someone is editing, and in the Media Library, it renders the Form
 * embed placeholder instead. If no turned-on vendor accepts the stored link,
 * people who can administer media see a notice, and everyone else sees
 * nothing.
 */
#[FieldFormatter(
  id: 'az_form_embed',
  label: new TranslatableMarkup('Form embed'),
  description: new TranslatableMarkup('Renders the embedded form, or a placeholder while editing.'),
  field_types: [
    'string',
  ],
)]
class FormEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a FormEmbedFormatter.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field this formatter renders.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\az_form_embed\FormEmbedVendorManager $vendorManager
   *   Finds the vendor that accepts a link.
   * @param \Drupal\az_form_embed\EditingContext $editingContext
   *   Tells us whether an editor is working on this page.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user, for deciding who sees the refused-link notice.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logs the media id and the reason whenever we refuse a link.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected FormEmbedVendorManager $vendorManager,
    protected EditingContext $editingContext,
    protected AccountInterface $currentUser,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.az_form_embed_vendor'),
      $container->get('az_form_embed.editing_context'),
      $container->get('current_user'),
      $container->get('logger.factory')->get('az_form_embed'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Drupal only offers this formatter for the link field of a media type that
   * uses the Form embed source.
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $bundle = $field_definition->getTargetBundle();
    if ($field_definition->getTargetEntityTypeId() !== 'media' || $bundle === NULL) {
      return FALSE;
    }
    $media_type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle);
    return $media_type !== NULL && $media_type->getSource() instanceof FormEmbed;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media = $items->getEntity();
    $label = $media instanceof MediaInterface ? (string) $media->label() : '';
    // Also use the placeholder in the Media Library's own view mode, on any
    // route. Rationale: Canvas opens the Media Library from its own form
    // route, which isn't in EditingContext's list. A live form there loads
    // the vendor's scripts inside the dialog, and with Slate's loaded, the
    // dialog's backdrop ends up on top of the dialog, so no click reaches it.
    $in_media_library = $this->viewMode === 'media_library';
    $editing = $in_media_library || $this->editingContext->isEditing();
    $cache = [
      // Cache a separate copy per route. Rationale: editing routes get the
      // placeholder instead. Without this, a placeholder built for an editor
      // can be cached and served to a visitor.
      'contexts' => ['route.name'],
      // Also render again when a vendor is turned on or off. Rationale: which
      // vendors are on decides whether a link shows its form, and turning a
      // module on doesn't clear cached pages by itself.
      'tags' => Cache::mergeTags($media->getCacheTags(), [FormEmbedVendorManager::CACHE_TAG]),
    ];

    foreach ($items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }

      // Check the link again here instead of trusting the check on save.
      // Rationale: the save-time check is a validation constraint, which
      // Drupal only runs when code calls validate() on the entity. For
      // example, a migration skips it unless told to validate. This is the
      // only check that always runs before a link goes into a script tag.
      $reason = NULL;
      $values = $this->vendorManager->resolve((string) ($item->getValue()['value'] ?? ''), $reason);

      if ($editing) {
        $elements[$delta] = [
          '#type' => 'component',
          '#component' => 'az_quickstart:form-embed-placeholder',
          '#props' => [
            // Leave the name out in the Media Library. Rationale: the admin
            // theme already prints each item's name under its preview.
            'label' => $in_media_library ? '' : $label,
            'vendor' => $values === NULL ? '' : (string) $this->vendorManager->getDefinition($values['vendor'])['label'],
          ],
          '#cache' => $cache,
        ];
        continue;
      }

      if ($values === NULL) {
        // Log the media id and the reason, never the link. Rationale: a
        // refused link can hold anything, including someone's personal data.
        $this->logger->warning('Refused to embed a form on media @id: @reason.', [
          '@id' => $media->id(),
          '@reason' => $reason,
        ]);
        $elements[$delta] = $this->buildRefusedNotice($cache);
        continue;
      }

      $elements[$delta] = [
        '#type' => 'component',
        '#component' => 'az_quickstart:form-embed',
        '#props' => [
          'form' => $values + ['label' => $label],
        ],
        '#cache' => $cache,
      ];
    }

    return $elements;
  }

  /**
   * Builds the notice shown where a refused link's form would have been.
   *
   * It has no link and no part of the link. Rationale: the link failed our
   * checks, so turning it into something clickable would undo them.
   *
   * @param array $cache
   *   The cache metadata to add.
   *
   * @return array
   *   A render array.
   */
  protected function buildRefusedNotice(array $cache): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This form could not be displayed because its link is not valid. See the log for details.'),
      '#attributes' => [
        'class' => ['alert', 'alert-danger'],
      ],
      // Only people who can administer media see this. Using an AccessResult
      // also adds the user.permissions cache context, so the notice can't be
      // cached for an admin and then shown to a visitor.
      '#access' => AccessResult::allowedIfHasPermission($this->currentUser, 'administer media'),
      '#cache' => $cache,
    ];
  }

}
