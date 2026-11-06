<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Form;

use Drupal\az_form_embed\FormEmbedVendorManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Media Library's form for adding a Form embed from a link.
 *
 * It follows media_remote's URL form, plus help under the box that names the
 * form providers this site accepts.
 *
 * @see \Drupal\media_remote\Form\MediaRemoteMediaForm
 */
class FormEmbedAddForm extends AddFormBase {

  /**
   * Constructs a FormEmbedAddForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The Media Library UI builder.
   * @param \Drupal\az_form_embed\FormEmbedVendorManager $vendorManager
   *   Finds the turned-on vendors.
   * @param \Drupal\media_library\OpenerResolverInterface|null $opener_resolver
   *   The opener resolver.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MediaLibraryUiBuilder $library_ui_builder,
    protected FormEmbedVendorManager $vendorManager,
    ?OpenerResolverInterface $opener_resolver = NULL,
  ) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('plugin.manager.az_form_embed_vendor'),
      $container->get('media_library.opener_resolver'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_az_form_embed';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Form link'),
      '#description' => $this->describeVendors(),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://',
      ],
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Builds the help under the link box: which providers, and an example.
   *
   * @return string
   *   The help text.
   */
  protected function describeVendors(): string {
    $vendors = $this->vendorManager->getVendors();
    if ($vendors === []) {
      return (string) $this->t("No form providers are turned on, so this site can't embed forms yet.");
    }
    $first = reset($vendors);
    return (string) $this->t("Paste the form's share link. This site accepts forms from @vendors. For example: @example", [
      '@vendors' => implode(', ', array_map(fn ($vendor) => $vendor->label(), $vendors)),
      '@example' => $first->examples()[0] ?? '',
    ]);
  }

  /**
   * Submit handler for the Add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('url')], $form, $form_state);
  }

  /**
   * Validates the pasted link by building the media item and validating it.
   *
   * Validating the media item runs the same check as saving it, so the editor
   * sees the same message in both places.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');
    $media_type = $this->getMediaType($form_state);
    $media_storage = $this->entityTypeManager->getStorage('media');
    $source_field_name = $this->getSourceFieldName($media_type);
    $media = $this->createMediaFromValue($media_type, $media_storage, $source_field_name, $url);
    foreach ($media->validate() as $violation) {
      $form_state->setErrorByName('url', $violation->getMessage());
    }
  }

}
