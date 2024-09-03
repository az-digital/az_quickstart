<?php

namespace Drupal\az_publication\Controller;

use Drupal\az_publication\Entity\AZAuthorInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;

/**
 * Class AZAuthorController.
 *
 *  Returns responses for Author routes.
 */
class AZAuthorController extends ControllerBase {

  /**
   * Constructs a new \Drupal\az_publication\Controller object.
   */
  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected RendererInterface $renderer,
  ) {}

  /**
   * Displays a Author revision.
   *
   * @param int $az_author_revision
   *   The Author revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($az_author_revision) {
    $az_author = $this->entityTypeManager()->getStorage('az_author')
      ->loadRevision($az_author_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('az_author');

    return $view_builder->view($az_author);
  }

  /**
   * Page title callback for a Author revision.
   *
   * @param int $az_author_revision
   *   The Author revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($az_author_revision) {
    /** @var \Drupal\az_publication\Entity\AZAuthorInterface $az_author */
    $az_author = $this->entityTypeManager()->getStorage('az_author')
      ->loadRevision($az_author_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $az_author->label(),
      '%date' => $this->dateFormatter->format($az_author->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Author.
   *
   * @param \Drupal\az_publication\Entity\AZAuthorInterface $az_author
   *   A Author object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(AZAuthorInterface $az_author) {
    $account = $this->currentUser();
    /** @var \Drupal\az_publication\AZAuthorStorageInterface $az_author_storage */
    $az_author_storage = $this->entityTypeManager()->getStorage('az_author');

    $langcode = $az_author->language()->getId();
    $langname = $az_author->language()->getName();
    $languages = $az_author->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $az_author->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $az_author->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all author revisions") || $account->hasPermission('administer author entities')));
    $delete_permission = (($account->hasPermission("delete all author revisions") || $account->hasPermission('administer author entities')));

    $rows = [];

    $vids = $az_author_storage->revisionIds($az_author);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\az_publication\Entity\AZAuthorInterface $revision */
      $revision = $az_author_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid !== $az_author->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.az_author.revision', [
            'az_author' => $az_author->id(),
            'az_author_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $az_author->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderInIsolation($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.az_author.translation_revert', [
                'az_author' => $az_author->id(),
                'az_author_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.az_author.revision_revert', [
                'az_author' => $az_author->id(),
                'az_author_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.az_author.revision_delete', [
                'az_author' => $az_author->id(),
                'az_author_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['az_author_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
