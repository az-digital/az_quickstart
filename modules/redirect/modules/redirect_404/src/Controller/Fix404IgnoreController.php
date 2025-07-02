<?php

namespace Drupal\redirect_404\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\redirect_404\RedirectNotFoundStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to ignore a path from the 'Fix 404 pages' page.
 */
class Fix404IgnoreController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configuration;

  /**
   * The redirect storage.
   *
   * @var \Drupal\redirect_404\RedirectNotFoundStorageInterface
   */
  protected $redirectStorage;

  /**
   * Constructs a Fix404Ignore object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\redirect_404\RedirectNotFoundStorageInterface $redirect_storage
   *   A redirect storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RedirectNotFoundStorageInterface $redirect_storage) {
    $this->configuration = $config_factory;
    $this->redirectStorage = $redirect_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('redirect.not_found_storage')
    );
  }

  /**
   * Adds path into the ignored list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function ignorePath(Request $request) {
    $editable = $this->configuration->getEditable('redirect_404.settings');
    $existing_config_raw = $editable->get('pages');
    $path = $request->query->get('path');

    if (empty($existing_config_raw) || !empty($path) || !strpos($path, $existing_config_raw)) {
      $this->redirectStorage->resolveLogRequest($path);

      // Users without 'administer redirect settings' and 'ignore 4040 request'
      // permission can also ignore pages.
      if (!$this->currentUser()->hasPermission('administer redirect settings') && $this->currentUser()->hasPermission('ignore 404 requests')) {
        $existing_config_raw .= $path . "\n";
        $editable->set('pages', $existing_config_raw);
        $editable->save();

        $response = $this->redirect('redirect_404.fix_404');
        $this->messenger()->addMessage($this->t('Resolved the path %path in the database.', [
          '%path' => $path,
        ]));
      }
      else {
        $options = [
          'query' => [
            'ignore' => $path,
            'destination' => Url::fromRoute('redirect_404.fix_404')->getInternalPath(),
          ],
        ];
        $response = $this->redirect('redirect.settings', [], $options);
        $this->messenger()->addMessage($this->t('Resolved the path %path in the database. Please check the ignored list and save the settings.', [
          '%path' => $path,
        ]));
      }

      return $response;
    }
  }

}
