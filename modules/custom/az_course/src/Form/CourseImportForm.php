<?php

namespace Drupal\az_course\Form;

use Drupal\az_course\CourseMigrateBatchExecutable;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CourseImportForm to compute course links.
 */
class CourseImportForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\migrate\Plugin\MigrationPluginManagerInterface definition.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManagerMigration;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The course search service.
   *
   * @var \Drupal\az_course\CourseSearch
   */
  protected $courseSearch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    $instance->courseSearch = $container->get('az_course.search');
    $instance->cron = $container->get('cron');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'az_course.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'course_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('az_course.settings');
    $default = "";
    if (!empty($config->get('courses'))) {
      $default = implode("\n", $config->get('courses'));
    }

    $form['courses'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Courses to Import'),
      '#description' => $this->t('Courses will be imported when az_courses migration is run either by drush or pushing the "Import Courses" button. List courses to import, one per line, in format "ENGL 101" or "ENGL" for entire subject.'),
      '#required' => FALSE,
      '#default_value' => $default,
      '#resizable' => 'vertical',
      '#rows' => 10,
      '#weight' => '2',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Course List'),
      '#button_type' => 'primary',
      '#weight' => '3',
    ];

    $form['run'] = [
      '#type' => 'submit',
      '#value' => t('Save and Import Courses'),
      '#submit' => ['::runMigrate'],
      '#weight' => '4',
    ];

    $form['rollback'] = [
      '#type' => 'submit',
      '#value' => t('Rollback'),
      '#submit' => ['::rollback'],
      '#weight' => '5',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $courses = $form_state->getValue('courses');

    $courses = preg_split("/[\n\r]+/", $courses);
    if ($courses === FALSE) {
      $form_state->setErrorByName('courses', t('Enter search terms to locate a course to import.'));
    }
    else {
      foreach ($courses as $course) {
        if (empty($course)) {
        }
        elseif (preg_match("/^[[:space:]]*[[:alpha:]]+[[:space:]]+[[:alnum:]]+[[:space:]]*$/", $course)) {
        }
        elseif (preg_match("/^[[:space:]]*[[:alpha:]]+[[:space:]]*$/", $course)) {
        }
        else {
          $form_state->setErrorByName('courses', t('Use format "MATH 123 or MATH" for courses.'));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $courses = $form_state->getValue('courses');
    $courses = preg_split("/[\n\r]+/", $courses);

    $this->config('az_course.settings')
      ->set('courses', $courses)
      ->save();

    drupal_flush_all_caches();
    $this->messenger()->addStatus($this->t('Updated Course Settings.'));
  }

  /**
   * Form submission handler for running migration manually.
   */
  public function runMigrate(array &$form, FormStateInterface $form_state) {

    $courses = $form_state->getValue('courses');
    $courses = preg_split("/[\n\r]+/", $courses);

    $this->config('az_course.settings')
      ->set('courses', $courses)
      ->save();

    $courses = $this->config('az_course.settings')->get('courses');
    $course_urls = [];
    if (!empty($courses)) {
      $matches = [];
      // Convert courses listed into actual URLs to migrate.
      foreach ($courses as $course) {
        if (preg_match("/^[[:space:]]*([[:alpha:]]+)[[:space:]]+([[:alnum:]]+)[[:space:]]*$/", $course, $matches)) {
          $urls = $this->courseSearch->fetchUrls($matches[1], $matches[2]);
          foreach ($urls as $url) {
            $course_urls[] = $url;
          }
        }
        elseif (preg_match("/^[[:space:]]*([[:alpha:]]+)[[:space:]]*$/", $course, $matches)) {
          $options = $this->courseSearch->fetchOptions($matches[1]);
          foreach ($options as $o) {
            $course_urls[] = $o;
          }
        }
      }
    }

    // Pass expected urls to batched executable.
    $migration = $this->pluginManagerMigration->createInstance("az_courses");
    $options = [
      'limit' => 0,
      'update' => 1,
      'force' => 0,
      'configuration' => [
        'source' => [
          'urls' => $course_urls,
        ],
      ],
    ];
    $executable = new CourseMigrateBatchExecutable($migration, new MigrateMessage(), $options);
    $executable->batchImport();
  }

  /**
   * Form submission handler for running rollback.
   */
  public function rollback(array &$form, FormStateInterface $form_state) {

    $migration = $this->pluginManagerMigration->createInstance('az_courses');

    // Reset status.
    $status = $migration->getStatus();
    if ($status !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
    }

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $result = $executable->rollback();

    if ($result === MigrationInterface::RESULT_COMPLETED) {
      $this->messenger()->addStatus($this->t('Rolled back imported courses.'));
    }
    else {
      $this->messenger()->addError($this->t('Failed to roll back imported courses.'));
    }
  }

}
