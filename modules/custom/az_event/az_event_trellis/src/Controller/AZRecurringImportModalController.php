<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns a modal copy of the az_recurring_import_rule configuration form.
 */
final class AZRecurringImportModalController extends ControllerBase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Create a new AZRecurringImportModalController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   The entity form builder.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFormBuilderInterface $entityFormBuilder, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('cache.default'),
    );
  }

  /**
   * Open az_recurring_import_rule configuration form as a modal.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse containing command to open a modal.
   */
  public function __invoke(Request $request): AjaxResponse {
    $search = [];
    $key = $request->query->get('search');
    // Attempt to get cached search if we have a valid cache key.
    if (!empty($key) && str_starts_with($key, 'az_recurring_import_modal:')) {
      // Get a cached search if there is one for our key.
      $search = $this->cache->get($key)->data ?? [];
    }

    // Create an AjaxResponse that opens a modal copy of the config form.
    $response = new AjaxResponse();
    // Entity forms require an entity.
    $config = $this->entityTypeManager->getStorage('az_recurring_import_rule')->create($search);
    // Generate a copy of the configuration entity form using the stub as basis.
    $config_form = $this->entityFormBuilder->getForm($config, 'add');
    // Forms generated during ajax calls do not have the right action path.
    $config_form['#action'] = Url::fromRoute('entity.az_recurring_import_rule.add_form')->toString();
    // Add an ajax command to open the modal.
    $response->addCommand(new OpenModalDialogCommand($this->t('Create Recurring Import'), $config_form, ['width' => '1000']));
    return $response;
  }

}
