<?php

declare(strict_types=1);

namespace Drupal\az_media_slate\Plugin\Field\FieldFormatter;

use Drupal\az_media_slate\AzMediaSlateService;
use Drupal\az_media_slate\SlateUrl;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a Slate form embed.
 *
 * Slate's embed is a script that fetches the form and injects it into a
 * container div whose id we choose and pass along as the embed's "div"
 * parameter. So this formatter renders an empty container, hands the id to
 * Slate through the URL, and lets a small behavior load the script.
 *
 * @see \Drupal\az_media_slate\SlateUrl
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */
#[FieldFormatter(
  id: 'az_media_remote_slate',
  label: new TranslatableMarkup('Remote Media - Slate Form'),
  description: new TranslatableMarkup('Renders a Slate form embed with a fallback link and responsive sizing.'),
  field_types: [
    'string',
  ],
)]
class AzMediaRemoteSlateFormatter extends MediaRemoteFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Tells us whether an editor is working on this page.
   */
  protected AzMediaSlateService $slateService;

  /**
   * The current user, for gating the rejected-URL notice.
   */
  protected AccountInterface $currentUser;

  /**
   * Logger for URLs we refuse to load.
   */
  protected LoggerInterface $logger;

  public function __construct(
    $plugin_id,
    $plugin_definition,
    $field_definition,
    $settings,
    $label,
    $view_mode,
    $third_party_settings,
    AzMediaSlateService $slate_service,
    AccountInterface $current_user,
    LoggerInterface $logger,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->slateService = $slate_service;
    $this->currentUser = $current_user;
    $this->logger = $logger;
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
      $container->get('az_media_slate'),
      $container->get('current_user'),
      $container->get('logger.factory')->get('az_media_slate')
    );
  }

  /**
   * {@inheritdoc}
   *
   * The pattern media_remote validates a pasted URL against on save.
   *
   * SlateUrl::parse() is what actually decides whether we will load a URL, and
   * it runs again at render time - see viewElements() for why the save-time
   * check cannot be relied on. This pattern exists because media_remote
   * requires one, and it is kept close to what the parser accepts so that an
   * editor finds out about a bad URL while saving rather than seeing an empty
   * space on the page afterwards. Only Slate's documented prefill keys are
   * allowed through, which is what catches a pasted person parameter here.
   */
  public static function getUrlRegexPattern() {
    // Case-insensitivity is scoped to the scheme, host, and id with (?i:...)
    // rather than applied to the whole pattern with the /i flag. Slate requires
    // query keys to be lowercase and SlateUrl enforces that, so a blanket /i
    // would accept FORM_sys=x on save and then reject it at render.
    //
    // The prefill key allows lowercase characters or a percent escape, which
    // mirrors SlateUrl::PREFILL_PATTERN as closely as a regex over an encoded
    // URL can. The two cannot agree perfectly, because this reads the URL
    // encoded and the parser reads it decoded: form_%53YS decodes to form_SYS,
    // so it passes here and is rejected there. That is a contrived URL rather
    // than an editor's mistake, and SlateUrl remains the check that decides
    // what loads.
    return '/^(?i:https:\/\/([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+technolutions\.net)\/register\/\?id=(?i:[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12})(&(form_(?:[a-z0-9_:.-]|%[0-9A-Fa-f]{2})+|output|div)=[^&#]*)*$/';
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      'https://uaz.technolutions.net/register/?id=dbfabd84-d348-4bf9-88ef-1832b354fcb0',
      'https://uaz.technolutions.net/register/?id=dbfabd84-d348-4bf9-88ef-1832b354fcb0&form_sys:first=Wilbur',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    if (SlateUrl::parse($url) !== NULL) {
      return t('Slate Form at @url', ['@url' => $url]);
    }
    return parent::deriveMediaDefaultNameFromUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $editing = $this->slateService->isEditingContext();
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }

      if ($editing) {
        $elements[$delta] = $this->buildEditingPlaceholder((string) $entity->label());
        continue;
      }

      // Validate again, rather than trusting that the value was checked when
      // it was saved. media_remote's check is an entity-level constraint, so
      // any code that saves a media entity without calling validate() skips
      // it - a migration, for instance. This is the only check that always
      // runs before we put a URL in a script tag.
      $reason = NULL;
      $slate_url = SlateUrl::parse((string) $item->getValue()['value'], $reason);
      if ($slate_url === NULL) {
        // Log the media and why, never the URL itself. A rejected URL can
        // hold anything, including someone's personal data.
        $this->logger->warning('Refused to embed a Slate form on media @id: @reason.', [
          '@id' => $entity->id(),
          '@reason' => $reason,
        ]);
        $elements[$delta] = $this->buildRejectionNotice();
        continue;
      }

      // Build the container id from the media's UUID rather than
      // Html::getUniqueId(). That helper counts up within one request, so the
      // number it produces gets baked into render-cached markup and two
      // separately cached embeds can end up sharing an id.
      $container_id = 'az-media-slate-' . preg_replace('/[^a-z0-9-]/i', '', $entity->uuid()) . '-' . $delta;

      $elements[$delta] = [
        '#theme' => 'az_media_slate',
        '#canonical_url' => $slate_url->getCanonicalUrl(),
        '#attributes' => new Attribute([
          'id' => $container_id,
          'class' => [
            'az-media-slate__form',
          ],
          'data-az-slate-embed-src' => $slate_url->getEmbedUrl($container_id),
        ]),
        '#attached' => [
          'library' => ['az_media_slate/az-media-slate'],
        ],
        '#cache' => [
          // What renders here depends on the route, because an editing route
          // gets the placeholder instead. Without this context a placeholder
          // built for an editor can be served from cache to a visitor.
          'contexts' => ['route.name'],
          'tags' => $entity->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

  /**
   * Builds the grey box shown in place of a form while editing.
   */
  protected function buildEditingPlaceholder(string $label): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['az-media-slate-placeholder'],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('@label - Slate form preview unavailable while editing.', [
          '@label' => $label,
        ]),
        '#attributes' => [
          'class' => ['az-media-slate-placeholder__label'],
        ],
      ],
      '#attached' => [
        'library' => ['az_media_slate/az-media-slate.styles'],
      ],
      '#cache' => [
        'contexts' => ['route.name'],
      ],
    ];
  }

  /**
   * Builds the notice shown where a rejected URL would have been.
   *
   * Carries no link and no part of the rejected URL. A URL that failed
   * validation is untrusted, so turning it into something clickable would
   * undo the check that rejected it.
   */
  protected function buildRejectionNotice(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This Slate form could not be displayed because its address is not valid. See the log for details.'),
      '#attributes' => [
        'class' => ['az-media-slate-error'],
      ],
      // Only show this to someone who can act on it. Building the access
      // result this way makes user.permissions bubble into the render array's
      // cache metadata on its own, so the notice cannot be cached for an
      // administrator and then served to a visitor.
      '#access' => AccessResult::allowedIfHasPermission($this->currentUser, 'administer media'),
      '#attached' => [
        'library' => ['az_media_slate/az-media-slate.styles'],
      ],
      '#cache' => [
        'contexts' => ['route.name'],
      ],
    ];
  }

}
