<?php

declare(strict_types=1);

namespace Drupal\Tests\az_media_slate\Unit;

use Drupal\az_media_slate\Plugin\Field\FieldFormatter\AzMediaRemoteSlateFormatter;
use Drupal\az_media_slate\SlateUrl;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Slate URL parser.
 *
 * This is the check that stands between a string an editor pasted and a
 * script we load into a page, so the rejection cases below matter more than
 * the accepting ones.
 *
 * @coversDefaultClass \Drupal\az_media_slate\SlateUrl
 * @group az_media_slate
 */
class SlateUrlTest extends UnitTestCase {

  /**
   * A valid form id, reused across the cases below.
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
      // The regex media_remote uses is case-insensitive, so a mixed-case host
      // passes on save. The parser has to accept it too, or a URL could save
      // and then fail to render.
      'mixed case scheme and host' => [
        'HTTPS://UAZ.Technolutions.NET/register/?id=' . self::ID,
        $base,
      ],
      'uppercase id' => [
        'https://uaz.technolutions.net/register/?id=' . strtoupper(self::ID),
        'https://uaz.technolutions.net/register/?id=' . strtoupper(self::ID),
      ],
      // A prefill key is the form field's export key, used with no prefix.
      // These three are the examples in Slate's own prefill documentation.
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
      // parse_str() would turn this key into my_field, so the parser splits
      // the query itself. An export key has to survive intact or the field it
      // names quietly does not get filled in.
      'export key containing a dot' => [
        $base . '&my.field=Wilbur',
        $base . '&my.field=Wilbur',
      ],
      // The person key is refused, but a key that merely begins with those
      // letters is an ordinary export key. The rule must match the whole key.
      'export key beginning with person' => [
        $base . '&personal_email=wilbur%40example.edu',
        $base . '&personal_email=wilbur@example.edu',
      ],
      // Slate sets output and div itself. A pasted copy is dropped rather
      // than rejected, because it is our parameter to own.
      'reserved parameters are dropped' => [
        $base . '&output=embed&div=someone-elses-id',
        $base,
      ],
      'surrounding whitespace' => [' ' . $base . ' ', $base],
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
      // The suffix is checked with a leading dot, so a lookalike domain that
      // merely ends in the same letters does not pass.
      'lookalike host' => ['https://eviltechnolutions.net/register/?id=' . self::ID, 'bad_host'],
      'host as a path segment' => ['https://evil.com/uaz.technolutions.net/register/?id=' . self::ID, 'bad_host'],
      'credentials in the url' => ['https://user:pass@uaz.technolutions.net/register/?id=' . self::ID, 'has_userinfo'],
      'explicit port' => ['https://uaz.technolutions.net:8443/register/?id=' . self::ID, 'has_port'],
      'fragment' => [$base . '#section', 'has_fragment'],
      'fragment after a parameter' => [$base . '&form_a=b#section', 'has_fragment'],
      'wrong path' => ['https://uaz.technolutions.net/other/?id=' . self::ID, 'bad_path'],
      'no id' => ['https://uaz.technolutions.net/register/', 'missing_id'],
      'malformed id' => ['https://uaz.technolutions.net/register/?id=not-a-guid', 'bad_id'],
      'id missing a group' => ['https://uaz.technolutions.net/register/?id=dbfabd84-d348-4bf9-1832b354fcb0', 'bad_id'],
      // One stored URL serves every visitor, so a person parameter would show
      // one record's data to all of them.
      'person parameter' => [$base . '&person=' . self::ID, 'person_param'],
      // Slate requires query keys to be all lowercase.
      'uppercase export key' => [$base . '&SYS:first=x', 'unknown_param'],
      'uppercase inside an export key' => [$base . '&sys:FIRST=x', 'unknown_param'],
      // The parser decodes a key before checking it, so an encoded space is
      // caught rather than being carried into the URL we build.
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
   * The embed URL carries our container id and Slate's output parameter.
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
   * The canonical URL never carries the parameters that make Slate return JS.
   *
   * Linking to the embed URL would hand someone a script file instead of the
   * form, so the two must not converge.
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
   * The save-time pattern agrees with the parser on the dangerous cases.
   *
   * The media_remote module checks a pasted URL against a regex when the media
   * is saved, while SlateUrl decides what actually gets loaded. The regex is
   * not the security boundary, but if it is looser than the parser an editor
   * saves a URL happily and then finds an empty space where the form should
   * be. These are the cases where the two must not drift apart.
   *
   * @covers \Drupal\az_media_slate\Plugin\Field\FieldFormatter\AzMediaRemoteSlateFormatter::getUrlRegexPattern
   */
  public function testSaveTimePatternMatchesParser(): void {
    $pattern = AzMediaRemoteSlateFormatter::getUrlRegexPattern();
    $base = 'https://uaz.technolutions.net/register/?id=' . self::ID;

    $accepted = [
      'plain form URL' => $base,
      'test environment host' => 'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      // Slate writes export keys unencoded, which is the form that has to
      // agree on both sides.
      'documented export key' => $base . '&sys:first=Alexander',
      'mapped field export key' => $base . '&sys:field:acainterest=' . self::ID,
      'form-specific export key' => $base . '&lunch_preference=Chicken',
      'dotted export key' => $base . '&my.field=Wilbur',
      'several parameters at once' => $base . '&sys:first=Alexander&sys:last=Hamilton',
      // The person key is refused, but this is a normal export key.
      'export key beginning with person' => $base . '&personal_email=x',
      // Keys must be lowercase; values may be any case.
      'uppercase prefill value' => $base . '&sys:first=ALEXANDER',
      // The regex is case-insensitive for the host and id, so the parser has
      // to accept those too.
      'mixed case host and id' => 'HTTPS://UAZ.Technolutions.NET/register/?id=' . strtoupper(self::ID),
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
      // Slate requires lowercase query keys. Case-insensitivity in the regex
      // is scoped to the host and id for this reason: a blanket /i flag would
      // accept these on save and leave the parser to reject them at render.
      'uppercase export key' => $base . '&SYS:first=x',
      'uppercase reserved key' => $base . '&OUTPUT=embed',
      'uppercase path' => 'https://uaz.technolutions.net/REGISTER/?id=' . self::ID,
      // Uppercase anywhere in the key, not only at the front.
      'uppercase inside export key' => $base . '&sys:FIRST=x',
      // A percent escape in a key is refused on both sides. The regex refuses
      // every escape outright; the parser decodes first and refuses what the
      // escape turns into. Without that the regex would accept these and the
      // parser would reject them at render.
      'uppercase in encoded key' => $base . '&sys%3AFirst=x',
      'encoded space in key' => $base . '&sys%20first=x',
    ];
    foreach ($rejected as $label => $url) {
      $this->assertSame(0, preg_match($pattern, $url), $label);
      $this->assertNull(SlateUrl::parse($url), $label);
    }
  }

  /**
   * A caller-supplied div does not survive into the embed URL.
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
