<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin advanced settings.
 */
class WebformAdminConfigAdvancedForm extends WebformAdminConfigBaseForm {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The render cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The webform libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_advanced_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderCache = $container->get('cache.render');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->routerBuilder = $container->get('router.builder');
    $instance->librariesManager = $container->get('webform.libraries_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    // UI.
    $form['ui'] = [
      '#type' => 'details',
      '#title' => $this->t('User interface settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['ui']['video_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Video display'),
      '#description' => $this->t('Controls how videos are displayed in inline help and within the global help section.'),
      '#options' => [
        'dialog' => $this->t('Dialog'),
        'link' => $this->t('External link'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $config->get('ui.video_display'),
    ];
    $form['ui']['toolbar_item'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Webforms as a top-level administration menu item in the toolbar'),
      '#description' => $this->t('If checked, the Webforms section will be displayed as a top-level administration menu item in the toolbar.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.toolbar_item'),
      '#access' => $this->moduleHandler->moduleExists('toolbar'),
    ];
    if ($this->librariesManager->isExcluded('tippyjs')) {
      $form['ui']['description_help'] = [
        '#type' => 'value',
        '#value' => $config->get('ui.description_help'),
      ];
      $form['ui']['description_help_disabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display element description as help text (tooltip)'),
        '#description' => $this->t("If checked, all element descriptions will be moved to help text (tooltip).")
          . '<br/><br/><em>'
          . $this->t('This behavior is disabled when the <a href=":href">Tippy.js library is disabled</a>.', [':href' => Url::fromRoute('webform.config.libraries')->toString()]) . '</em>',
        '#default_value' => $config->get('ui.description_help'),
        '#disabled' => TRUE,
      ];
    }
    else {
      $form['ui']['description_help'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display element description as help text (tooltip)'),
        '#description' => $this->t("If checked, all element descriptions will be moved to help text (tooltip)."),
        '#return_value' => TRUE,
        '#default_value' => $config->get('ui.description_help'),
      ];
    }
    $form['ui']['details_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save details open/close state'),
      '#description' => $this->t('If checked, all <a href=":details_href">Details</a> element\'s open/close state will be saved using <a href=":local_storage_href">Local Storage</a>.', [
        ':details_href' => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/details',
        ':local_storage_href' => 'https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API',
      ]),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.details_save'),
    ];
    $form['ui']['help_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable help'),
      '#description' => $this->t('If checked, help text will be removed from every webform page and form.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.help_disabled'),
    ];
    $form['ui']['dialog_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable dialogs'),
      '#description' => $this->t('If checked, all modal/off-canvas dialogs (i.e. popups) will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.dialog_disabled'),
    ];
    $form['ui']['offcanvas_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable off-canvas system tray'),
      '#description' => $this->t('If checked, all off-canvas system trays will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.offcanvas_disabled'),
      '#states' => [
        'visible' => [
          ':input[name="ui[dialog_disabled]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['ui']['promotions_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable promotions'),
      '#description' => $this->t('If checked, dismissible promotion messages that appear when the Webform module is updated will be disabled.') . ' ' .
        $this->t('Promotions on the <a href=":href">Webform: Add-ons</a> page will still be displayed.', [':href' => Url::fromRoute('webform.addons')->toString()]) . '<br/>' .
        $this->t('Note: Promotions are only visible to users who can <em>administer modules</em>.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.promotions_disabled'),
    ];
    $form['ui']['support_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable support options'),
      '#description' => $this->t('If checked, support option, displayed on the <a href=":href_addons">Add-ons</a> and <a href=":help_href">Help</a> pages will be disabled.', [':href_addons' => Url::fromRoute('webform.addons')->toString(), ':href_help' => Url::fromRoute('webform.help')->toString()]),
      '#return_value' => TRUE,
      '#default_value' => $config->get('ui.support_disabled'),
    ];

    // Requirements.
    $form['requirements'] = [
      '#type' => 'details',
      '#title' => $this->t('Requirement settings'),
      '#description' => $this->t('The below requirements are checked by the <a href=":href">Status report</a>.', [':href' => Url::fromRoute('system.status')->toString()]),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['requirements']['cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if CDN is being used for external libraries'),
      '#description' => $this->t('If unchecked, all warnings about missing libraries will be disabled.') . '<br/><br/>' .
        $this->t('Relying on a CDN for external libraries can cause unexpected issues with Ajax and BigPipe support. For more information see: <a href=":href">Issue #1988968</a>', [':href' => 'https://www.drupal.org/project/drupal/issues/1988968']),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.cdn'),
    ];

