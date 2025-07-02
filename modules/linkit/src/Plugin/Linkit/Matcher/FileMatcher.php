<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\linkit\Utility\LinkitXss;

/**
 * Provides specific linkit matchers for the file entity type.
 *
 * @Matcher(
 *   id = "entity:file",
 *   label = @Translation("File"),
 *   target_entity = "file",
 *   provider = "file"
 * )
 */
class FileMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    if (!empty($this->configuration['file_extensions'])) {
      $summary[] = $this->t('Limit matches to the following file extensions: @file_extensions', [
        '@file_extensions' => str_replace(' ', ', ', $this->configuration['file_extensions']),
      ]);
    }

    $summary[] = $this->t('Show image dimensions: @show_image_dimensions', [
      '@show_image_dimensions' => $this->configuration['images']['show_dimensions'] ? $this->t('Yes') : $this->t('No'),
    ]);

    $summary[] = $this->t('Show image thumbnail: @show_image_thumbnail', [
      '@show_image_thumbnail' => $this->configuration['images']['show_thumbnail'] ? $this->t('Yes') : $this->t('No'),
    ]);

    if ($this->moduleHandler->moduleExists('image') && $this->configuration['images']['show_thumbnail']) {
      $image_style = ImageStyle::load($this->configuration['images']['thumbnail_image_style']);
      if (!is_null($image_style)) {
        $summary[] = $this->t('Thumbnail style: @thumbnail_style', [
          '@thumbnail_style' => $image_style->label(),
        ]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'file_extensions' => '',
      'file_status' => FileInterface::STATUS_PERMANENT,
      'images' => [
        'show_dimensions' => FALSE,
        'show_thumbnail' => FALSE,
        'thumbnail_image_style' => 'linkit_result_thumbnail',
      ],
      'substitution_type' => 'file',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies() + [
      'module' => ['file'],
    ];

    if ($this->configuration['images']['show_thumbnail']) {
      $dependencies['module'][] = 'image';
      $dependencies['config'][] = 'image.style.' . $this->configuration['images']['thumbnail_image_style'];
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['extensions'] = [
      '#type' => 'details',
      '#title' => $this->t('File extensions'),
      '#open' => TRUE,
      '#weight' => -100,
    ];

    $file_extensions = str_replace(' ', ', ', $this->configuration['file_extensions']);
    $form['extensions']['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#default_value' => $file_extensions,
      '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [['\Drupal\file\Plugin\Field\FieldType\FileItem', 'validateExtensions']],
      '#maxlength' => 256,
    ];

    $form['images'] = [
      '#type' => 'details',
      '#title' => $this->t('Image file settings'),
      '#description' => $this->t('Extra settings for image files in the result.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['images']['show_dimensions'] = [
      '#title' => $this->t('Show pixel dimensions'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['images']['show_dimensions'],
    ];

    if ($this->moduleHandler->moduleExists('image')) {
      $form['images']['show_thumbnail'] = [
        '#title' => $this->t('Show thumbnail'),
        '#type' => 'checkbox',
        '#default_value' => $this->configuration['images']['show_thumbnail'],
      ];

      $form['images']['thumbnail_image_style'] = [
        '#title' => $this->t('Thumbnail image style'),
        '#type' => 'select',
        '#default_value' => $this->configuration['images']['thumbnail_image_style'],
        '#options' => image_style_options(FALSE),
        '#states' => [
          'visible' => [
            ':input[name="images[show_thumbnail]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['file_extensions'] = $form_state->getValue('file_extensions');

    $values = $form_state->getValue('images');
    if (!$values['show_thumbnail']) {
      $values['thumbnail_image_style'] = NULL;
    }

    $this->configuration['images'] = $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($search_string) {
    $query = parent::buildEntityQuery($search_string);

    $query->condition('status', $this->configuration['file_status']);

    if (!empty($this->configuration['file_extensions'])) {
      $file_extensions = explode(' ', $this->configuration['file_extensions']);
      $group = $query->orConditionGroup();
      foreach ($file_extensions as $file_extension) {
        $group->condition('filename', '%\.' . $this->database->escapeLike($file_extension), 'LIKE');
      }
      $query->condition($group);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDescription(EntityInterface $entity) {
    $description_array = [];

    $description_array[] = parent::buildDescription($entity);

    /** @var \Drupal\file\FileInterface $entity */
    $file = $entity->getFileUri();

    if ($this->configuration['images']['show_dimensions'] || $this->configuration['images']['show_thumbnail']) {
      $image_factory = \Drupal::service('image.factory');
      $supported_extensions = $image_factory->getSupportedExtensions();
      /** @var \Drupal\file\Validation\FileValidatorInterface $file_validator */
      $file_validator = \Drupal::service('file.validator');
      $extensions = implode(' ', $supported_extensions);
      $validators = ['FileExtension' => ['extensions' => $extensions]];
      // Check if the file extension is supported by the image toolkit.
      $result = DeprecationHelper::backwardsCompatibleCall(
       \Drupal::VERSION,
        '10.2',
        static function () use ($entity, $file_validator, $validators) {
          return $file_validator->validate($entity, $validators);
        },
        static function () use ($entity, $extensions) {
          return file_validate_extensions($entity, $extensions);
        }
      );
      if (empty($result)) {
        /** @var \Drupal\Core\Image\ImageInterface $image */
        $image = $image_factory->get($file);
        if ($image->isValid()) {
          if ($this->configuration['images']['show_dimensions']) {
            $description_array[] = $image->getWidth() . 'x' . $image->getHeight() . 'px';
          }

          if ($this->configuration['images']['show_thumbnail'] && $this->moduleHandler->moduleExists('image')) {
            $image_element = [
              '#weight' => -10,
              '#theme' => 'image_style',
              '#style_name' => $this->configuration['images']['thumbnail_image_style'],
              '#uri' => $entity->getFileUri(),
            ];

            $description_array[] = (string) \Drupal::service('renderer')->render($image_element);
          }
        }
      }
    }

    $description = implode('<br />', $description_array);
    return LinkitXss::descriptionFilter($description);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPath(EntityInterface $entity) {
    /** @var \Drupal\file\FileInterface $entity */
    return \Drupal::service('file_url_generator')->generateString($entity->getFileUri());
  }

}
