<?php

namespace Drupal\smart_date\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for smart date format edit forms.
 *
 * @ingroup smart_date
 */
class SmartDateFormatForm extends EntityForm {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $smartDateFormatStorage;

  /**
   * Constructs a new date format form.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date service.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $smart_date_format_storage
   *   The smart date format storage.
   */
  public function __construct(DateFormatterInterface $date_formatter, ConfigEntityStorageInterface $smart_date_format_storage) {
    $this->dateFormatter = $date_formatter;
    $this->smartDateFormatStorage = $smart_date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('smart_date_format')
    );
  }

  /**
   * Checks for an existing date format.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    return (bool) $this->smartDateFormatStorage->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;
    // Populate defaults.
    if ($entity->isNew()) {
      $options = [
        'date_format' => 'D, M j Y',
        'time_format' => 'g:ia',
        'time_hour_format' => 'ga',
        'separator' => ' - ',
        'join' => ', ',
        'allday_label' => 'All day',
        'date_first' => '1',
        'ampm_reduce' => '1',
        'site_time_toggle' => '1',
      ];
    }
    else {
      // Populate from the entity.
      $options = $entity->getOptions();
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $entity->label(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#disabled' => !$entity->isNew(),
      '#maxlength' => 64,
      '#description' => $this
        ->t('A unique name for this item. It must only contain lowercase letters, numbers, and underscores.'),
      '#machine_name' => [
        'exists' => [
          $this,
          'exists',
        ],
      ],
    ];

    $form['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP Date Format'),
      '#description' => $this->t('The <a href="@php-date-reference">PHP date code</a> to use for formatting dates.', ['@php-date-reference' => 'https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters']),
      '#default_value' => $options['date_format'],
      '#size' => 20,
    ];

    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP Time Format'),
      '#description' => $this->t('The <a href="@php-date-reference">PHP date code</a> to use for formatting times.', ['@php-date-reference' => 'https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters']),
      '#default_value' => $options['time_format'],
      '#size' => 20,
    ];

    $form['time_hour_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP Time Format - on the hour'),
      '#description' => $this->t('The <a href="@php-date-reference">PHP date code</a> to use for formatting times that fall on the hour. Examples might be 2pm or 14h. Leave this blank to always use the standard format specified above.', ['@php-date-reference' => 'https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters']),
      '#default_value' => $options['time_hour_format'],
      '#size' => 20,
    ];

    $form['allday_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All Day Label'),
      '#description' => $this->t('What to output when an event has been set to run all day. Leave blank to only show the date.'),
      '#default_value' => $options['allday_label'],
      '#size' => 20,
    ];

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time separator'),
      '#description' => $this->t('The string to separate the start and end times. Include spaces before and after if those are desired.'),
      '#default_value' => $options['separator'],
      '#size' => 10,
    ];

    $form['join'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time join'),
      '#description' => $this->t('The characters that will be used to join dates and their associated times.'),
      '#default_value' => $options['join'],
      '#size' => 10,
    ];

    $form['date_first'] = [
      '#type' => 'select',
      '#title' => $this->t('First part shown'),
      '#description' => $this->t('Specify whether the time or date should be shown first.'),
      '#default_value' => $options['date_first'],
      '#options' => [
        '1' => $this->t('Date'),
        '0' => $this->t('Time'),
      ],
    ];

    $form['ampm_reduce'] = [
      '#type' => 'checkbox',
      '#return_value' => '1',
      '#title' => $this->t('Reduce output duplication'),
      '#description' => $this->t("Don't show am/pm in the start time if it's the same as the value for the end time, in the same day. Note that this is recommended by the Associated Press style guide. Will also reduce duplicate output of month and year in date ranges, when appropriate."),
      '#default_value' => $options['ampm_reduce'],
    ];

    $form['site_time_toggle'] = [
      '#type' => 'checkbox',
      '#return_value' => '1',
      '#title' => $this->t('"Site Time" display'),
      '#description' => $this->t("Show times in the user's preferred timezone (defaults to the site's timezone) in parentheses at end of the value if the timezone is overridden. ex. (12:00pm - 1:00pm UTC)"),
      '#default_value' => $options['site_time_toggle'],
    ];

    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $entity->language()->getId(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (empty($form_state->getValue('time_format')) && empty($form_state->getValue('date_format'))) {
      $form_state->setErrorByName('time_format', $this->t('Please specify either the time format or the date format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Smart date format.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Smart date format.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.smart_date_format.collection');
  }

}