    $form['requirements']['clientside_validation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if Webform Clientside Validation module is installed when using the Clientside Validation module'),
      '#description' => $this->t('If unchecked, all warnings about the Webform Clientside Validation will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.clientside_validation'),
    ];

    $form['requirements']['bootstrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if the Webform Bootstrap Integration module is installed when using the Bootstrap theme'),
      '#description' => $this->t('If unchecked, all warnings about the Webform Bootstrap Integration module will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.bootstrap'),
    ];
    $form['requirements']['spam'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if SPAM protection module is installed'),
      '#description' => $this->t('If unchecked, all warnings about Webform SPAM protection will be disabled.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('requirements.spam'),
    ];

    // Test.
    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['test']['types'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element type'),
      '#description' => $this->t("Above test data is keyed by element #type."),
      '#default_value' => $config->get('test.types'),
    ];
    $form['test']['names'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Test data by element key'),
      '#description' => $this->t("Above test data is keyed by full or partial element keys. For example, using 'zip' will populate element keys that are 'zip' and 'zip_code' but not 'zipcode' or 'zipline'."),
      '#default_value' => $config->get('test.names'),
    ];

    // Batch.
    $form['batch'] = [
      '#type' => 'details',
      '#title' => $this->t('Batch settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['batch']['default_batch_export_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch export size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_export_size'),
      '#description' => $this->t('Batch export size is used when submissions are being exported/downloaded.'),
    ];
    $form['batch']['default_batch_import_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch import size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_import_size'),
      '#description' => $this->t('Batch import size is used when submissions are being imported/uploaded.'),
    ];
    $form['batch']['default_batch_update_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch update size'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_update_size'),
      '#description' => $this->t('Batch update size is used when submissions are being bulk updated.'),
    ];
    $form['batch']['default_batch_delete_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch delete size'),
      '#description' => $this->t('Batch delete size is used when submissions are being cleared.'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_delete_size'),
    ];
    $form['batch']['default_batch_email_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch email size'),
      '#description' => $this->t('Batch email size is used by any handler that sends out bulk emails. This include the scheduled email handler.'),
      '#min' => 1,
      '#required' => TRUE,
      '#default_value' => $config->get('batch.default_batch_email_size'),
    ];

    // Repair.
    $form['repair'] = [
      '#type' => 'details',
      '#title' => $this->t('Repair webform configuration'),
      '#open' => TRUE,
      '#help' => FALSE,
      '#weight' => 100,
    ];
    $form['repair']['title'] = [
      '#markup' => $this->t('This action will…'),
    ];
    $form['repair']['list'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Repair webform submission storage schema'),
        $this->t('Repair admin configuration'),
        $this->t('Repair webform settings'),
        $this->t('Repair webform handlers'),
        $this->t('Repair webform field storage definitions'),
        $this->t('Repair webform submission storage schema'),
        $this->t('Remove webform submission translation settings'),
      ],
    ];
    $form['repair']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Repair webform configuration'),
      '#url' => Url::fromRoute('webform.config.repair'),
      '#attributes' => ['class' => ['button', 'button--small']],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update config and submit form.
    $config = $this->config('webform.settings');
    $config->set('ui', $form_state->getValue('ui'));
    $config->set('requirements', $form_state->getValue('requirements'));
    $config->set('test', $form_state->getValue('test'));
    $config->set('batch', $form_state->getValue('batch'));

    // Track if help is disabled.
    // @todo Figure out how to clear cached help block.
    $is_help_disabled = ($config->getOriginal('ui.help_disabled') !== $config->get('ui.help_disabled'));
    $is_toolbar_item = ($config->getOriginal('ui.toolbar_item') !== $config->get('ui.toolbar_item'));

    parent::submitForm($form, $form_state);

    // Clear cached data.
    if ($is_help_disabled || $is_toolbar_item) {
      // Flush cache when help is being enabled.
      // @see webform_help()
      drupal_flush_all_caches();
    }
    else {
      // Clear render cache so that local tasks can be updated to hide/show
      // the 'Contribute' tab.
      // @see webform_local_tasks_alter()
      $this->renderCache->deleteAll();
      $this->routerBuilder->rebuild();
    }

    // Redirect to the update advanced admin configuration form.
    if ($is_toolbar_item) {
      $path = $config->get('ui.toolbar_item')
        ? '/admin/webform/config/advanced'
        : '/admin/structure/webform/config/advanced';
      $form_state->setRedirectUrl(Url::fromUserInput($path));
    }
  }

}
