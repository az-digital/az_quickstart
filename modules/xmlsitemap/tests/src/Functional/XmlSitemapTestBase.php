<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\xmlsitemap\Entity\XmlSitemap;

/**
 * Helper test class with some added functions for testing.
 */
abstract class XmlSitemapTestBase extends BrowserTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'system', 'user', 'xmlsitemap'];

  /**
   * The admin user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   *
   * @codingStandardsIgnoreStart
   */
  protected $admin_user;

  /**
   * The normal user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $normal_user;
  // @codingStandardsIgnoreEnd

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The xmlsitemap.settings configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager object.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * System time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->state = $this->container->get('state');
    $this->config = $this->container->get('config.factory')->getEditable('xmlsitemap.settings');
    $this->moduleHandler = $this->container->get('module_handler');
    $this->languageManager = $this->container->get('language_manager');
    $this->linkStorage = $this->container->get('xmlsitemap.link_storage');
    $this->time = $this->container->get('datetime.time');

    // Create the Article and Page content types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'settings' => [
          // Set proper default options for the page content type.
          'node' => [
            'options' => ['promote' => FALSE],
            'submitted' => FALSE,
          ],
        ],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Capture any (remaining) watchdog errors.
    $this->assertNoWatchdogErrors();

    parent::tearDown();
  }

  /**
   * Assert the page does not respond with the specified response code.
   *
   * @param string $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param string $message
   *   Message to display.
   * @param string $group
   *   Name of the group.
   *
   * @return mixed
   *   Assertion result.
   */
  protected function assertNoResponse($code, $message = '', $group = 'Browser') {
    $curl_code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertFalse($match, $message ? $message : t('HTTP response not expected @code, actual @curl_code', ['@code' => $code, '@curl_code' => $curl_code]), t('Browser'));
  }

  /**
   * Retrieves an XML Sitemap.
   *
   * @param array $context
   *   An optional array of the XML Sitemap's context.
   * @param array $options
   *   Options to be forwarded to Url::fromUri(). These values will be merged
   *   with, but always override $sitemap->uri['options'].
   * @param array $headers
   *   An array containing additional HTTP request headers, each formatted as
   *   "name: value".
   *
   * @return string
   *   The retrieved HTML string, also available as $this->drupalGetContent()
   */
  protected function drupalGetSitemap(array $context = [], array $options = [], array $headers = []) {
    $sitemap = XmlSitemap::loadByContext($context);
    if (!$sitemap) {
      return $this->fail('Could not load sitemap by context.');
    }
    $uri = xmlsitemap_sitemap_uri($sitemap);
    return $this->drupalGet($uri['path'], $options + $uri['options'], $headers);
  }

  /**
   * Regenerate the sitemap by setting the regenerate flag and running cron.
   */
  protected function regenerateSitemap() {
    $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    $this->state->set('xmlsitemap_generated_last', 0);
    $this->cronRun();
    $this->assertTrue($this->state->get('xmlsitemap_generated_last') && !$this->state->get('xmlsitemap_regenerate_needed'), t('XML Sitemaps regenerated and flag cleared.'));
  }

  /**
   * Assert Sitemap Link.
   */
  protected function assertSitemapLink($entity_type, $entity_id = NULL) {
    if (is_array($entity_type)) {
      $links = $this->linkStorage->loadMultiple($entity_type);
      $link = $links ? reset($links) : FALSE;
    }
    else {
      $link = $this->linkStorage->load($entity_type, $entity_id);
    }
    $this->assertIsArray($link, 'Link loaded.');
    return $link;
  }

  /**
   * Assert No Sitemap Link.
   */
  protected function assertNoSitemapLink($entity_type, $entity_id = NULL) {
    if (is_array($entity_type)) {
      $links = $this->linkStorage->loadMultiple($entity_type);
      $link = $links ? reset($links) : FALSE;
    }
    else {
      $link = $this->linkStorage->load($entity_type, $entity_id);
    }
    $this->assertFalse($link, 'Link not loaded.');
    return $link;
  }

  /**
   * Assert Sitemap Link Visible.
   */
  protected function assertSitemapLinkVisible($entity_type, $entity_id) {
    $link = $this->linkStorage->load($entity_type, $entity_id);
    $this->assertTrue($link && $link['access'] && $link['status'], t('Sitemap link @type @id is visible.', ['@type' => $entity_type, '@id' => $entity_id]));
  }

  /**
   * Assert Sitemap Link Not Visible.
   */
  protected function assertSitemapLinkNotVisible($entity_type, $entity_id) {
    $link = $this->linkStorage->load($entity_type, $entity_id);
    $this->assertTrue($link && !($link['access'] && $link['status']), t('Sitemap link @type @id is not visible.', ['@type' => $entity_type, '@id' => $entity_id]));
  }

  /**
   * Assert Sitemap Link Values.
   */
  protected function assertSitemapLinkValues($entity_type, $entity_id, array $conditions) {
    $link = $this->linkStorage->load($entity_type, $entity_id);

    if (!$link) {
      return $this->fail(sprintf('Could not load sitemap link for %s %s.', $entity_type, $entity_id));
    }

    foreach ($conditions as $key => $value) {
      if ($value === NULL || $link[$key] === NULL) {
        // For nullable fields, always check for identical values (===).
        $this->assertSame($value, $link[$key], t('Identical values for @type @id link field @key.', [
          '@type' => $entity_type,
          '@id' => $entity_id,
          '@key' => $key,
        ]));
      }
      else {
        // Otherwise check simple equality (==).
        $this->assertEquals($value, $link[$key], t('Equal values for @type @id link field @key - @a - @b.', [
          '@type' => $entity_type,
          '@id' => $entity_id,
          '@key' => $key,
          '@a' => $link[$key],
          '@b' => $value,
        ]));
      }
    }
  }

  /**
   * Assert Not Sitemap Link Values.
   */
  protected function assertNotSitemapLinkValues($entity_type, $entity_id, array $conditions) {
    $link = $this->linkStorage->load($entity_type, $entity_id);

    if (!$link) {
      return $this->fail(sprintf('Could not load sitemap link for %s %s.', $entity_type, $entity_id));
    }

    foreach ($conditions as $key => $value) {
      if ($value === NULL || $link[$key] === NULL) {
        // For nullable fields, always check for identical values (===).
        $this->assertNotSame($value, $link[$key], t('Not identical values for @type @id link field @key.', [
          '@type' => $entity_type,
          '@id' => $entity_id,
          '@key' => $key,
        ]));
      }
      else {
        // Otherwise check simple equality (==).
        $this->assertNotEquals($value, $link[$key], t('Not equal values for link @type @id field @key.', [
          '@type' => $entity_type,
          '@id' => $entity_id,
          '@key' => $key,
        ]));
      }
    }
  }

  /**
   * Assert Raw Sitemap Links.
   */
  protected function assertRawSitemapLinks() {
    $links = func_get_args();
    foreach ($links as $link) {
      $path = Url::fromUri('internal:' . $link['loc'], ['language' => xmlsitemap_language_load($link['language']), 'absolute' => TRUE])->toString();
      $this->assertSession()->responseContains($link['loc']);
    }
  }

  /**
   * Assert No Raw Sitemap Links.
   */
  protected function assertNoRawSitemapLinks() {
    $links = func_get_args();
    foreach ($links as $link) {
      $path = Url::fromUri('internal:' . $link['loc'], ['language' => xmlsitemap_language_load($link['language']), 'absolute' => TRUE])->toString();
      $this->assertSession()->responseNotContains($link['loc']);
    }
  }

  /**
   * Add Sitemap Link.
   */
  protected function addSitemapLink(array $link = []) {
    $last_id = &drupal_static(__FUNCTION__, 1);

    $link += [
      'type' => 'testing',
      'subtype' => '',
      'id' => $last_id,
      'access' => 1,
      'status' => 1,
    ];

    // Make the default path easier to read than a random string.
    $link += ['loc' => '/' . $link['type'] . '-' . $link['id']];

    $last_id = max($last_id, $link['id']) + 1;
    $this->linkStorage->save($link);
    return $link;
  }

  /**
   * Assert Flag.
   */
  protected function assertFlag($variable, $assert_value = TRUE, $reset_if_true = TRUE) {
    $value = xmlsitemap_var($variable);

    if ($reset_if_true && $value) {
      $state_variables = xmlsitemap_state_variables();
      if (isset($state_variables[$variable])) {
        $this->state->set($variable, FALSE);
      }
      else {
        $this->config->set($variable, FALSE)->save();
      }
    }
    $this->assertEquals($assert_value, $value, "$variable is " . ($assert_value ? 'TRUE' : 'FALSE'));
    return $value == $assert_value;
  }

  /**
   * Assert XML Sitemap Problems.
   *
   * @codingStandardsIgnoreStart
   */
  protected function assertXMLSitemapProblems($problem_text = FALSE) {
    // @codingStandardsIgnoreEnd
    $this->drupalGet('admin/config/search/xmlsitemap');
    $this->assertSession()->pageTextContains('One or more problems were detected with your XML Sitemap configuration');
    if ($problem_text) {
      $this->clickLink('status report');
      $this->assertSession()->pageTextContains($problem_text);
    }
  }

  /**
   * Assert No XML Sitemap Problems.
   *
   * @codingStandardsIgnoreStart
   */
  protected function assertNoXMLSitemapProblems() {
    // @codingStandardsIgnoreEnd
    $this->drupalGet('admin/config/search/xmlsitemap');
    $this->assertSession()->pageTextNotContains('One or more problems were detected with your XML Sitemap configuration');
  }

  /**
   * Fetch all seen watchdog messages.
   *
   * @todo Add unit tests for this function.
   */
  protected function getWatchdogMessages(array $conditions = [], $reset = FALSE) {
    static $seen_ids = [];

    if (!$this->moduleHandler->moduleExists('dblog') || $reset) {
      $seen_ids = [];
      return [];
    }

    $query = \Drupal::database()->select('watchdog');
    $query->fields('watchdog', [
      'wid',
      'type',
      'severity',
      'message',
      'variables',
      'timestamp',
    ]);
    foreach ($conditions as $field => $value) {
      if ($field == 'variables' && !is_string($value)) {
        $value = serialize($value);
      }
      $query->condition($field, $value);
    }
    if ($seen_ids) {
      $query->condition('wid', $seen_ids, 'NOT IN');
    }
    $query->orderBy('timestamp');
    $messages = $query->execute()->fetchAllAssoc('wid');

    $seen_ids = array_merge($seen_ids, array_keys($messages));
    return $messages;
  }

  /**
   * Assert Watchdog Message.
   */
  protected function assertWatchdogMessage(array $conditions, $message = 'Watchdog message found.') {
    $this->assertNotEmpty($this->getWatchdogMessages($conditions), $message);
  }

  /**
   * Assert No Watchdog Message.
   */
  protected function assertNoWatchdogMessage(array $conditions, $message = 'Watchdog message not found.') {
    $this->assertEmpty($this->getWatchdogMessages($conditions), $message);
  }

  /**
   * Check that there were no watchdog errors or worse.
   */
  protected function assertNoWatchdogErrors() {
    $messages = $this->getWatchdogMessages();

    foreach ($messages as $message) {
      $message->text = $this->formatWatchdogMessage($message);
      if (in_array($message->severity, [
        RfcLogLevel::EMERGENCY, RfcLogLevel::ALERT, RfcLogLevel::CRITICAL, RfcLogLevel::ERROR, RfcLogLevel::WARNING,
      ])) {
        $this->fail($message->text);
      }
    }

    // Clear the seen watchdog messages since we've failed on any errors.
    $this->getWatchdogMessages([], TRUE);
  }

  /**
   * Format a watchdog message in a one-line summary.
   *
   * @param string $message
   *   A watchdog message object.
   *
   * @return string
   *   A string containing the watchdog message's timestamp, severity, type,
   *   and actual message.
   */
  private function formatWatchdogMessage($message) {
    static $levels;

    if (!isset($levels)) {
      $levels = RfcLogLevel::getLevels();
    }

    return t('@timestamp - @severity - @type - @message', [
      '@timestamp' => $message->timestamp,
      '@severity' => $levels[$message->severity],
      '@type' => $message->type,
        // @codingStandardsIgnoreLine
        // '@message' => theme_dblog_message(array('event' => $message, 'link' => FALSE)),.
    ]);
  }

}
