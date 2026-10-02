<?php

declare(strict_types=1);

namespace Drupal\Tests\az_form_embed_slate\Unit;

use Drupal\az_form_embed_slate\Plugin\FormEmbedVendor\Slate;
use Drupal\az_form_embed_slate\SlateUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Slate URL parser, and the Slate vendor built on it.
 *
 * SlateUrl stands between a string an editor pasted and a script we load onto
 * a page, so the rejection cases below matter most.
 */
#[CoversClass(SlateUrl::class)]
#[CoversClass(Slate::class)]
#[Group('az_form_embed')]
class SlateUrlTest extends UnitTestCase {

  /**
   * The test form's id, used by the cases below.
   */
  private const ID = 'dbfabd84-d348-4bf9-88ef-1832b354fcb0';

  /**
   * URLs the parser should accept, with the canonical URL it should return.
   */
  public static function validUrlProvider(): array {
    $base = 'https://slate.admissions.arizona.edu/register/?id=' . self::ID;
    return [
      // Each of the university's Slate instances has a production host on
      // arizona.edu and a test host on technolutions.net. Every one is
      // covered.
      'production vanity host' => [$base, $base],
      'law vanity host' => [
        'https://slate.law.arizona.edu/register/?id=' . self::ID,
        'https://slate.law.arizona.edu/register/?id=' . self::ID,
      ],
      'vetmed vanity host' => [
        'https://slate.vetmed.arizona.edu/register/?id=' . self::ID,
        'https://slate.vetmed.arizona.edu/register/?id=' . self::ID,
      ],
      'grad test host' => [
        'https://uag.test.technolutions.net/register/?id=' . self::ID,
        'https://uag.test.technolutions.net/register/?id=' . self::ID,
      ],
      'law test host' => [
        'https://ual.test.technolutions.net/register/?id=' . self::ID,
        'https://ual.test.technolutions.net/register/?id=' . self::ID,
      ],
      'uaonline test host' => [
        'https://uao.test.technolutions.net/register/?id=' . self::ID,
        'https://uao.test.technolutions.net/register/?id=' . self::ID,
      ],
      'vetmed test host' => [
        'https://uav.test.technolutions.net/register/?id=' . self::ID,
        'https://uav.test.technolutions.net/register/?id=' . self::ID,
      ],
      'grad vanity host' => [
        'https://slate.grad.arizona.edu/register/?id=' . self::ID,
        'https://slate.grad.arizona.edu/register/?id=' . self::ID,
      ],
      'uaonline vanity host' => [
        'https://slate.uaonline.arizona.edu/register/?id=' . self::ID,
        'https://slate.uaonline.arizona.edu/register/?id=' . self::ID,
      ],
      'test host' => [
        'https://uaz.test.technolutions.net/register/?id=' . self::ID,
        'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      ],
      // Browsers treat the scheme and host as case-insensitive, so a
      // mixed-case host is the same form, and comes back lowercased.
      'mixed case scheme and host' => [
        'HTTPS://UAZ.Test.Technolutions.NET/register/?id=' . self::ID,
        'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      ],
      'mixed case vanity host' => [
        'HTTPS://Slate.Admissions.Arizona.EDU/register/?id=' . self::ID,
        'https://slate.admissions.arizona.edu/register/?id=' . self::ID,
      ],
      'uppercase id' => [
        'https://slate.admissions.arizona.edu/register/?id=' . strtoupper(self::ID),
        'https://slate.admissions.arizona.edu/register/?id=' . strtoupper(self::ID),
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
      // A field with several choices takes one copy of its key per choice.
      // Every copy is kept, in order.
      'repeated key for several choices' => [
        $base . '&sys:field:academic_interest=engineering&sys:first=Alexander&sys:field:academic_interest=undecided',
        $base . '&sys:field:academic_interest=engineering&sys:first=Alexander&sys:field:academic_interest=undecided',
      ],
      // Slate uses the last id it's given, so the rebuilt link does too.
      'repeated id' => [
        'https://slate.admissions.arizona.edu/register/?id=' . strtoupper(self::ID) . '&id=' . self::ID,
        $base,
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
        'https://slate.admissions.arizona.edu/register/form?id=' . self::ID,
        $base,
      ],
      'named path with an id' => [
        'https://slate.admissions.arizona.edu/register/moreinfo?id=' . self::ID,
        $base,
      ],
      'id after another parameter' => [
        'https://slate.admissions.arizona.edu/register/?sys:first=Alexander&id=' . self::ID,
        $base . '&sys:first=Alexander',
      ],
      // A link by name, with no id, keeps its name.
      'named path' => [
        'https://slate.admissions.arizona.edu/register/moreinfo',
        'https://slate.admissions.arizona.edu/register/moreinfo',
      ],
      'named path with a trailing slash' => [
        'https://slate.admissions.arizona.edu/register/moreinfo/',
        'https://slate.admissions.arizona.edu/register/moreinfo',
      ],
      'named path with prefill' => [
        'https://slate.admissions.arizona.edu/register/moreinfo?sys:first=Alexander',
        'https://slate.admissions.arizona.edu/register/moreinfo?sys:first=Alexander',
      ],
      // The named form the Slate team supplied, which does render as an embed.
      'named path on the test host' => [
        'https://uaz.test.technolutions.net/register/referawildcat',
        'https://uaz.test.technolutions.net/register/referawildcat',
      ],
    ];
  }

  /**
   * URLs the parser should reject, with the reason it should report.
   */
  public static function invalidUrlProvider(): array {
    $base = 'https://slate.admissions.arizona.edu/register/?id=' . self::ID;
    return [
      'empty' => ['', 'empty'],
      'whitespace only' => ['   ', 'empty'],
      'http' => ['http://slate.admissions.arizona.edu/register/?id=' . self::ID, 'bad_scheme'],
      'javascript scheme' => ['javascript:alert(1)', 'unparseable'],
      'another host entirely' => ['https://example.com/register/?id=' . self::ID, 'bad_host'],
      // Hosts are matched exactly, so a host that only resembles one on the
      // list fails.
      'lookalike host' => ['https://eviltechnolutions.net/register/?id=' . self::ID, 'bad_host'],
      'host as a path segment' => ['https://evil.com/slate.admissions.arizona.edu/register/?id=' . self::ID, 'bad_host'],
      'bare arizona.edu' => ['https://arizona.edu/register/?id=' . self::ID, 'bad_host'],
      // Another school's Slate test site, which a pattern like
      // ua[a-z].test.technolutions.net would let through.
      "another school's Slate" => ['https://uab.test.technolutions.net/register/?id=' . self::ID, 'bad_host'],
      // A host a department running its own DNS could create, which a pattern
      // like slate.[a-z]+.arizona.edu would let through.
      'department-made slate host' => ['https://slate.cs.arizona.edu/register/?id=' . self::ID, 'bad_host'],
      'subdomain of a listed host' => ['https://evil.slate.admissions.arizona.edu/register/?id=' . self::ID, 'bad_host'],
      'hyphenated lookalike' => ['https://evil-arizona.edu/register/?id=' . self::ID, 'bad_host'],
      'unhyphenated lookalike' => ['https://notarizona.edu/register/?id=' . self::ID, 'bad_host'],
      'suffix moved into the middle' => ['https://arizona.edu.evil.com/register/?id=' . self::ID, 'bad_host'],
      'http on a real vanity host' => ['http://slate.grad.arizona.edu/register/?id=' . self::ID, 'bad_scheme'],
      'credentials in the url' => [
        'https://user:pass@slate.admissions.arizona.edu/register/?id=' . self::ID,
        'has_userinfo',
      ],
      'explicit port' => ['https://slate.admissions.arizona.edu:8443/register/?id=' . self::ID, 'has_port'],
      'fragment' => [$base . '#section', 'has_fragment'],
      'fragment after a parameter' => [$base . '&form_a=b#section', 'has_fragment'],
      'wrong path' => ['https://slate.admissions.arizona.edu/other/?id=' . self::ID, 'bad_path'],
      // A form name goes back into the script src, so it has to be one plain
      // word. For example, a browser resolves /register/../manage to /manage.
      'path that climbs out of register' => ['https://slate.admissions.arizona.edu/register/../manage/x', 'bad_path'],
      'encoded dots in the path' => ['https://slate.admissions.arizona.edu/register/%2e%2e/manage', 'bad_path'],
      'nested path' => ['https://slate.admissions.arizona.edu/register/a/b', 'bad_path'],
      'no id and no name' => ['https://slate.admissions.arizona.edu/register/', 'missing_id'],
      // The id has to be its own parameter, not text inside another value.
      'id hidden inside a value' => ['https://slate.admissions.arizona.edu/register/?sys:first=a?id=x', 'missing_id'],
      'named path with a malformed id' => ['https://slate.admissions.arizona.edu/register/moreinfo?id=nope', 'bad_id'],
      'malformed id' => ['https://slate.admissions.arizona.edu/register/?id=not-a-guid', 'bad_id'],
      'id missing a group' => [
        'https://slate.admissions.arizona.edu/register/?id=dbfabd84-d348-4bf9-1832b354fcb0',
        'bad_id',
      ],
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
   * The parser accepts each valid URL and returns its canonical URL.
   */
  #[DataProvider('validUrlProvider')]
  public function testValidUrls(string $input, string $expected_canonical): void {
    $reason = 'unset';
    $parsed = SlateUrl::parse($input, $reason);

    $this->assertNotNull($parsed, 'The URL was accepted.');
    $this->assertNull($reason, 'No rejection reason was set.');
    $this->assertSame($expected_canonical, urldecode($parsed->getCanonicalUrl()));
  }

  /**
   * The parser rejects each invalid URL, with the reason it should report.
   */
  #[DataProvider('invalidUrlProvider')]
  public function testInvalidUrls(string $input, string $expected_reason): void {
    $reason = NULL;
    $parsed = SlateUrl::parse($input, $reason);

    $this->assertNull($parsed, 'The URL was rejected.');
    $this->assertSame($expected_reason, $reason);
  }

  /**
   * The embed URL carries the form's id and output=embed, and no div.
   */
  public function testEmbedUrl(): void {
    $parsed = SlateUrl::parse('https://slate.admissions.arizona.edu/register/?id=' . self::ID);
    $embed = $parsed->getEmbedUrl();

    $this->assertStringContainsString('output=embed', $embed);
    $this->assertStringContainsString('id=' . self::ID, $embed);
    // The loader adds div, because the id is picked in the browser.
    $this->assertStringNotContainsString('div=', $embed);
  }

  /**
   * A link by name keeps its name in the embed URL.
   */
  public function testEmbedUrlForNamedPath(): void {
    $parsed = SlateUrl::parse('https://slate.admissions.arizona.edu/register/moreinfo?sys:first=Alexander');
    $embed = urldecode($parsed->getEmbedUrl());

    $this->assertSame('https://slate.admissions.arizona.edu/register/moreinfo?sys:first=Alexander&output=embed', $embed);
  }

  /**
   * The embed URL keeps every copy of a repeated key, before output=embed.
   */
  public function testEmbedUrlKeepsRepeatedKeys(): void {
    $parsed = SlateUrl::parse('https://uaz.test.technolutions.net/register/referawildcat?sys:field:academic_interest=engineering&sys:field:academic_interest=undecided');
    $embed = urldecode($parsed->getEmbedUrl());

    $this->assertSame('https://uaz.test.technolutions.net/register/referawildcat?sys:field:academic_interest=engineering&sys:field:academic_interest=undecided&output=embed', $embed);
  }

  /**
   * The canonical URL never carries the parameters that make Slate return JS.
   *
   * A link to the embed URL would show someone a script instead of the form,
   * so the two URLs must stay different.
   */
  public function testCanonicalUrlIsNotTheEmbedUrl(): void {
    $parsed = SlateUrl::parse('https://slate.admissions.arizona.edu/register/?id=' . self::ID);

    $this->assertStringNotContainsString('output=embed', $parsed->getCanonicalUrl());
    $this->assertStringNotContainsString('div=', $parsed->getCanonicalUrl());
    $this->assertNotSame($parsed->getCanonicalUrl(), $parsed->getEmbedUrl());
  }

  /**
   * The Slate vendor accepts what SlateUrl accepts, and explains the rest.
   *
   * The vendor is what the Form embed module asks, both when an editor saves
   * a link and when a page renders one. A link that looks like Slate's but
   * fails SlateUrl must be claimed, so the editor hears Slate's reason, and
   * then refused.
   */
  public function testSlateVendor(): void {
    $vendor = new Slate([], 'slate', ['label' => 'Slate', 'examples' => []]);
    $vendor->setStringTranslation($this->getStringTranslationStub());
    $base = 'https://slate.admissions.arizona.edu/register/?id=' . self::ID;

    $accepted = [
      'plain form URL' => $base,
      'test environment host' => 'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      'grad vanity host' => 'https://slate.grad.arizona.edu/register/?id=' . self::ID,
      'uaonline vanity host' => 'https://slate.uaonline.arizona.edu/register/?id=' . self::ID,
      'named path on the test host' => 'https://uaz.test.technolutions.net/register/referawildcat',
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
      // Browsers treat the scheme and host as case-insensitive, and Slate
      // ids are hex, so case doesn't matter there.
      'mixed case host and id' => 'HTTPS://UAZ.Test.Technolutions.NET/register/?id=' . strtoupper(self::ID),
      'form path with an id' => 'https://slate.admissions.arizona.edu/register/form?id=' . self::ID,
      'id after another parameter' => $base . '&sys:first=Alexander',
      'id not first' => 'https://slate.admissions.arizona.edu/register/?sys:first=Alexander&id=' . self::ID,
      'named path' => 'https://slate.admissions.arizona.edu/register/moreinfo',
      'named path with prefill' => 'https://slate.admissions.arizona.edu/register/moreinfo?sys:first=Alexander',
      // The id and person rules must match the whole key.
      'export key beginning with id' => 'https://slate.admissions.arizona.edu/register/moreinfo?identity=x',
    ];
    foreach ($accepted as $label => $url) {
      $this->assertTrue($vendor->claims($url), $label);
      $target = $vendor->resolve($url);
      $this->assertNotNull($target, $label);
      $this->assertSame(SlateUrl::parse($url)->getEmbedUrl(), $target->embedUrl, $label);
      $this->assertSame(SlateUrl::parse($url)->getCanonicalUrl(), $target->canonicalUrl, $label);
    }

    $rejected = [
      'person parameter' => $base . '&person=' . self::ID,
      'another host' => 'https://example.com/register/?id=' . self::ID,
      'lookalike host' => 'https://eviltechnolutions.net/register/?id=' . self::ID,
      'bare arizona.edu' => 'https://arizona.edu/register/?id=' . self::ID,
      'hyphenated lookalike' => 'https://evil-arizona.edu/register/?id=' . self::ID,
      'suffix moved into the middle' => 'https://arizona.edu.evil.com/register/?id=' . self::ID,
      "another school's Slate" => 'https://uab.test.technolutions.net/register/?id=' . self::ID,
      'fragment' => $base . '#section',
      'plain http' => 'http://slate.admissions.arizona.edu/register/?id=' . self::ID,
      // Slate requires lowercase keys and paths.
      'uppercase export key' => $base . '&SYS:first=x',
      'uppercase reserved key' => $base . '&OUTPUT=embed',
      'uppercase path' => 'https://slate.admissions.arizona.edu/REGISTER/?id=' . self::ID,
      // Uppercase anywhere in the key, not only at the start.
      'uppercase inside export key' => $base . '&sys:FIRST=x',
      // The parser decodes a percent escape in a key first, then refuses
      // what it decodes to.
      'uppercase in encoded key' => $base . '&sys%3AFirst=x',
      'encoded space in key' => $base . '&sys%20first=x',
      // A path either names a form in one plain word or names nothing.
      'path that climbs out of register' => 'https://slate.admissions.arizona.edu/register/../manage/x',
      'nested path' => 'https://slate.admissions.arizona.edu/register/a/b',
      'no id and no name' => 'https://slate.admissions.arizona.edu/register/',
      // A ? inside a value doesn't start a new parameter, so this has no id.
      'id hidden inside a value' => 'https://slate.admissions.arizona.edu/register/?sys:first=a?id=x',
      'malformed id' => 'https://slate.admissions.arizona.edu/register/?id=not-a-guid',
      'named path with a person parameter' => 'https://slate.admissions.arizona.edu/register/moreinfo?person=' . self::ID,
    ];
    foreach ($rejected as $label => $url) {
      $reason = NULL;
      $this->assertNull($vendor->resolve($url, $reason), $label);
      $this->assertNotNull($reason, $label);
      $this->assertNotSame('', $vendor->explain($reason), $label);
    }

    // A link on another school's Slate isn't claimed, because its host isn't
    // one of the university's. So no vendor explains it, and the editor sees
    // the Form embed module's general message instead.
    $this->assertFalse($vendor->claims('https://uab.test.technolutions.net/register/?id=' . self::ID));
  }

  /**
   * Only a link on a listed Slate host, under /register, looks like Slate's.
   */
  public function testLooksLikeSlate(): void {
    $looks_like_slate = [
      'test host' => 'https://uaz.test.technolutions.net/register/?id=' . self::ID,
      'vanity host' => 'https://slate.admissions.arizona.edu/register/?id=' . self::ID,
      'plain http, which Slate then refuses' => 'http://slate.admissions.arizona.edu/register/?id=' . self::ID,
      'no trailing slash' => 'https://slate.admissions.arizona.edu/register?id=' . self::ID,
    ];
    foreach ($looks_like_slate as $label => $url) {
      $this->assertTrue(SlateUrl::looksLikeSlate($url), $label);
    }
    $not_slate = [
      'a Trellis form, also on arizona.edu' => 'https://forms-a.trellis.arizona.edu/192',
      'another host' => 'https://example.com/register/?id=' . self::ID,
      'lookalike host' => 'https://eviltechnolutions.net/register/?id=' . self::ID,
      "another school's Slate" => 'https://uab.test.technolutions.net/register/?id=' . self::ID,
      'not a URL' => 'not a url',
    ];
    foreach ($not_slate as $label => $url) {
      $this->assertFalse(SlateUrl::looksLikeSlate($url), $label);
    }
  }

  /**
   * A div parameter in the pasted URL never reaches Slate.
   */
  public function testPastedDivIsDropped(): void {
    $parsed = SlateUrl::parse(
      'https://slate.admissions.arizona.edu/register/?id=' . self::ID . '&div=someone-elses-id'
    );

    $this->assertStringNotContainsString('someone-elses-id', $parsed->getEmbedUrl());
    $this->assertStringNotContainsString('someone-elses-id', $parsed->getCanonicalUrl());
  }

  /**
   * The browser is handed the same rules the parser applies.
   */
  public function testForwardingRules(): void {
    $rules = SlateUrl::getForwardingRules();
    $pattern = '/' . $rules['keyPattern'] . '/';

    // The pattern has to compile and accept the keys Slate's docs use.
    $accepted = ['sys:first', 'sys:field:acainterest', 'lunch_preference', 'my.field', 'person'];
    foreach ($accepted as $key) {
      $this->assertSame(1, preg_match($pattern, $key), $key);
    }
    foreach (['SYS:first', 'sys first', 'sys/first', ''] as $key) {
      $this->assertSame(0, preg_match($pattern, $key), $key);
    }

    // The keys the embed URL sets for itself. Slate honors the last copy of
    // a parameter, so a forwarded one would beat ours.
    $this->assertSame(['output', 'div', 'id'], $rules['blockedKeys']);

    // The person key is forwarded on purpose. A visitor's own link is the
    // one place it means what Slate says it means, unlike a URL saved once
    // for everyone, which parse() still refuses.
    $this->assertNotContains('person', $rules['blockedKeys']);

    $this->assertSame(64, $rules['maxKeyLength']);
    $this->assertSame(512, $rules['maxValueLength']);
  }

}
