<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\Element\WebformMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for Webform add-ons.
 */
class WebformAddonsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The webform theme manager.
   *
   * @var \Drupal\webform\WebformThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addons;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->themeManager = $container->get('webform.theme_manager');
    $instance->addons = $container->get('webform.addons_manager');
    return $instance;
  }

  /**
   * Returns the Webform add-ons page.
   *
   * @return array
   *   The webform submission webform.
   */
  public function index() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons'],
      ],
    ];

    // Support.
    if (!$this->config('webform.settings')->get('ui.support_disabled')) {
      $build['support'] = ['#theme' => 'webform_help_support'];
    }

    // Filter.
    $is_claro_theme = $this->themeManager->isActiveTheme('claro');
    $data_source = $is_claro_theme ? '.admin-item' : 'li';
    $data_parent = $is_claro_theme ? '.admin-item' : 'li';

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by keyword'),
      '#attributes' => [
        'name' => 'text',
        'class' => ['webform-form-filter-text'],
        'data-summary' => '.webform-addons-summary',
        'data-item-singular' => $this->t('add-on'),
        'data-item-plural' => $this->t('add-ons'),
        'data-no-results' => '.webform-addons-no-results',
        'data-element' => '.admin-list',
        'data-source' => $data_source,
        'data-parent' => $data_parent,
        'title' => $this->t('Enter a keyword to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total add-ons', ['@total' => count($this->addons->getProjects())]),
      '#prefix' => '<p class="webform-addons-summary">',
      '#suffix' => '</p>',
    ];

    // Projects.
    $build['projects'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['webform-addons-projects', 'js-webform-details-toggle', 'webform-details-toggle'],
      ],
    ];

    // Store and disable compact mode.
    // @see system_admin_compact_mode
    $system_admin_compact_mode = system_admin_compact_mode();
    $this->request->cookies->set('Drupal_visitor_admin_compact_mode', FALSE);

    $categories = $this->addons->getCategories();
    foreach ($categories as $category_name => $category) {
      $build['projects'][$category_name] = [
        '#type' => 'details',
        '#title' => $category['title'],
        '#attributes' => ['data-webform-element-id' => 'webform-addons-' . $category_name],
        '#open' => TRUE,
      ];
      $projects = $this->addons->getProjects($category_name);
      foreach ($projects as $project_name => &$project) {
        if (!empty($project['install']) && !$this->moduleHandler()->moduleExists($project_name)) {
          // If current user can install module then display a dismissible warning.
          if ($this->currentUser()->hasPermission('administer modules')) {
            $build['projects'][$project_name . '_message'] = [
              '#type' => 'webform_message',
              '#message_id' => $project_name . '_message',
              '#message_type' => 'warning',
              '#message_close' => TRUE,
              '#message_storage' => WebformMessage::STORAGE_USER,
              '#message_message' => $this->t('Please install to the <a href=":href">@title</a> project to improve the Webform module\'s user experience.', [':href' => $project['url']->toString(), '@title' => $project['title']]) .
                ' <em>' . $project['install'] . '</em>',
              '#weight' => -100,
            ];
          }
        }

        // Append (Experimental) to title.
        if (!empty($project['experimental'])) {
          $project['title'] .= ' [' . $this->t('EXPERIMENTAL') . ']';
        }
        $project['description'] .= '<br /><small>' . $project['url']->toString() . '</small>';

        // Append recommended to project's description.
        if (!empty($project['recommended'])) {
          $project['description'] .= '<br /><b class="color-success"> ★' . $this->t('Recommended') . '</b>';
        }
      }

      $build['projects'][$category_name]['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $projects,
      ];
    }

    // Reset compact mode to stored setting.
    $this->request->cookies->get('Drupal_visitor_admin_compact_mode', $system_admin_compact_mode);

    // No results.
    $build['no_results'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('No add-ons found. Try a different search.'),
      '#message_type' => 'info',
      '#attributes' => ['class' => ['webform-addons-no-results']],
    ];

    $build['#attached']['library'][] = 'webform/webform.addons';
    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

}
