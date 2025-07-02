<?php

namespace Drupal\smart_date\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Smart date format entity.
 *
 * @ingroup smart_date
 *
 * @ConfigEntityType(
 *   id = "smart_date_format",
 *   label = @Translation("Smart date format"),
 *   handlers = {
 *     "list_builder" = "Drupal\smart_date\Entity\SmartDateFormatListBuilder",
 *     "form" = {
 *       "default" = "Drupal\smart_date\Form\SmartDateFormatForm",
 *       "add" = "Drupal\smart_date\Form\SmartDateFormatForm",
 *       "edit" = "Drupal\smart_date\Form\SmartDateFormatForm",
 *       "delete" = "Drupal\smart_date\Form\SmartDateFormatDeleteForm",
 *     },
 *     "access" = "Drupal\smart_date\Entity\SmartDateFormatAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\smart_date\Entity\SmartDateFormatHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer site configuration",
 *   list_cache_tags = { "rendered" },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/regional/smart-date/{smart_date_format}",
 *     "add-form" = "/admin/config/regional/smart-date/add",
 *     "edit-form" = "/admin/config/regional/smart-date/{smart_date_format}/configure",
 *     "delete-form" = "/admin/config/regional/smart-date/{smart_date_format}/delete",
 *     "collection" = "/admin/config/regional/smart-date",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "date_format",
 *     "time_format",
 *     "time_hour_format",
 *     "allday_label",
 *     "separator",
 *     "join",
 *     "ampm_reduce",
 *     "date_first",
 *     "site_time_toggle",
 *   },
 * )
 */
class SmartDateFormat extends ConfigEntityBase implements SmartDateFormatInterface {

  /**
   * The Smart date format ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Smart date format label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Smart date time format.
   *
   * @var string
   */
  protected $date_format;

  /**
   * The Smart date time format.
   *
   * @var string
   */
  protected $time_format;

  /**
   * The Smart date time format to use for times on the hour.
   *
   * @var string
   */
  protected $time_hour_format;

  /**
   * The Smart date time format label for all day events.
   *
   * @var string
   */
  protected $allday_label;

  /**
   * The Smart date time format separator to use in ranges.
   *
   * @var string
   */
  protected $separator;

  /**
   * The Smart date time format join to use between date and time.
   *
   * @var string
   */
  protected $join;

  /**
   * Whether or not to remove the first am/pm when appropriate.
   *
   * @var bool
   */
  protected $ampm_reduce;

  /**
   * Whether the date or time should be rendered first.
   *
   * @var int
   */
  protected $date_first;

  /**
   * Whether or not to show the time in the site's timezone, if overridden.
   *
   * @var bool
   */
  protected $site_time_toggle;

  /**
   * {@inheritdoc}
   */
  public function getDateFormat() {
    return $this->get('date_format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateFormat($date_format) {
    $this->set('date_format', $date_format);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeFormat() {
    return $this->get('time_format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeFormat($time_format) {
    $this->set('time_format', $time_format);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHourFormat() {
    return $this->get('time_hour_format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeHourFormat($time_hour_format) {
    $this->set('time_hour_format', $time_hour_format);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlldayLabel() {
    return $this->get('allday_label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlldayLabel($allday_label) {
    $this->set('allday_label', $allday_label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeparator() {
    return $this->get('separator')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeparator($separator) {
    $this->set('separator', $separator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJoin() {
    return $this->get('join')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setJoin($join) {
    $this->set('join', $join);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmpmReduce() {
    return $this->get('ampm_reduce')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmpmReduce($ampm_reduce) {
    $this->set('ampm_reduce', $ampm_reduce);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFirst() {
    return $this->get('date_first')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateFirst($date_first) {
    $this->set('date_first', $date_first);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteTimeToggle() {
    return $this->get('site_time_toggle')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSiteTimeToggle($site_time_toggle) {
    $this->set('site_time_toggle', $site_time_toggle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $keys = $this->getAllKeys();
    $values = [];
    foreach ($keys as $key) {
      $values[$key] = $this->get($key);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $values) {
    $keys = $this->getAllKeys();
    foreach ($keys as $key) {
      if (isset($values[$key])) {
        $this->set($key, $values[$key]);
      }
    }
    return $values;
  }

  /**
   * Return an array of the keys used by Smart Date Formats.
   */
  protected function getAllKeys() {
    return [
      'date_format',
      'time_format',
      'time_hour_format',
      'allday_label',
      'separator',
      'join',
      'ampm_reduce',
      'date_first',
      'site_time_toggle',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return ['rendered'];
  }

}
