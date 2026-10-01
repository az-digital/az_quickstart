<?php

declare(strict_types=1);

namespace Drupal\az_form_embed_trellis;

/**
 * A Trellis form URL that has been checked and rebuilt from its parts.
 *
 * Whatever passes this class ends up as the src of a script tag on a page, so
 * this is the module's security boundary.
 *
 * Trellis forms are FormAssembly forms. One pasted link gives two URLs, and
 * they aren't interchangeable:
 * - The canonical URL is the page a person opens in a browser, such as
 *   https://forms-a.trellis.arizona.edu/185 plus any prefill. It's the
 *   fallback link's href.
 * - The embed URL is FormAssembly's Quick Publish address, with /publish/
 *   before the form's number. FormAssembly answers it with JavaScript, so as
 *   a link href it would show a script instead of the form.
 *
 * @see https://help.formassembly.com/help/javascript-form-publishing
 */
final class TrellisUrl {

  /**
   * The hosts Trellis forms are served from, matched exactly.
   */
  private const HOSTS = [
    'forms-a.trellis.arizona.edu',
    'trellis.tfaforms.net',
  ];

  /**
   * Paths that give a form's number, captured as the first group.
   *
   * FormAssembly links to a form as /185 or /forms/view/185. A
   * /forms/legacyView/185/... link is the page for reviewing a submitted
   * response, which still names its form.
   */
  private const FORM_PATTERNS = [
    '#^/([0-9]{1,10})/?$#',
    '#^/forms/view/([0-9]{1,10})/?$#',
    '#^/forms/legacyView/([0-9]{1,10})(?:/[A-Za-z0-9]+)*/?$#',
  ];

  /**
   * A path that names a form instead of numbering it, such as /f/MyForm.
   *
   * Quick Publish has no address for these, so they're refused with their
   * own reason.
   */
  private const NAMED_FORM_PATTERN = '#^/f/[A-Za-z0-9_-]+/?$#';

  /**
   * The query keys kept: FormAssembly's field names, such as tfa_4.
   *
   * Other keys are dropped. Rationale: FormAssembly names its fields tfa_
   * and a number, so other keys can't fill one in. And links copied from a
   * browser often carry tracking keys, such as Google's _gl.
   */
  private const PREFILL_KEY_PATTERN = '/^tfa_[A-Za-z0-9_]{1,60}$/';

  /**
   * The longest prefill value allowed. A link with a longer one is refused.
   *
   * It's far above the values in the campus links we tested.
   */
  private const MAX_VALUE_LENGTH = 512;

  /**
   * Constructs a TrellisUrl. Use parse() instead.
   *
   * @param string $host
   *   One of HOSTS.
   * @param string $formId
   *   The form's number.
   * @param array<string, string> $prefill
   *   Field values to fill in, keyed by field name.
   */
  private function __construct(
    private readonly string $host,
    private readonly string $formId,
    private readonly array $prefill,
  ) {}

  /**
   * Checks whether a link is on a Trellis host, even if it isn't valid.
   *
   * @param string $url
   *   The link an editor pasted.
   *
   * @return bool
   *   TRUE if the link's host is a Trellis form host.
   */
  public static function looksLikeTrellis(string $url): bool {
    $host = parse_url(trim($url), PHP_URL_HOST);
    return is_string($host) && in_array(strtolower($host), self::HOSTS, TRUE);
  }

  /**
   * Checks a link and rebuilds it from its parts.
   *
   * @param string $url
   *   The link an editor pasted.
   * @param string|null $reason
   *   (optional) Set to a short code saying why the link was refused.
   *
   * @return static|null
   *   The checked URL, or NULL if the link was refused.
   */
  public static function parse(string $url, ?string &$reason = NULL): ?self {
    $parts = parse_url(trim($url));
    if ($parts === FALSE || ($parts['scheme'] ?? '') !== 'https') {
      $reason = 'bad_scheme';
      return NULL;
    }
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
    $host = strtolower($parts['host'] ?? '');
    if (!in_array($host, self::HOSTS, TRUE)) {
      $reason = 'bad_host';
      return NULL;
    }

    $path = $parts['path'] ?? '';
    $form_id = NULL;
    foreach (self::FORM_PATTERNS as $pattern) {
      if (preg_match($pattern, $path, $matches)) {
        $form_id = $matches[1];
        break;
      }
    }
    if ($form_id === NULL) {
      $reason = preg_match(self::NAMED_FORM_PATTERN, $path) ? 'named_form' : 'bad_path';
      return NULL;
    }

    // Read the query by hand rather than with parse_str(). Rationale:
    // parse_str() turns a key like tfa_4[] into a nested array, and renames
    // keys with dots in them.
    //
    // If a key repeats, keep only its last copy. Rationale: FormAssembly's own
    // prefill does the same. A field with several choices takes them in one
    // value, separated by semicolons, as in tfa_5=Red;Blue. Don't keep every
    // copy the way SlateUrl does, because Slate repeats keys and FormAssembly
    // doesn't.
    $prefill = [];
    foreach (explode('&', $parts['query'] ?? '') as $pair) {
      if ($pair === '') {
        continue;
      }
      [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
      $key = urldecode($key);
      if (!preg_match(self::PREFILL_KEY_PATTERN, $key)) {
        continue;
      }
      $value = urldecode($value);
      if (strlen($value) > self::MAX_VALUE_LENGTH) {
        $reason = 'param_too_long';
        return NULL;
      }
      $prefill[$key] = $value;
    }

    return new self($host, $form_id, $prefill);
  }

  /**
   * The Quick Publish address FormAssembly serves the form's script from.
   *
   * Only ever use it as a script source. The prefill rides along in its
   * query string, where the handler reads it. FormAssembly itself ignores
   * it there.
   */
  public function getEmbedUrl(): string {
    return $this->buildUrl('/publish/' . $this->formId);
  }

  /**
   * The URL a person can open in a browser. Safe to use as a link href.
   */
  public function getCanonicalUrl(): string {
    return $this->buildUrl('/' . $this->formId);
  }

  /**
   * Returns the field values the link prefills, keyed by field name.
   *
   * @return array<string, string>
   *   The values.
   */
  public function getPrefill(): array {
    return $this->prefill;
  }

  /**
   * Builds a URL on this form's host, with the prefill as its query.
   *
   * @param string $path
   *   The path, starting with a slash.
   */
  private function buildUrl(string $path): string {
    $url = 'https://' . $this->host . $path;
    if ($this->prefill !== []) {
      $url .= '?' . http_build_query($this->prefill, '', '&', PHP_QUERY_RFC3986);
    }
    return $url;
  }

}
