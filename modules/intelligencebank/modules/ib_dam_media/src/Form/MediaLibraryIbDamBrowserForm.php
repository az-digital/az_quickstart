<?php

namespace Drupal\ib_dam_media\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\ib_dam\Asset\Asset;
use Drupal\ib_dam\Asset\EmbedAssetInterface;
use Drupal\ib_dam\AssetFormatter\AssetFormatterManager;
use Drupal\ib_dam\AssetValidation\AssetValidationManager;
use Drupal\ib_dam\AssetValidation\AssetValidationTrait;
use Drupal\ib_dam\IbDamResourceModel as Model;
use Drupal\ib_dam\Downloader;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse;
use Drupal\ib_dam_media\AssetStorage\MediaStorage;
use Drupal\ib_dam_media\Exceptions\MediaStorageUnableSaveMediaItem;
use Drupal\ib_dam_media\Exceptions\MediaTypeMatcherBadMediaTypeMatch;
use Drupal\ib_dam_media\MediaTypeMatcher;
use Drupal\media\MediaInterface;
use Drupal\media_library\Ajax\UpdateSelectionCommand;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MediaLibraryIbDamBrowserForm extends FormBase {
  use AssetValidationTrait;
  use MessengerTrait;

  const ADMIN_PERMISSION = 'administer intelligencebank configuration';

  protected $mediaTypeMatcher;
  protected $mediaTypesConfig;
  protected $downloader;
  protected $assetValidationManager;

  /**
   * Debug state.
   *
   * @var bool
   */
  private $debug;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  protected $configuration;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs widget plugin.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\ib_dam\AssetValidation\AssetValidationManager $asset_validation_manager
   *   The asset validation manager service.
   * @param \Drupal\ib_dam\Downloader $downloader
   *   The downloader service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\ib_dam_media\MediaTypeMatcher $mediaTypeMatcher
   *   The media type matcher service.
   */
  public function __construct(
    AccountInterface $current_user,
    LoggerChannelFactoryInterface $logger_factory,
    AssetValidationManager $asset_validation_manager,
    Downloader $downloader,
    ConfigFactoryInterface $config_factory,
    MediaTypeMatcher $mediaTypeMatcher,
    RequestStack $request_stack
  ) {
    $this->currentUser = $current_user;
    $this->logger = $logger_factory->get('ib_dam');
    $this->mediaTypesConfig = (array) $config_factory->get('ib_dam_media.settings')->get('media_types');
    $this->assetValidationManager = $asset_validation_manager;
    $this->downloader = $downloader;
    $this->mediaTypeMatcher = $mediaTypeMatcher;
    $this->request = $request_stack->getCurrentRequest();

    $this->configuration = $config_factory->get('ib_dam_media.settings');
    $debug_mode  = (boolean) $config_factory->get('ib_dam.settings')->get('debug');
    $has_rights  = $this->currentUser->hasPermission(self::ADMIN_PERMISSION);
    $this->debug = $debug_mode && $has_rights ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ib_dam_browser_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.ib_dam.asset_validation'),
      $container->get('ib_dam.downloader'),
      $container->get('config.factory'),
      $container->get('ib_dam_media.media_type_matcher'),
      $container->get('request_stack'),
    );
  }

  /**
   * Build allowed file extensions list for a given media types.
   *
   * @param array $media_type_ids
   *   An array of media type ids.
   *
   * @return array
   *   An array of allowed file extensions.
   */
  private function getAllowedFileExtensionsList(array $media_type_ids = []) {
    $supported_media_type_ids = array_column(
      $this->mediaTypesConfig,
      'media_type'
    );

    if (!$media_type_ids) {
      $media_type_ids = $supported_media_type_ids;
    }
    else {
      $media_type_ids = array_intersect(
        $supported_media_type_ids,
        $media_type_ids
      );
    }
    return $this->mediaTypeMatcher->getAllowedFileExtensions($media_type_ids, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?: 'process';

    $state = MediaLibraryState::fromRequest($this->request);
    $widget_context = $form_state->get(['widget_context']) ?? [];

    if (empty($widget_context)) {
      $opener_params = $state->getOpenerParameters();
      $widget_context['ib_dam_media'] = [
        'field' => [
          'name' => $opener_params['field_name'] ?? '',
          'entity_type_id' => $opener_params['entity_type_id'] ?? '',
          'entity_bundle_id' => $opener_params['bundle'] ?? '',
        ],
        'allowed_media_types' => $state->getAllowedTypeIds(),
      ];
      $form_state->set(['widget_context'], $widget_context);
    }

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    if ($step === 'process') {
      $form_state->set('step', $step);
      $this->buildProcessStep($form, $form_state);
    }
    elseif ($step === 'configure') {
      $this->buildConfigureStep($form, $form_state);
    }

    $url = Url::fromRoute('id_dam_media.asset_browser_form');
    $query = $state->all();
    $query[FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
    $url->setOptions(['query' => $query]);
    $form['#action'] = $url->toString();
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Select',
      '#attributes' => [
        'class' => ['is-entity-browser-submit'],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'wrapper'  => 'ib-dam-browser-form-ajax-wrapper',
        'event' => 'click',
      ],
    ];

    if ($step === 'process') {
      $form['submit']['#attributes']['class'][] = 'js-hide';
    }

    $form['#prefix'] = '<div id="ib-dam-browser-form-ajax-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * Build process step form.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function buildProcessStep(array &$form, FormStateInterface $form_state) {
    $allow_embed     = FALSE;
    $target_bundles  = [];
    $widget_context  = $form_state->get(['widget_context']);

    if (!empty($widget_context['ib_dam_media']['allowed_media_types'])) {
      $target_bundles = $widget_context['ib_dam_media']['allowed_media_types'];
    }

    $file_extensions = $this->getAllowedFileExtensionsList($target_bundles);

    if (!empty($this->mediaTypesConfig['embed']['media_type'])) {
      $embed_media_type_id = $this->mediaTypesConfig['embed']['media_type'];
      $allow_embed = in_array($embed_media_type_id, $target_bundles);
    }

    if (empty($target_bundles)) {
      if (!empty($widget_context['ib_dam_media']['field'])) {
        $this->showEmptyTargetBundlesError($widget_context['ib_dam_media']['field']);
      }
    }

    if (!$file_extensions) {
      $this->getMediaTypesError(TRUE);
    }

    if (!$file_extensions && !$allow_embed) {
      $form['actions']['submit']['#disabled'] = TRUE;
      return;
    }

    static::setWidgetSetting($form_state, 'configured_extensions', $file_extensions);
    static::setWidgetSetting($form_state, 'allow_embed', $allow_embed);

    $extensions_message = $this->t("<p>Please, configure allowed file types on field configuration pages.</p>Allowed file extensions to download:<br>@types", [
      '@types' => implode(', ', $file_extensions),
    ])->render();

    $media_types_message = $this->getMediaTypesError()->render();

    $form['ib_dam_app_el'] = [
      '#type' => 'ib_dam_app',
      '#file_extensions' => $file_extensions,
      '#allow_embed' => $allow_embed,
      '#debug_response' => $this->debug,
      '#messages' => [
        [
          'id' => 'local',
          'once' => TRUE,
          'text' => $extensions_message . '<p>' . $media_types_message . '</p>',
          'title' => $this->t("This file isn't allowed to download."),
        ],
        [
          'id' => 'embed',
          'once' => TRUE,
          'text' => $media_types_message,
          'title' => $this->t("You're not allowed to embed files."),
        ],
      ],
      '#submit_selector' => '.is-entity-browser-submit',
    ];
  }

  /**
   * Build configure step form.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function buildConfigureStep(array &$form, FormStateInterface $form_state) {
    /* @var $asset \Drupal\ib_dam\Asset\AssetInterface */
    $asset     = $this->getAssets($form_state, TRUE);
    $formatter = AssetFormatterManager::create($asset);

    $form['settings'] = [
      '#type'  => 'fieldset',
      '#tree'  => TRUE,
      '#title' => $this->t('Settings'),
    ];
    $form['settings'] += $formatter->settingsForm($asset);
  }

  /**
   * Creates asset object from iframe app response item.
   *
   * @param \stdClass $response
   *   Asset data for a Model class.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Returns an asset instance.
   */
  private function buildAsset(\stdClass $response) {
    $model = Model::buildModel($response);
    $mime  = $model->getMimetype();
    $model->setType(Downloader::getSourceTypeFromMime($mime));

    $asset = Asset::createFromSource($model, $this->currentUser->id());

    $source_type = $asset->getSourceType() == 'embed'
      ? 'embed'
      : $model->getType();

    $media_type_id = $this->mediaTypeMatcher->matchType($source_type, 'source_type');

    if (empty($media_type_id)) {
      $source = clone $asset->source();

      (new MediaTypeMatcherBadMediaTypeMatch($source_type, $source))
        ->logException()
        ->displayMessage();

      return NULL;
    }
    else {
      $storage_type_id = implode(':', [
        MediaStorage::class,
        $source_type,
        $media_type_id,
      ]);
      $asset->setStorageType($storage_type_id);
    }

    return $asset;
  }

  /**
   * Helper function to iterate over response items and build asset instances.
   *
   * @param array $items
   *   An array of response objects.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface[]
   *   An array of asset objects.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function prepareAssets(array $items = []) {
    $assets = [];
    foreach ($items as $item) {
      if ($asset = $this->buildAsset($item)) {
        $assets[] = $asset;
      }
    }
    return array_filter($assets);
  }

  /**
   * Fetch assets from the $form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param bool $first
   *   Optional. Return only first asset, instead of returning array.
   *   Defaults to FALSE.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface[]|\Drupal\ib_dam\Asset\AssetInterface
   *   Assets array or the first asset.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function getAssets(FormStateInterface $form_state, bool $first = FALSE) {
    $items = $form_state->getValue(['ib_dam_app_el', 'items'], []);
    $assets = $this->prepareAssets($items);
    return !$first ? $assets : reset($assets);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Prepare all required data.
    $step    = $form_state->get('step');
    $assets  = $form_state->get('assets') ?? $this->getAssets($form_state);
    // No browser element
    $browser = $form_state->get('browser') ?? $form['ib_dam_app_el'];

    // For the process step just store required data and rebuild the form.
    if ($step === 'process') {
      // Update step.
      $form_state->set('step', 'configure');
      // Store data in order to make it available on the next step(s).
      $form_state->set('browser', $browser);
      $form_state->set('assets', $assets);

      // Rebuild the form (and add asset configuration form) only for embed asset.
      $isEmbedAsset = !empty($assets) && $assets[0] instanceof EmbedAssetInterface;
      if ($isEmbedAsset) {
        $form_state->setRebuild();
      }
      // DC-4.0-10: Don't provide configuration form if asset is downloadable.
      else {
        $step = 'configure';
      }
    }

    if ($step === 'configure') {
      $validators[] = [
        'id'         => 'file',
        'validators' => [
          'validateFileExtensions' => static::getWidgetSetting($form_state, 'configured_extensions'),
          'validateFileDirectory'  => $this->getUploadLocation(),
          'validateFileSize'       => FALSE,
        ],
      ];

      $validators[] = [
        'id'         => 'resource',
        'validators' => [
          'validateIsAllowedResourceType' => [
            'type'    => 'embed',
            'allowed' => static::getWidgetSetting($form_state, 'allow_embed'),
          ],
        ],
      ];

      $this->validateAssets($validators, $assets, $form_state, $browser);

      if (empty($form_state->getErrors())) {
        $this->validateAndSaveAssets($assets, $browser, $form_state);
      }
    }
  }

  /**
   * Validate, save assets list.
   *
   * Run save process on a list of assets.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface[] $assets
   *   The list of assets to operate on them.
   * @param array &$element
   *   The reference to the ib_dam browser form element.
   *   Used to mark elements with errors.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   *
   * @return bool
   *   The result of operation.
   */
  public function validateAndSaveAssets(array $assets, array &$element, FormStateInterface $form_state) {
    /* @var $assets \Drupal\ib_dam\Asset\AssetInterface[] */
    foreach ($assets as $asset) {
      if (method_exists($asset, 'saveAttachments')) {
        try {
          $asset->saveAttachments($this->downloader, $this->getUploadLocation());
        }
        catch (AssetDownloaderBadResponse $e) {
          $e->logException()
            ->displayMessage();
        }
      }

      if ($asset instanceof EmbedAssetInterface) {
        // Apply overrides from form state to the asset.
        $settings = $form_state->getValue('settings');
        $asset->setUrl($settings['remote_url']);

        $width  = $settings['width']  ?? 0;
        $height = $settings['height'] ?? 0;
        $alt    = $settings['alt']    ?? '';
        $title  = $settings['title']  ?? '';

        if (!empty($alt)) {
          $asset->setDescription($alt);
          unset($settings['alt']);
        }

        if (!empty($title)) {
          $asset->setName($title);
          unset($settings['title']);
        }

        if (empty($width)) {
          unset($settings['width']);
        }
        if (empty($height)) {
          unset($settings['height']);
        }

        unset($settings['remote_url']);
        $asset->setDisplaySettings($settings);
      }
      // @todo: add support for local

      if ($this->debug) {
        $params = clone $asset->source();

        $this->logger->debug('Saving media, params: @args', [
          '@args' => print_r([
            'storage_type' => $asset->getStorageType(),
            'model' => $params,
          ], TRUE),
        ]);
      }

      $media = $asset->save();

      try {
        $media->save();

        $thumbnail = $asset->thumbnail();
        if ($thumbnail instanceof FileInterface && !empty($thumbnail->getFileUri())) {
          $media->thumbnail->entity = $thumbnail;
          $media->thumbnail->title = 'Thumbnail for ' . $asset->getName();
          $media->save();
        }

        $media_list[] = $media;
      }
      catch (\Error $e) {
        (new MediaStorageUnableSaveMediaItem($e->getMessage()))
          ->logException()
          ->displayMessage();
      }
    }

    if (empty($media_list)) {
      $form_state->setError($element, 'No media items was saved. See errors above.');
      return FALSE;
    }
    $form_state->setValue('ib_assets', $media_list);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    if ($step === 'configure' && empty($form_state->getErrors())) {
      // we finished, do cleanup.
      $this->clearFormValues($element, $form_state);
    }
  }

  public function submitModalFormAjax(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    /** @var MediaInterface[] $mediaList */
    $mediaList = $form_state->getValue('ib_assets');
    if ($step === 'configure' && empty($form_state->getErrors()) && !empty($mediaList)) {
      $media_ids = array_map(fn (MediaInterface $media) => $media->id(), $mediaList);
      $media_id_to_focus = array_reverse($media_ids)[0];
      $type = $mediaList[0]->bundle();
      //dump($media_ids, $media_id_to_focus);
      // Close our
      // Open media if...
      // Update selection
      // focus to selected media type.

      $response = new AjaxResponse();
      $response->addCommand(new CloseDialogCommand());

      $state = MediaLibraryState::fromRequest($this->request);
      $state->remove('media_library_content');
      $state->remove('ajax_form');
      $state->set('_media_library_form_rebuild', TRUE);

      /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
      $ui_builder = \Drupal::service('media_library.ui_builder');
      $state->set('media_library_selected_type', $type);
      $state->set('hash', $state->getHash());

      $build = $ui_builder->buildUi($state);

      $dialogMode = $this->getDialogMode();
      if ($dialogMode === 'regular') {
        $this->handleRegularDialogMode($response, $state, $build);
        $response->addCommand(new UpdateSelectionCommand($media_ids));
        $response->addCommand(new ReplaceCommand('#media-library-wrapper', $build));
        $response->addCommand(new InvokeCommand("#media-library-content [value=$media_id_to_focus]", 'focus'));
      }
      else {
        $response->addCommand(new UpdateSelectionCommand($media_ids));
        $build = $ui_builder->buildUi($state);
        $response->addCommand(new ReplaceCommand('#media-library-wrapper', $build));
        $response->addCommand(new InvokeCommand("#media-library-content [value=$media_id_to_focus]", 'focus'));
      }

      return $response;
    }
    if ($form_state->hasAnyErrors()) {
      $form_state->setRebuild();
      $errors = $form_state->getErrors();
    }
    return $form;
  }

  private function handleRegularDialogMode(AjaxResponse &$response, MediaLibraryState $state, array $library_ui): void {
    $dialog_options = MediaLibraryUiBuilder::dialogOptions();
    $response->addCommand(
      new OpenModalDialogCommand($dialog_options['title'], $library_ui, $dialog_options)
    );
  }

  private function handleStackedDialogMode(AjaxResponse $response, MediaLibraryState $state): AjaxResponse {
    $library_ui = \Drupal::service('media_library.ui_builder')->buildUi($state);
    $dialog_options = MediaLibraryUiBuilder::dialogOptions();
    return (new AjaxResponse())
      ->addCommand(new OpenModalDialogCommand($dialog_options['title'], $library_ui, $dialog_options));
  }

  /**
   * Clear values from Iframe response form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    if (isset($element['ib_dam_app_el'])) {
      $form_state->setValueForElement($element['ib_dam_app_el'], '');
      NestedArray::setValue($form_state->getUserInput(), $element['ib_dam_app_el']['#parents'], '');
    }
    $form_state->setValue('ib_assets', NULL);
  }

  /**
   * Return upload location for an assets.
   */
  protected function getUploadLocation() {
    return $this->configuration->get('upload_location') ?? 'public://intelligencebank';
  }

  protected function getDialogMode() {
    return $this->configuration->get('dialog_mode') ?? 'regular';
  }

  /**
   * Helper method to show error when there are no available file extensions.
   */
  private function getMediaTypesError($display = FALSE) {
    // @todo: check configuration permission.
    $message = $this->t("Check media types mapping <a href=':link' target='_blank'>on configuration form</a> to upload different file types.", [
      ':link' => Url::fromRoute('ib_dam_media.configuration_form')->toString(),
    ]);

    if (!$display) {
      return $message;
    }

    $this->messenger()->addWarning($message);
    return NULL;
  }

  /**
   * Helper method to show error when there are no enabled target bundles.
   */
  private function showEmptyTargetBundlesError(array $field) {
    // @todo: add link to the field configuration page.
    $this->messenger()->addWarning(
      $this->t("You should allow at least one target bundle in %field field settings on %bundle in %entity_type", [
        '%field' => $field['name'],
        '%bundle' => $field['entity_bundle_id'],
        '%entity_type' => $field['entity_type_id'],
      ]));
  }

  /**
   * Extract given setting from widget_context.
   *
   * Widget context used to pass information about calling context.
   *
   * @see ib_dam_media_field_widget_entity_browser_entity_reference_form_alter()
   * @see ib_dam_media_form_entity_embed_dialog_alter()
   */
  private static function getWidgetSetting(FormStateInterface $form_state, $setting) {
    $parents = ['widget_context', 'ib_dam_media'];
    $parents[] = $setting;
    return $form_state->get($parents);
  }

  /**
   * Set setting in the widget_context.
   */
  private static function setWidgetSetting(FormStateInterface &$form_state, $setting, $value) {
    $parents = ['widget_context', 'ib_dam_media'];
    $parents[] = $setting;
    $form_state->set($parents, $value);
  }

  /**
   * Getter for trait validation functionality.
   *
   * @return \Drupal\ib_dam\AssetValidation\AssetValidationManager
   *   The AssetValidationManager instance.
   */
  protected function getAssetValidationManager() {
    return $this->assetValidationManager;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
