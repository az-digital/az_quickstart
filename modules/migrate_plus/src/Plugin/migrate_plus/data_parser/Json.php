<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "json",
 *   title = @Translation("JSON")
 * )
 */
class Json extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Iterator over the JSON data.
   */
  protected ?\ArrayIterator $iterator = NULL;

  /**
   * The currently saved source url (as a string).
   *
   * @var string
   */
  protected $currentUrl;

  /**
   * The active url's source data.
   *
   * @var array
   */
  protected $sourceData;

  /**
   * Retrieves the JSON data and returns it as an array.
   *
   * @param string $url
   *   URL of a JSON feed.
   * @param string|int $item_selector
   *   Selector within the data content at which useful data is found.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getSourceData(string $url, string|int $item_selector = '') {
    // Use cached source data if this is the first request or URL is same as the
    // last time we made the request.
    if ($this->currentUrl != $url || !$this->sourceData) {
      $response = $this->getDataFetcherPlugin()->getResponseContent($url);

      // Convert objects to associative arrays.
      $this->sourceData = json_decode($response, TRUE);

      // If json_decode() has returned NULL, it might be that the data isn't
      // valid utf8 - see http://php.net/manual/en/function.json-decode.php#86997.
      if (!$this->sourceData) {
      $utf8response = mb_convert_encoding($response, 'UTF-8');
        $this->sourceData = json_decode($utf8response, TRUE);
      }
      $this->currentUrl = $url;
    }

    // Backwards-compatibility for depth selection.
    if (is_numeric($this->itemSelector)) {
      return $this->selectByDepth($this->sourceData, (int) $item_selector);
    }

    // If the item_selector is an empty string, return all.
    if ($item_selector === '') {
      return $this->sourceData;
    }

    // Otherwise, we're using xpath-like selectors.
    $selectors = explode('/', trim($item_selector, '/'));
    $return = $this->sourceData;
    foreach ($selectors as $selector) {
      // If the item_selector is missing, return an empty array.
      if (!isset($return[$selector])) {
        return [];
      }
      $return = $return[$selector];
    }
    return $return;
  }

  /**
   * Get the source data for reading.
   *
   * @param array $raw_data
   *   Raw data from the JSON feed.
   * @param int $item_selector
   *   Depth within the data content at which useful data is found.
   *
   *   Selected items at the requested depth of the JSON feed.
   */
  protected function selectByDepth(array $raw_data, int $item_selector = 0): array {
    // Return the results in a recursive iterator that can traverse
    // multidimensional arrays.
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($raw_data),
      \RecursiveIteratorIterator::SELF_FIRST);
    $items = [];
    // Backwards-compatibility - an integer item_selector is interpreted as a
    // depth. When there is an array of items at the expected depth, pull that
    // array out as a distinct item.
    $identifierDepth = $item_selector;
    $iterator->rewind();
    while ($iterator->valid()) {
      $item = $iterator->current();
      if (is_array($item) && $iterator->getDepth() === $identifierDepth) {
        $items[] = $item;
      }
      $iterator->next();
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl(string $url): bool {
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url, $this->itemSelector);
    // Ensure there is source data at the current url.
    if (is_null($source_data)) {
      return FALSE;
    }
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    $current = $this->iterator->current();
    if (is_array($current)) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $field_data = $current;
        $field_selectors = explode('/', trim((string) $selector, '/'));
        foreach ($field_selectors as $field_selector) {
          if (is_array($field_data) && array_key_exists($field_selector, $field_data)) {
            $field_data = $field_data[$field_selector];
          }
          else {
            $field_data = '';
          }
        }
        $this->currentItem[$field_name] = $field_data;
      }
      if (!empty($this->configuration['include_raw_data'])) {
        $this->currentItem['raw'] = $current;
      }
      $this->iterator->next();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getNextUrls(string $url): array {
    $next_urls = [];

    // If a pager selector is provided, get the data from the source.
    $selector_data = NULL;
    if (!empty($this->configuration['pager']['selector'])) {
      $selector_data = $this->getSourceData($url, $this->configuration['pager']['selector']);
    }

    // Logic for each type of pager.
    switch ($this->configuration['pager']['type']) {
      case 'urls':
        if (NULL !== $selector_data) {
          if (is_array($selector_data)) {
            $next_urls = $selector_data;
          }
          elseif (filter_var($selector_data, FILTER_VALIDATE_URL)) {
            $next_urls[] = $selector_data;
          }
        }
        break;

      case 'cursor':
        if (NULL !== $selector_data && is_scalar($selector_data)) {
          // Just use 'cursor' as a default parameter key if not provided.
          $key = !empty($this->configuration['pager']['key']) ? $this->configuration['pager']['key'] : 'cursor';
          // Parse the url and replace the cursor param value and rebuild the url.
          $path = UrlHelper::parse($url);
          $path['query'][$key] = $selector_data;
          $next_urls[] = Url::fromUri($path['path'], [
            'query' => $path['query'],
            'fragment' => $path['fragment'],
          ])->toString();
        }
        break;

      case 'page':
        if (NULL !== $selector_data && is_scalar($selector_data)) {
          // Just use 'page' as a default parameter key if not provided.
          $key = !empty($this->configuration['pager']['key']) ? $this->configuration['pager']['key'] : 'page';
          // Define the max page to generate.
          $max = $selector_data + 1;
          if (!empty($this->configuration['pager']['selector_max'])) {
            $max = $this->getSourceData($url, $this->configuration['pager']['selector_max']);
          }

          // Parse the url and replace the page param value and rebuild the url.
          $path = UrlHelper::parse($url);
          for ($page = $selector_data + 1; $page < $max; ++$page) {
            $path['query'][$key] = $page;
            $next_urls[] = Url::fromUri($path['path'], [
              'query' => $path['query'],
              'fragment' => $path['fragment'],
            ])->toString();
          }
        }
        break;

      case 'paginator':
        // The first pass uses the endpoint's default size.
        // @todo Handle first URL set page size on first pass.
        if (!isset($this->configuration['pager']['default_num_items'])) {
          throw new MigrateException('Pager "default_num_items" must be configured.');
        }
        $num_items = $this->configuration['pager']['default_num_items'];

        // Use 'page' as a default page parameter key if not provided.
        $page_key = !empty($this->configuration['pager']['page_key']) ? $this->configuration['pager']['page_key'] : 'page';

        // Set default paginator type.
        $paginator_type_options = ['page_number', 'starting_item'];
        $paginator_type = $paginator_type_options[0];
        // Check configured paginator type.
        if (!empty($this->configuration['pager']['paginator_type'])) {
          if (!in_array($this->configuration['pager']['paginator_type'], $paginator_type_options)) {
            // Not set to one of the two available options.
            throw new MigrateException(
              'Pager "paginator_type" must be configured as either "page_number" or "starting_item" ("page_number" is default).'
            );
          }
          $paginator_type = $this->configuration['pager']['paginator_type'];
        }

        // Use 'pagesize' as a default page parameter key if not provided.
        $size_key = !empty($this->configuration['pager']['size_key']) ? $this->configuration['pager']['size_key'] : 'pagesize';

        // Parse the url.
        $path = UrlHelper::parse($url);

        $curr_page = !empty($path['query'][$page_key]) ? $path['query'][$page_key] : 0;

        // @todo Use core's QueryBase and pager.
        // @see contrib module external_entities \Entity\Query\External\Query.php for example.
        $next_start = $curr_page + $num_items;
        $next_end = $num_items;
        // Use "page_number" when the pager uses page numbers to determine
        // the item to start at, use "starting_item" when the pager uses the
        // item number to start at.
        if ($paginator_type === 'page_number') {
          $next_start = $curr_page + 1;
        }

        // Replace the paginator param value.
        $path['query'][$page_key] = $next_start;
        // Replace the size param value.
        $path['query'][$size_key] = $next_end;

        // If we have a selector that tells us the number of rows returned in
        // the current request, use that to decide if we should add the next
        // url to the array.
        if (NULL !== $selector_data) {
          if (is_scalar($selector_data)) {
            // If we have a numeric number of rows and the current page is still
            // a full page (i.e. the number of items, $selector_data, in this
            // page equals the number of items configured, $num_items), advance
            // to the next page.
            if ($selector_data == $num_items) {
              $next_urls[] = Url::fromUri($path['path'], [
                'query' => $path['query'],
                'fragment' => $path['fragment'],
              ])->toString();
            }
          }
          else {
            // If we have an array of rows
            if (count($selector_data) > 0) {
              $next_urls[] = Url::fromUri($path['path'], [
                'query' => $path['query'],
                'fragment' => $path['fragment'],
              ])->toString();
            }
          }
        }
        else {
          // Rebuild the url.
          $next_urls[] = Url::fromUri($path['path'], [
            'query' => $path['query'],
            'fragment' => $path['fragment'],
          ])->toString();

          // Service may return 404 for last page, ensure next_urls are valid.
          foreach ($next_urls as $key => $next_url) {
            try {
              $response = $this->getDataFetcherPlugin()->getResponse($next_url);
              if ($response->getStatusCode() !== 200) {
                unset($next_urls[$key]);
              }
            }
            catch (\Exception $e) {
              unset($next_urls[$key]);
            }
          }
        }
        break;
    }

    return array_merge(parent::getNextUrls($url), $next_urls);
  }


}
