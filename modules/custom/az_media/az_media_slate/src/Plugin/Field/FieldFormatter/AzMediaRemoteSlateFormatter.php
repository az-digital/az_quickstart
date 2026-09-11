<?php

declare(strict_types=1);

namespace Drupal\az_media_slate\Plugin\Field\FieldFormatter;

use Drupal\az_media_slate\AzMediaSlateService;
use Drupal\az_media_slate\SlateUrl;
use Drupal\Component\Utility\Html;
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
 * This runs when a page shows a Slate Form media item. It renders an empty div
 * with an id we choose, such as az-media-slate-<media uuid>-0, and passes that
 * id to Slate as the embed URL's "div" parameter. js/az-media-slate.js then
 * loads Slate's script, which fetches the form and writes it into that div.
 *
 * While someone is editing, it renders a grey placeholder instead. If the
 * stored URL fails SlateUrl's checks, people who can administer media see a
 * notice, and everyone else sees nothing.
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
   * The current user, for deciding who sees the rejected-URL notice.
   */
  protected AccountInterface $currentUser;

  /**
   * Logs the media id and the reason whenever we refuse a URL.
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
   * The regex media_remote checks a pasted URL against when media is saved.
   *
   * The media_remote module requires one. It isn't the security boundary:
   * SlateUrl::parse() runs again at render, and viewElements() says why. This
   * regex stays close to the parser so an editor hears about a bad URL while
   * saving, instead of finding an empty space on the page later. That's also
   * why it refuses person.
   */
  public static function getUrlRegexPattern() {
    // Careful: this must never accept a URL that SlateUrl::parse() rejects.
    // If it does, the editor saves without error and the form quietly never
    // shows. testSaveTimePatternMatchesParser() covers the cases that matter.
    //
    // Only the scheme, host, and id ignore case, via (?i:...). Don't put /i on
    // the whole pattern. For example, SYS:first=x would then pass here, but
    // Slate requires lowercase keys, so the parser rejects it.
    $host = '(?i:https:\/\/([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+technolutions\.net)';

    // An optional form name after /register/, the same shape as
    // SlateUrl::PATH_PATTERN, as in /register/moreinfo.
    $name = '(?:[A-Za-z0-9_-]+\/?)?';

    // One query parameter: either an id shaped like a GUID, or a key that looks
    // like an export key (see SlateUrl::PREFILL_PATTERN). The lookahead
    // (?!(?:id|person)=) keeps a bad id and person out of the second branch.
    // It matches the whole key, so identity and personal_email still pass.
    //
    // Keys can't contain percent escapes here, even though the parser decodes
    // them. Rationale: an escape can decode into something the parser rejects.
    // For example, sys%20first decodes to "sys first". Refusing every escape
    // keeps this stricter than the parser, never looser. Slate's docs write
    // export keys unencoded, so a URL written that way isn't affected.
    $pair = '(?:id=(?i:[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12})|(?!(?:id|person)=)[a-z0-9_:.-]+=[^&#]*)';

    // The link has to name a form somehow, the same rule as the parser: a name
    // in the path, or an id parameter. The id has to start a parameter, right
    // after the first ? or after an &. Rationale: a value can contain a ?, so
    // matching any "?id=" would count ?sys:first=a?id=x as having an id.
    $names_a_form = '(?=[^?]*\/register\/[A-Za-z0-9_-]|[^?]*\?(?:[^&#]*&)*id=)';

    return '/^' . $names_a_form . $host . '\/register\/' . $name . '(?:\?(?:' . $pair . '(?:&' . $pair . ')*)?)?$/';
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      'https://uaz.technolutions.net/register/?id=dbfabd84-d348-4bf9-88ef-1832b354fcb0',
      'https://uaz.technolutions.net/register/?id=dbfabd84-d348-4bf9-88ef-1832b354fcb0&sys:first=Wilbur',
      'https://uaz.technolutions.net/register/moreinfo',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * The Name field starts with this static placeholder for the editor to
   * replace. It leaves out the URL, because a pasted Slate link is long and
   * makes a poor name.
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    return t('Slate Form Name');
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

      // Check the URL again here instead of trusting the check on save.
      // Rationale: media_remote's check is a validation constraint, a rule
      // Drupal only runs when code calls validate() on the entity. For
      // example, a migration skips it unless told to validate. This is the
      // only check that always runs before a URL goes into a script tag.
      $reason = NULL;
      $slate_url = SlateUrl::parse((string) $item->getValue()['value'], $reason);
      if ($slate_url === NULL) {
        // Log the media id and the reason, never the URL. Rationale: a
        // rejected URL can hold anything, including someone's personal data.
        $this->logger->warning('Refused to embed a Slate form on media @id: @reason.', [
          '@id' => $entity->id(),
          '@reason' => $reason,
        ]);
        $elements[$delta] = $this->buildRejectionNotice();
        continue;
      }

      // Build the container id from the media's UUID. Don't use
      // Html::getUniqueId() here. Rationale: it numbers ids within one request
      // (az-media-slate, then az-media-slate--2), and the render cache saves
      // that markup for later requests. So two embeds cached on different
      // requests can both come back as az-media-slate on the same page.
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
          // Cache a separate copy per route. Rationale: editing routes get the
          // placeholder instead. Without this, a placeholder built for an
          // editor can be cached and served to a visitor.
          'contexts' => ['route.name'],
          'tags' => $entity->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

  /**
   * Builds the grey box shown in place of the form while editing.
   *
   * It matches the Trellis placeholder in az_media_trellis: the media's name,
   * in bold, on grey.
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
        // Escape the name. Rationale: html_tag runs a plain string through
        // Xss::filterAdmin(), which keeps tags like <b>. So a media named
        // "Tricky <b>bold</b>" would show up bold instead of as typed.
        '#value' => Html::escape($label),
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
   * Builds the notice shown where a rejected URL's form would have been.
   *
   * It has no link and no part of the URL. Rationale: the URL failed our
   * checks, so turning it into something clickable would undo them.
   */
  protected function buildRejectionNotice(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This Slate form could not be displayed because its address is not valid. See the log for details.'),
      '#attributes' => [
        'class' => ['az-media-slate-error'],
      ],
      // Only people who can administer media see this. Using an AccessResult
      // also adds the user.permissions cache context automatically, so the
      // notice can't be cached for an admin and then shown to a visitor.
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
