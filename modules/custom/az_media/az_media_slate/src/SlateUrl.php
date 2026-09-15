<?php

declare(strict_types=1);

namespace Drupal\az_media_slate;

/**
 * A Slate form URL that has been checked and rebuilt from its parts.
 *
 * Whatever passes this class ends up as the src of a script tag on a page, so
 * this is the module's security boundary.
 *
 * Before a stored URL goes into a script tag, run it through parse(), even
 * though the save-time regex already checked it. Rationale: that check can be
 * skipped. For example, a migration saves media without validating it unless
 * told to.
 *
 * One pasted link gives two URLs, and they aren't interchangeable:
 * - The canonical URL is the page a person opens in a browser, such as
 *   https://<host>/register/?id=<guid> plus any prefill. It's the fallback
 *   link's href.
 * - The embed URL adds output=embed and div=<container id>. Slate answers it
 *   with JavaScript, so as a link href it would show or download a script
 *   instead of the form.
 *
 * Only getEmbedUrl() builds the embed URL, and it needs a container id to do
 * it, which keeps the two from getting mixed up.
 *
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */
final class SlateUrl {

  /**
   * The domain Slate's hosted sites live under, checked as a suffix.
   *
   * For example, uaz.test.technolutions.net passes. The leading dot matters:
   * eviltechnolutions.net ends in the same letters but fails. Keep this in step
   * with SLATE_HOST_SUFFIX in js/az-media-slate.js.
   */
  private const HOST_SUFFIX = '.technolutions.net';

  /**
   * The path every Slate form link starts with.
   */
  private const PATH = '/register/';

  /**
   * The paths we accept: /register/, optionally followed by a form's name.
   *
   * Slate links to a form by id, as in /register/?id=<guid>, or by name, as in
   * /register/moreinfo (Slate's prefill docs show links like that). The name
   * has to be one word of letters, digits, hyphens, or underscores. Rationale:
   * the name goes back into the script src we build, and a browser resolves
   * /register/../manage to /manage, a different page on Slate's site.
   */
  private const PATH_PATTERN = '#^/register/(?:([A-Za-z0-9_-]+)/?)?$#';

  /**
   * The form id, shaped like a GUID.
   *
   * This checks the grouping only (8-4-4-4-12 hex digits), not the UUID version
   * or variant. Rationale: nobody has confirmed that Slate's ids follow
   * RFC 4122, the UUID spec, so a stricter check could reject a real form. The
   * host and path checks are what limit what we'll load.
   */
  private const ID_PATTERN = '/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i';

  /**
   * The shape of a prefill key: a form field's export key, all lowercase.
   *
   * An export key is the name Slate gives each field. Slate prefills a field
   * when the query string names its export key, for example
   * sys:first=Alexander or lunch_preference=Chicken. There's no prefix. Slate
   * requires keys to be lowercase; values can be any case.
   *
   * This checks the shape, not a list of names. Rationale: each form defines
   * its own export keys inside Slate, so we can't know them here. That means
   * an unknown Slate parameter passes too. It only travels to Slate in the
   * query string. The scheme, host, and path checks decide whose script we
   * load, and person is refused by name in parse().
   *
   * @see https://knowledge.technolutions.net/docs/prepopulating-or-prefilling-forms-using-query-string-parameters
   */
  private const PREFILL_PATTERN = '/^[a-z0-9_:.-]+$/';

  /**
   * Query keys we set ourselves. A pasted copy is dropped, so ours wins.
   */
  private const RESERVED_KEYS = ['output', 'div'];

  /**
   * Length limits for one query key and one value.
   *
   * Both are far above the examples in Slate's prefill docs. They stop a
   * pasted URL from growing without limit.
   */
  private const MAX_KEY_LENGTH = 64;
  private const MAX_VALUE_LENGTH = 512;

  /**
   * The form id from the pasted URL, or NULL for a link by name.
   */
  private ?string $id;

  /**
   * The form's name from the path, such as moreinfo, or NULL if there's none.
   */
  private ?string $name;

  /**
   * The scheme and host, lowercased, e.g. https://uaz.technolutions.net.
   */
  private string $origin;

  /**
   * Prefill parameters that passed the checks, as key => value.
   */
  private array $prefill;

  private function __construct(string $origin, ?string $id, ?string $name, array $prefill) {
    $this->origin = $origin;
    $this->id = $id;
    $this->name = $name;
    $this->prefill = $prefill;
  }

