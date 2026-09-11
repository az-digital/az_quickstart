<?php

declare(strict_types=1);

namespace Drupal\Tests\az_media_slate\Unit;

use Drupal\az_media_slate\Plugin\Field\FieldFormatter\AzMediaRemoteSlateFormatter;
use Drupal\az_media_slate\SlateUrl;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Slate URL parser.
 *
 * SlateUrl stands between a string an editor pasted and a script we load onto
 * a page, so the rejection cases below matter most.
 *
 * @coversDefaultClass \Drupal\az_media_slate\SlateUrl
 * @group az_media_slate
 */
class SlateUrlTest extends UnitTestCase {

  /**
   * The test form's id, used by the cases below.
   */
  private const ID = 'dbfabd84-d348-4bf9-88ef-1832b354fcb0';

  /**
   * URLs the parser should accept, with the canonical URL it should return.
   */
  public static function validUrlProvider(): array {
    $base = 'https://uaz.technolutions.net/register/?id=' . self::ID;
    return [
      'production host' => [$base, $base],
      'test host' => [
        'https://uaz.test.technolutions.net/register/?id=' . self::ID,
        'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      ],
      // The save-time regex ignores case in the host, so a mixed-case host
      // saves. The parser must accept it too, or the URL would save and then
      // fail to render.
      'mixed case scheme and host' => [
        'HTTPS://UAZ.Technolutions.NET/register/?id=' . self::ID,
        $base,
      ],
      'uppercase id' => [
        'https://uaz.technolutions.net/register/?id=' . strtoupper(self::ID),
        'https://uaz.technolutions.net/register/?id=' . strtoupper(self::ID),
      ],
      // A prefill key is the field's export key, with no prefix. The next four
      // cases come from Slate's prefill documentation.
      'system export key' => [
        $base . '&sys%3Afirst=Alexander',
        $base . '&sys:first=Alexander',
      ],
      'mapped field export key' => [
        $base . '&sys%3Afield%3Aacainterest=84fcea7a-72e6-4743-8594-4c25c1c25015',
        $base . '&sys:field:acainterest=84fcea7a-72e6-4743-8594-4c25c1c25015',
      ],
      'form-specific export key' => [
        $base . '&lunch_preference=Chicken',
        $base . '&lunch_preference=Chicken',
      ],
      'several parameters at once' => [
        $base . '&sys%3Afirst=Alexander&sys%3Alast=Hamilton',
        $base . '&sys:first=Alexander&sys:last=Hamilton',
      ],
      // The parser splits the query itself. For example, parse_str() would
      // turn this key into my_field, and the field would quietly never
      // prefill.
      'export key containing a dot' => [
        $base . '&my.field=Wilbur',
        $base . '&my.field=Wilbur',
      ],
      // The person key is refused, but personal_email is an ordinary export
      // key. The rule must match the whole key.
      'export key beginning with person' => [
        $base . '&personal_email=wilbur%40example.edu',
        $base . '&personal_email=wilbur@example.edu',
      ],
      // We set output and div ourselves. A pasted copy is dropped, not
      // rejected, so ours wins.
      'reserved parameters are dropped' => [
        $base . '&output=embed&div=someone-elses-id',
        $base,
      ],
      'surrounding whitespace' => [' ' . $base . ' ', $base],
      // Slate's own page for a form gives /register/form?id=<guid> as its link.
      // A link with an id always comes back out as /register/?id=<guid>.
      'form path with an id' => [
        'https://uaz.technolutions.net/register/form?id=' . self::ID,
        $base,
      ],
      'named path with an id' => [
        'https://uaz.technolutions.net/register/moreinfo?id=' . self::ID,
        $base,
      ],
      'id after another parameter' => [
        'https://uaz.technolutions.net/register/?sys:first=Alexander&id=' . self::ID,
        $base . '&sys:first=Alexander',
      ],
      // A link by name, with no id, keeps its name.
      'named path' => [
        'https://uaz.technolutions.net/register/moreinfo',
        'https://uaz.technolutions.net/register/moreinfo',
      ],
      'named path with a trailing slash' => [
        'https://uaz.technolutions.net/register/moreinfo/',
        'https://uaz.technolutions.net/register/moreinfo',
      ],
      'named path with prefill' => [
        'https://uaz.technolutions.net/register/moreinfo?sys:first=Alexander',
        'https://uaz.technolutions.net/register/moreinfo?sys:first=Alexander',
      ],
    ];
  }

  /**
   * URLs the parser should reject, with the reason it should report.
   */
  public static function invalidUrlProvider(): array {
    $base = 'https://uaz.technolutions.net/register/?id=' . self::ID;
    return [
      'empty' => ['', 'empty'],
      'whitespace only' => ['   ', 'empty'],
      'http' => ['http://uaz.technolutions.net/register/?id=' . self::ID, 'bad_scheme'],
      'javascript scheme' => ['javascript:alert(1)', 'unparseable'],
      'another host entirely' => ['https://example.com/register/?id=' . self::ID, 'bad_host'],
      // The suffix check includes the leading dot, so a lookalike domain that
      // only ends in the same letters fails.
      'lookalike host' => ['https://eviltechnolutions.net/register/?id=' . self::ID, 'bad_host'],
      'host as a path segment' => ['https://evil.com/uaz.technolutions.net/register/?id=' . self::ID, 'bad_host'],
      'credentials in the url' => ['https://user:pass@uaz.technolutions.net/register/?id=' . self::ID, 'has_userinfo'],
      'explicit port' => ['https://uaz.technolutions.net:8443/register/?id=' . self::ID, 'has_port'],
      'fragment' => [$base . '#section', 'has_fragment'],
      'fragment after a parameter' => [$base . '&form_a=b#section', 'has_fragment'],
      'wrong path' => ['https://uaz.technolutions.net/other/?id=' . self::ID, 'bad_path'],
      // A form name goes back into the script src, so it has to be one plain
      // word. For example, a browser resolves /register/../manage to /manage.
      'path that climbs out of register' => ['https://uaz.technolutions.net/register/../manage/x', 'bad_path'],
      'encoded dots in the path' => ['https://uaz.technolutions.net/register/%2e%2e/manage', 'bad_path'],
      'nested path' => ['https://uaz.technolutions.net/register/a/b', 'bad_path'],
      'no id and no name' => ['https://uaz.technolutions.net/register/', 'missing_id'],
      // The id has to be its own parameter, not text inside another value.
      'id hidden inside a value' => ['https://uaz.technolutions.net/register/?sys:first=a?id=x', 'missing_id'],
      'named path with a malformed id' => ['https://uaz.technolutions.net/register/moreinfo?id=nope', 'bad_id'],
      'malformed id' => ['https://uaz.technolutions.net/register/?id=not-a-guid', 'bad_id'],
      'id missing a group' => ['https://uaz.technolutions.net/register/?id=dbfabd84-d348-4bf9-1832b354fcb0', 'bad_id'],
      // One stored URL serves every visitor, so person would show one record's
      // details to all of them.
      'person parameter' => [$base . '&person=' . self::ID, 'person_param'],
      // Slate requires query keys to be all lowercase.
      'uppercase export key' => [$base . '&SYS:first=x', 'unknown_param'],
      'uppercase inside an export key' => [$base . '&sys:FIRST=x', 'unknown_param'],
      // The parser decodes a key before checking it, so an encoded space is
      // caught instead of being passed along to Slate.
      'export key containing a space' => [$base . '&sys%20first=x', 'unknown_param'],
      'over-long value' => [$base . '&sys:first=' . str_repeat('x', 513), 'param_too_long'],
      'over-long key' => [$base . '&' . str_repeat('a', 65) . '=x', 'param_too_long'],
    ];
  }

  /**
   * @covers ::parse
   * @covers ::getCanonicalUrl
   * @dataProvider validUrlProvider
   */
  public function testValidUrls(string $input, string $expected_canonical): void {
    $reason = 'unset';
    $parsed = SlateUrl::parse($input, $reason);

    $this->assertNotNull($parsed, 'The URL was accepted.');
    $this->assertNull($reason, 'No rejection reason was set.');
    $this->assertSame($expected_canonical, urldecode($parsed->getCanonicalUrl()));
  }

  /**
   * @covers ::parse
   * @dataProvider invalidUrlProvider
   */
  public function testInvalidUrls(string $input, string $expected_reason): void {
    $reason = NULL;
    $parsed = SlateUrl::parse($input, $reason);

    $this->assertNull($parsed, 'The URL was rejected.');
    $this->assertSame($expected_reason, $reason);
  }

  /**
   * The embed URL carries our container id and output=embed.
   *
   * @covers ::getEmbedUrl
   */
  public function testEmbedUrl(): void {
    $parsed = SlateUrl::parse('https://uaz.technolutions.net/register/?id=' . self::ID);
    $embed = $parsed->getEmbedUrl('az-media-slate-abc-0');

    $this->assertStringContainsString('output=embed', $embed);
    $this->assertStringContainsString('div=az-media-slate-abc-0', $embed);
    $this->assertStringContainsString('id=' . self::ID, $embed);
  }

  /**
   * A link by name keeps its name in the embed URL.
   *
   * @covers ::getEmbedUrl
   */
  public function testEmbedUrlForNamedPath(): void {
    $parsed = SlateUrl::parse('https://uaz.technolutions.net/register/moreinfo?sys:first=Alexander');
    $embed = urldecode($parsed->getEmbedUrl('az-media-slate-abc-0'));

    $this->assertSame('https://uaz.technolutions.net/register/moreinfo?sys:first=Alexander&output=embed&div=az-media-slate-abc-0', $embed);
  }

  /**
   * The canonical URL never carries the parameters that make Slate return JS.
   *
   * A link to the embed URL would show someone a script instead of the form,
   * so the two URLs must stay different.
   *
   * @covers ::getCanonicalUrl
   * @covers ::getEmbedUrl
   */
  public function testCanonicalUrlIsNotTheEmbedUrl(): void {
    $parsed = SlateUrl::parse('https://uaz.technolutions.net/register/?id=' . self::ID);

    $this->assertStringNotContainsString('output=embed', $parsed->getCanonicalUrl());
    $this->assertStringNotContainsString('div=', $parsed->getCanonicalUrl());
    $this->assertNotSame($parsed->getCanonicalUrl(), $parsed->getEmbedUrl('x'));
  }

  /**
   * The save-time regex agrees with the parser on the cases that matter.
   *
   * The media_remote module checks a pasted URL against a regex when media is
   * saved, and SlateUrl decides what actually loads. If the regex accepts a URL
   * the parser rejects, an editor saves without error and then finds an empty
   * space where the form should be. These cases keep the two in step.
   *
   * @covers \Drupal\az_media_slate\Plugin\Field\FieldFormatter\AzMediaRemoteSlateFormatter::getUrlRegexPattern
   */
  public function testSaveTimePatternMatchesParser(): void {
    $pattern = AzMediaRemoteSlateFormatter::getUrlRegexPattern();
    $base = 'https://uaz.technolutions.net/register/?id=' . self::ID;

    $accepted = [
      'plain form URL' => $base,
      'test environment host' => 'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      // Slate's docs write export keys unencoded, so both checks must agree on
      // URLs written that way.
      'documented export key' => $base . '&sys:first=Alexander',
      'mapped field export key' => $base . '&sys:field:acainterest=' . self::ID,
      'form-specific export key' => $base . '&lunch_preference=Chicken',
      'dotted export key' => $base . '&my.field=Wilbur',
      'several parameters at once' => $base . '&sys:first=Alexander&sys:last=Hamilton',
      // The person key is refused, but this is an ordinary export key.
      'export key beginning with person' => $base . '&personal_email=x',
      // Keys must be lowercase; values can be any case.
      'uppercase prefill value' => $base . '&sys:first=ALEXANDER',
      // The regex ignores case in the host and id, so the parser must accept
      // those too.
      'mixed case host and id' => 'HTTPS://UAZ.Technolutions.NET/register/?id=' . strtoupper(self::ID),
      'form path with an id' => 'https://uaz.technolutions.net/register/form?id=' . self::ID,
      'id after another parameter' => $base . '&sys:first=Alexander',
      'id not first' => 'https://uaz.technolutions.net/register/?sys:first=Alexander&id=' . self::ID,
      'named path' => 'https://uaz.technolutions.net/register/moreinfo',
      'named path with prefill' => 'https://uaz.technolutions.net/register/moreinfo?sys:first=Alexander',
      // The id and person rules must match the whole key.
      'export key beginning with id' => 'https://uaz.technolutions.net/register/moreinfo?identity=x',
    ];
    foreach ($accepted as $label => $url) {
      $this->assertSame(1, preg_match($pattern, $url), $label);
      $this->assertNotNull(SlateUrl::parse($url), $label);
    }

    $rejected = [
      'person parameter' => $base . '&person=' . self::ID,
      'another host' => 'https://example.com/register/?id=' . self::ID,
      'lookalike host' => 'https://eviltechnolutions.net/register/?id=' . self::ID,
      'fragment' => $base . '#section',
      'plain http' => 'http://uaz.technolutions.net/register/?id=' . self::ID,
      // A blanket /i flag on the regex would accept these three on save, and
      // the parser would reject them at render. So the regex ignores case only
      // in the scheme, host, and id.
      'uppercase export key' => $base . '&SYS:first=x',
      'uppercase reserved key' => $base . '&OUTPUT=embed',
      'uppercase path' => 'https://uaz.technolutions.net/REGISTER/?id=' . self::ID,
      // Uppercase anywhere in the key, not only at the start.
      'uppercase inside export key' => $base . '&sys:FIRST=x',
      // A percent escape in a key fails both checks. The regex refuses any
      // escape; the parser decodes first and then refuses the result. If the
      // regex allowed escapes, it would accept these and the parser would
      // reject them at render.
      'uppercase in encoded key' => $base . '&sys%3AFirst=x',
      'encoded space in key' => $base . '&sys%20first=x',
      // A path either names a form in one plain word or names nothing.
      'path that climbs out of register' => 'https://uaz.technolutions.net/register/../manage/x',
      'nested path' => 'https://uaz.technolutions.net/register/a/b',
      'no id and no name' => 'https://uaz.technolutions.net/register/',
      // A ? inside a value doesn't start a new parameter, so this has no id.
      'id hidden inside a value' => 'https://uaz.technolutions.net/register/?sys:first=a?id=x',
      'malformed id' => 'https://uaz.technolutions.net/register/?id=not-a-guid',
      'named path with a person parameter' => 'https://uaz.technolutions.net/register/moreinfo?person=' . self::ID,
    ];
    foreach ($rejected as $label => $url) {
      $this->assertSame(0, preg_match($pattern, $url), $label);
      $this->assertNull(SlateUrl::parse($url), $label);
    }
  }

  /**
   * A div parameter in the pasted URL doesn't make it into the embed URL.
   *
   * @covers ::getEmbedUrl
   */
  public function testPastedDivDoesNotOverrideOurs(): void {
    $parsed = SlateUrl::parse(
      'https://uaz.technolutions.net/register/?id=' . self::ID . '&div=someone-elses-id'
    );
    $embed = $parsed->getEmbedUrl('az-media-slate-ours-0');

    $this->assertStringContainsString('div=az-media-slate-ours-0', $embed);
    $this->assertStringNotContainsString('someone-elses-id', $embed);
  }

}
