<?php

namespace Drupal\webform_share\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform share embed form.
 */
class WebformShareEmbedForm extends FormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    $instance->requestHandler = $container->get('webform.request');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_share_embed_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $form['info'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Choose how you want to embed the webform and then copy-n-paste the below code snippet directly into the HTML source of any webpage.'),
      '#message_type' => 'info',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
    ];
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Embed type'),
      '#title_display' => 'invisible',
      '#options' => [
        'script' => $this->t('JavaScript -- The embedded webform will be inserted using JavaScript.'),
        'resizing' => $this->t('Resizing iframe -- The embedded webform will be responsive and adjusted to fit within the page using an iframe and JavaScript.'),
        'fixed' => $this->t('Fixed iframe -- The embedded webform will be a fixed size on the page using an iframe with a scrollbar.'),
      ],
      '#options_display' => 'buttons',
      '#options_description_display' => 'help',
      '#default_value' => 'script',
    ];
    $types = [
      'script' => [
        'type' => 'script',
        'title' => $this->t('JavaScript code'),
      ],
      'resizing' => [
        'type' => 'iframe',
        'title' => $this->t('Resizing iframe code'),
        'javascript' => TRUE,
      ],
      'fixed' => [
        'type' => 'iframe',
        'title' => $this->t('Fixed iframe code'),
      ],
    ];
    foreach ($types as $type => $item) {
      $build = [
        '#type' => 'webform_share_' . $item['type'],
        '#webform' => $webform,
        '#source_entity' => $source_entity,
        '#query' => $this->getRequest()->query->all(),
      ];
      if (!empty($item['javascript'])) {
        $build['#javascript'] = $item['javascript'];
      }
      $code = trim((string) $this->renderer->renderPlain($build));

      $form[$type] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['js-webform-share-admin-copy']],
        '#states' => [
          'visible' => [':input[name="type"]' => ['value' => $type]],
        ],
      ];
      $form[$type]['code'] = [
        '#type' => 'webform_codemirror',
        '#title' => $item['title'],
        '#mode' => 'html',
        '#default_value' => $code,
      ];
      $form[$type]['copy'] = [
        '#type' => 'button',
        '#value' => $this->t('Copy code'),
      ];
      $form[$type]['message'] = [
        '#markup' => $this->t('Code copied to clipboard…'),
        '#prefix' => '<strong class="webform-share-admin-copy-message">',
        '#suffix' => '</strong>',
      ];
    }

    $form['#attached']['library'][] = 'webform_share/webform_share.admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