  /**
   * Validates a pasted Slate link and rebuilds it from its parts.
   *
   * @param string $url
   *   The URL as an editor typed or pasted it.
   * @param string|null $reason
   *   Set to a short reason when the URL is rejected, such as 'bad_host', for
   *   the log. It never includes any part of the URL, because a rejected URL
   *   can hold anything, including someone's personal data.
   *
   * @return self|null
   *   The parsed URL, or NULL if it was rejected.
   */
  public static function parse(string $url, ?string &$reason = NULL): ?self {
    $reason = NULL;
    $url = trim($url);
    if ($url === '') {
      $reason = 'empty';
      return NULL;
    }

    $parts = parse_url($url);
    if ($parts === FALSE || !isset($parts['scheme'], $parts['host'], $parts['path'])) {
      $reason = 'unparseable';
      return NULL;
    }

    // Lowercase the scheme and host before comparing. Rationale: the save-time
    // regex in AzMediaRemoteSlateFormatter ignores case there, so
    // HTTPS://UAZ.Technolutions.NET saves fine. Without this, that URL would
    // then be rejected here, at render.
    $scheme = strtolower($parts['scheme']);
    $host = strtolower($parts['host']);

    if ($scheme !== 'https') {
      $reason = 'bad_scheme';
      return NULL;
    }
    // If the URL has credentials, a port, or a fragment, reject it. Rationale:
    // a share link from Slate has none of those. Don't strip them and carry on,
    // or we'd load something other than what the editor pasted.
    if (isset($parts['user']) || isset($parts['pass'])) {
      $reason = 'has_userinfo';
      return NULL;
    }
    if (isset($parts['port'])) {
      $reason = 'has_port';
      return NULL;
    }
    if (isset($parts['fragment'])) {
      $reason = 'has_fragment';
      return NULL;
    }
    if (!str_ends_with($host, self::HOST_SUFFIX)) {
      $reason = 'bad_host';
      return NULL;
    }
    if (!preg_match(self::PATH_PATTERN, $parts['path'], $path_match)) {
      $reason = 'bad_path';
      return NULL;
    }
    $name = $path_match[1] ?? NULL;

    // Split the query string by hand. Don't use parse_str() here. For example,
    // parse_str() turns the key my.field into my_field, a leftover from PHP's
    // old register_globals, so that field would quietly never prefill.
    $pairs = [];
    if (isset($parts['query']) && $parts['query'] !== '') {
      foreach (explode('&', $parts['query']) as $pair) {
        if ($pair === '') {
          continue;
        }
        $split = strpos($pair, '=');
        if ($split === FALSE) {
          $reason = 'unknown_param';
          return NULL;
        }
        $pairs[urldecode(substr($pair, 0, $split))] = urldecode(substr($pair, $split + 1));
      }
    }

    // If there's no id, the path has to name the form instead. For example,
    // /register/moreinfo is enough on its own, but /register/ names nothing.
    if (isset($pairs['id'])) {
      if (!preg_match(self::ID_PATTERN, $pairs['id'])) {
        $reason = 'bad_id';
        return NULL;
      }
    }
    elseif ($name === NULL) {
      $reason = 'missing_id';
      return NULL;
    }

    $prefill = [];
    foreach ($pairs as $key => $value) {
      if ($key === 'id' || in_array($key, self::RESERVED_KEYS, TRUE)) {
        continue;
      }
      // If a person parameter is present, reject the whole URL. Rationale: one
      // stored URL serves every visitor, and person=<guid> tells Slate to fill
      // the form with that record's details and update that record on submit.
      // So every visitor would see one applicant's details, and every
      // submission would land on that one record.
      if ($key === 'person') {
        $reason = 'person_param';
        return NULL;
      }
      // Any other key passes if it has the shape of an export key. See
      // PREFILL_PATTERN.
      if (!preg_match(self::PREFILL_PATTERN, $key)) {
        $reason = 'unknown_param';
        return NULL;
      }
      if (strlen($key) > self::MAX_KEY_LENGTH || strlen($value) > self::MAX_VALUE_LENGTH) {
        $reason = 'param_too_long';
        return NULL;
      }
      $prefill[$key] = $value;
    }

    return new self($scheme . '://' . $host, $pairs['id'] ?? NULL, $name, $prefill);
  }

  /**
   * The URL a person can open in a browser. Safe to use as a link href.
   */
  public function getCanonicalUrl(): string {
    return $this->buildUrl($this->prefill);
  }

  /**
   * The URL Slate answers with JavaScript. Use it only as a script src.
   *
   * @param string $container_id
   *   The id of the element Slate should write the form into. Slate's script
   *   looks this id up with document.getElementById(), so it must match the
   *   container we render.
   */
  public function getEmbedUrl(string $container_id): string {
    return $this->buildUrl($this->prefill + [
      'output' => 'embed',
      'div' => $container_id,
    ]);
  }

  /**
   * Builds a URL to this form with the given query parameters.
   *
   * A link with an id always comes out as /register/?id=<guid>, even when it
   * was pasted as /register/form?id=<guid>. Slate serves the form by id on
   * either path, and /register/?id= is what Slate's own embed code uses. A
   * link by name keeps its name, as in /register/moreinfo.
   *
   * @param array $query
   *   Query parameters to add after the id, as key => value.
   */
  private function buildUrl(array $query): string {
    if ($this->id !== NULL) {
      $path = self::PATH;
      $query = ['id' => $this->id] + $query;
    }
    else {
      $path = self::PATH . $this->name;
    }
    $query_string = http_build_query($query);
    return $this->origin . $path . ($query_string === '' ? '' : '?' . $query_string);
  }

}
