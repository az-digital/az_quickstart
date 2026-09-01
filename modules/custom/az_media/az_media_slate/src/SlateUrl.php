<?php

declare(strict_types=1);

namespace Drupal\az_media_slate;

/**
 * A Slate form URL that has been validated and rebuilt from its parts.
 *
 * Whatever survives this class becomes a <script src> we inject into a page,
 * so this is the module's security boundary. Nothing else should decide that a
 * stored URL is safe.
 *
 * Two URLs come out of one pasted link, and they are not interchangeable:
 *
 * - The canonical URL, https://<host>/register/?id=<guid>, is what a person
 *   opens in a browser. It is the href of the fallback link.
 * - The embed URL adds output=embed and div=<container id>. Slate answers it
 *   with JavaScript, so using it as a link href would show or download a
 *   script file instead of the form.
 *
 * Only getEmbedUrl() can produce the second one, and it needs a container id
 * to do it, which is what keeps the two apart.
 *
 * @see https://knowledge.technolutions.net/docs/embedding-forms
 */
final class SlateUrl {

  /**
   * Slate instances live under this domain. Checked as a suffix, not a match.
   */
  private const HOST_SUFFIX = '.technolutions.net';

  /**
   * Every Slate form is served from this path.
   */
  private const PATH = '/register/';

  /**
   * The form id, shaped like a GUID.
   *
   * This checks grouping only - not UUID version, variant, or that the value
   * is non-zero. Slate's ids have not been confirmed to be RFC 4122 valid, so
   * tightening this risks rejecting a real form. The host and path checks are
   * what actually constrain what we will load.
   */
  private const ID_PATTERN = '/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i';

  /**
   * Prefill keys Slate documents, e.g. form_sys:first or form_myfield.
   *
   * Slate requires parameter keys to be lowercase; values may be mixed case.
   *
   * @see https://knowledge.technolutions.net/docs/prepopulating-or-prefilling-forms-using-query-string-parameters
   */
  private const PREFILL_PATTERN = '/^form_[a-z0-9_:.-]+$/';

  /**
   * Query keys we set ourselves, so a pasted copy of them is dropped.
   */
  private const RESERVED_KEYS = ['output', 'div'];

  /**
   * Caps on a single query key and value, to keep the generated URL sane.
   */
  private const MAX_KEY_LENGTH = 64;
  private const MAX_VALUE_LENGTH = 512;

  /**
   * The form id from the pasted URL.
   */
  private string $id;

  /**
   * The scheme and host, lowercased, e.g. https://uaz.technolutions.net.
   */
  private string $origin;

  /**
   * Prefill parameters that survived the allowlist, as key => value.
   */
  private array $prefill;

  private function __construct(string $origin, string $id, array $prefill) {
    $this->origin = $origin;
    $this->id = $id;
    $this->prefill = $prefill;
  }

  /**
   * Validates a pasted Slate link and rebuilds it from its parts.
   *
   * @param string $url
   *   The URL as an editor typed or pasted it.
   * @param string|null $reason
   *   Set to a short machine-readable reason when the URL is rejected, for
   *   logging. Never contains any part of the URL - a rejected URL can carry
   *   anything, including personal data, and watchdog is not the place for it.
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

    // Lowercase the scheme and host before comparing. The regex media_remote
    // validates against is case-insensitive, so without this a mixed-case host
    // would pass on save and then be rejected here at render time.
    $scheme = strtolower($parts['scheme']);
    $host = strtolower($parts['host']);

    if ($scheme !== 'https') {
      $reason = 'bad_scheme';
      return NULL;
    }
    // A URL carrying credentials, a port, or a fragment is not a share link an
    // editor would get from Slate, so treat any of them as a rejection rather
    // than stripping them and loading something close to what was pasted.
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
    if ($parts['path'] !== self::PATH) {
      $reason = 'bad_path';
      return NULL;
    }

    // Split the query by hand rather than with parse_str(). parse_str()
    // rewrites "." and " " in a key to "_", left over from register_globals,
    // so a prefill key like form_sys.first would silently become
    // form_sys_first and the field would quietly not prefill.
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

    if (!isset($pairs['id'])) {
      $reason = 'missing_id';
      return NULL;
    }
    if (!preg_match(self::ID_PATTERN, $pairs['id'])) {
      $reason = 'bad_id';
      return NULL;
    }

    $prefill = [];
    foreach ($pairs as $key => $value) {
      if ($key === 'id' || in_array($key, self::RESERVED_KEYS, TRUE)) {
        continue;
      }
      // If a person parameter is present, reject the whole URL. Rationale: one
      // stored URL serves every visitor to the page, and person=<guid> tells
      // Slate to prefill that specific record's data and to route submissions
      // onto it. So a person parameter here would show one applicant's details
      // to everyone and file all their answers against that one record.
      if ($key === 'person') {
        $reason = 'person_param';
        return NULL;
      }
      // Allow the documented prefill keys and nothing else. Slate's parameter
      // set is not published beyond the prefill page, and an unrecognised one
      // could change rendering, redirects, or how a submission is recorded.
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

    return new self($scheme . '://' . $host, $pairs['id'], $prefill);
  }

  /**
   * The URL a person can open in a browser. Safe to use as a link href.
   */
  public function getCanonicalUrl(): string {
    return $this->origin . self::PATH . '?' . http_build_query(
      ['id' => $this->id] + $this->prefill
    );
  }

  /**
   * The URL Slate answers with JavaScript. Only ever a script src.
   *
   * @param string $container_id
   *   The id of the element Slate should inject the form into. Slate echoes
   *   this back inside the script it returns, so it has to match the container
   *   we render.
   */
  public function getEmbedUrl(string $container_id): string {
    return $this->origin . self::PATH . '?' . http_build_query(
      ['id' => $this->id] + $this->prefill + [
        'output' => 'embed',
        'div' => $container_id,
      ]
    );
  }

  /**
   * The form id, used to build a container id that survives caching.
   */
  public function getId(): string {
    return $this->id;
  }

}
