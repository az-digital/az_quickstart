<?php declare(strict_types=1);

namespace FileEye\MimeMap\Test;

use FileEye\MimeMap\MalformedTypeException;
use FileEye\MimeMap\MappingException;
use FileEye\MimeMap\Type;
use FileEye\MimeMap\TypeParameter;
use FileEye\MimeMap\UndefinedException;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeTest extends MimeMapTestBase
{
    /**
     * Data provider for testParse.
     *
     * @return array<string,mixed>
     */
    public static function parseProvider(): array
    {
        return [
            'application/ogg;description=Hello there!;asd=fgh' => [
                'application/ogg;description=Hello there!;asd=fgh',
                [
                  'application/ogg',
                  'application/ogg; description="Hello there!"; asd="fgh"',
                  'application/ogg; description="Hello there!"; asd="fgh"',
                ],
                ['application'],
                ['ogg'],
                true,
                [
                  'description' => ['Hello there!'],
                  'asd' => ['fgh'],
                ],
            ],
            'text/plain' => [
                'text/plain',
                [
                  'text/plain',
                  'text/plain',
                  'text/plain',
                ],
                ['text'],
                ['plain'],
                false,
                [],
            ],
            'text/plain;a=b' => [
                'text/plain;a=b',
                [
                  'text/plain',
                  'text/plain; a="b"',
                  'text/plain; a="b"',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'a' => ['b'],
                ],
            ],
            'application/ogg' => [
                'application/ogg',
                [
                  'application/ogg',
                  'application/ogg',
                  'application/ogg',
                ],
                ['application'],
                ['ogg'],
                false,
                [],
            ],
            '*/*' => [
                '*/*',
                [
                  '*/*',
                  '*/*',
                  '*/*',
                ],
                ['*'],
                ['*'],
                false,
                [],
            ],
            'n/n' => [
                'n/n',
                [
                  'n/n',
                  'n/n',
                  'n/n',
                ],
                ['n'],
                ['n'],
                false,
                [],
            ],
            '(UTF-8 Plain Text) text / plain ; charset = utf-8' => [
                '(UTF-8 Plain Text) text / plain ; charset = utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text (UTF-8 Plain Text)/plain; charset="utf-8"',
                ],
                ['text', 'UTF-8 Plain Text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8'],
                ],
            ],
            'text (Text) / plain ; charset = utf-8' => [
                'text (Text) / plain ; charset = utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text (Text)/plain; charset="utf-8"',
                ],
                ['text', 'Text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8'],
                ],
            ],
            'text / (Plain) plain ; charset = utf-8' => [
                'text / (Plain) plain ; charset = utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain (Plain); charset="utf-8"',
                ],
                ['text'],
                ['plain', 'Plain'],
                true,
                [
                  'charset' => ['utf-8'],
                ],
            ],
            'text / plain (Plain Text) ; charset = utf-8' => [
                'text / plain (Plain Text) ; charset = utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain (Plain Text); charset="utf-8"',
                ],
                ['text'],
                ['plain', 'Plain Text'],
                true,
                [
                  'charset' => ['utf-8'],
                ],
            ],
            'text / plain ; (Charset=utf-8) charset = utf-8' => [
                'text / plain ; (Charset=utf-8) charset = utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain; charset="utf-8" (Charset=utf-8)',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8', 'Charset=utf-8'],
                ],
            ],
            'text / plain ; charset (Charset) = utf-8' => [
                'text / plain ; charset (Charset) = utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain; charset="utf-8" (Charset)',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8', 'Charset'],
                ],
            ],
            'text / plain ; charset = (UTF8) utf-8' => [
                'text / plain ; charset = (UTF8) utf-8',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain; charset="utf-8" (UTF8)',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8', 'UTF8'],
                ],
            ],
            'text / plain ; charset = utf-8 (UTF-8 Plain Text)' => [
                'text / plain ; charset = utf-8 (UTF-8 Plain Text)',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain; charset="utf-8" (UTF-8 Plain Text)',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8', 'UTF-8 Plain Text'],
                ],
            ],
            'application/x-foobar;description="bbgh(kdur"' => [
                'application/x-foobar;description="bbgh(kdur"',
                [
                  'application/x-foobar',
                  'application/x-foobar; description="bbgh(kdur"',
                  'application/x-foobar; description="bbgh(kdur"',
                ],
                ['application'],
                ['x-foobar'],
                true,
                [
                  'description' => ['bbgh(kdur'],
                ],
            ],
            'application/x-foobar;description="a \"quoted string\""' => [
                'application/x-foobar;description="a \"quoted string\""',
                [
                  'application/x-foobar',
                  'application/x-foobar; description="a \"quoted string\""',
                  'application/x-foobar; description="a \"quoted string\""',
                ],
                ['application'],
                ['x-foobar'],
                true,
                [
                  'description' => ['a "quoted string"'],
                ],
            ],
            'text/xml;description=test' => [
                'text/xml;description=test',
                [
                  'text/xml',
                  'text/xml; description="test"',
                  'text/xml; description="test"',
                ],
                ['text'],
                ['xml'],
                true,
                [
                  'description' => ['test'],
                ],
            ],
            'text/xml;one=test;two=three' => [
                'text/xml;one=test;two=three',
                [
                  'text/xml',
                  'text/xml; one="test"; two="three"',
                  'text/xml; one="test"; two="three"',
                ],
                ['text'],
                ['xml'],
                true,
                [
                  'one' => ['test'],
                  'two' => ['three'],
                ],
            ],
            'text/xml;one="test";two="three"' => [
                'text/xml;one="test";two="three"',
                [
                  'text/xml',
                  'text/xml; one="test"; two="three"',
                  'text/xml; one="test"; two="three"',
                ],
                ['text'],
                ['xml'],
                true,
                [
                  'one' => ['test'],
                  'two' => ['three'],
                ],
            ],
            'text/xml; this="is"; a="parameter" (with a comment)' => [
                'text/xml; this="is"; a="parameter" (with a comment)',
                [
                  'text/xml',
                  'text/xml; this="is"; a="parameter"',
                  'text/xml; this="is"; a="parameter" (with a comment)',
                ],
                ['text'],
                ['xml'],
                true,
                [
                  'this' => ['is'],
                  'a' => ['parameter', 'with a comment'],
                ],
            ],
            // Various edge cases.
            'text/plain; charset="utf-8" (UTF/8)' => [
                'text/plain; charset="utf-8" (UTF/8)',
                [
                  'text/plain',
                  'text/plain; charset="utf-8"',
                  'text/plain; charset="utf-8" (UTF/8)',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'charset' => ['utf-8', 'UTF/8'],
                ],
            ],
            'appf/xml; a=b; b="parameter" (with; a comment)   ;c=d;  e=f (;) ;   g=h   ' => [
                'appf/xml; a=b; b="parameter" (with; a comment)   ;c=d;  e=f (;) ;   g=h   ',
                [
                  'appf/xml',
                  'appf/xml; a="b"; b="parameter"; c="d"; e="f"; g="h"',
                  'appf/xml; a="b"; b="parameter" (with; a comment); c="d"; e="f" (;); g="h"',
                ],
                ['appf'],
                ['xml'],
                true,
                [
                  'a' => ['b'],
                  'b' => ['parameter', 'with; a comment'],
                  'c' => ['d'],
                  'e' => ['f', ';'],
                  'g' => ['h'],
                ],
            ],
            'text/(abc)def(ghi)' => [
                'text/(abc)def(ghi)',
                [
                  'text/def',
                  'text/def',
                  'text/def (abc ghi)',
                ],
                ['text'],
                ['def', 'abc ghi'],
                false,
                [],
            ],
            'text/(abc)def' => [
                'text/(abc)def',
                [
                  'text/def',
                  'text/def',
                  'text/def (abc)',
                ],
                ['text'],
                ['def', 'abc'],
                false,
                [],
            ],
            'text/def(ghi)' => [
                'text/def(ghi)',
                [
                  'text/def',
                  'text/def',
                  'text/def (ghi)',
                ],
                ['text'],
                ['def', 'ghi'],
                false,
                [],
            ],
            'text/plain;a=(\)abc)def(\()' => [
                'text/plain;a=(\)abc)def(\()',
                [
                  'text/plain',
                  'text/plain; a="def"',
                  'text/plain; a="def" (\)abc \()',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'a' => ['def', '\)abc \('],
                ],
            ],
            'text/plain;a=\\foo(abc)' => [
                'text/plain;a=\\foo(abc)',
                [
                  'text/plain',
                  'text/plain; a="foo"',
                  'text/plain; a="foo" (abc)',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'a' => ['foo', 'abc'],
                ],
            ],
            'text/plain;a=(a"bc\)def")def' => [
                'text/plain;a=(a"bc\)def")def',
                [
                  'text/plain',
                  'text/plain; a="def"',
                  'text/plain; a="def" (a"bc\)def")',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'a' => ['def', 'a"bc\)def"'],
                ],
            ],
            'text/plain;a="(abc)def"' => [
                'text/plain;a="(abc)def"',
                [
                  'text/plain',
                  'text/plain; a="(abc)def"',
                  'text/plain; a="(abc)def"',
                ],
                ['text'],
                ['plain'],
                true,
                [
                  'a' => ['(abc)def'],
                ],
            ],
        ];
    }

    /**
     * @param string $type
     * @param string[] $expectedToString
     * @param string[] $expectedMedia
     * @param string[] $expectedSubType
     * @param bool $expectedHasParameters
     * @param string[] $expectedParameters
     */
    #[DataProvider('parseProvider')]
    public function testParse(string $type, array $expectedToString, array $expectedMedia, array $expectedSubType, bool $expectedHasParameters, array $expectedParameters): void
    {
        $mt = new Type($type);
        $this->assertSame($expectedMedia[0], $mt->getMedia());
        if (isset($expectedMedia[1])) {
            $this->assertTrue($mt->hasMediaComment());
            $this->assertSame($expectedMedia[1], $mt->getMediaComment());
        } else {
            $this->assertFalse($mt->hasMediaComment());
        }
        $this->assertSame($expectedSubType[0], $mt->getSubType());
        if (isset($expectedSubType[1])) {
            $this->assertTrue($mt->hasSubTypeComment());
            $this->assertSame($expectedSubType[1], $mt->getSubTypeComment());
        } else {
            $this->assertFalse($mt->hasSubTypeComment());
        }
        $this->assertSame($expectedHasParameters, $mt->hasParameters());
        if ($expectedHasParameters) {
            $this->assertSameSize($expectedParameters, $mt->getParameters());
        }
        foreach ($expectedParameters as $name => $param) {
            $this->assertTrue(isset($mt->getParameters()[$name]));
            $this->assertSame($name, $mt->getParameter($name)->getName());
            $this->assertSame($param[0], $mt->getParameter($name)->getValue());
            if (isset($param[1])) {
                $this->assertTrue($mt->getParameter($name)->hasComment());
                $this->assertSame($param[1], $mt->getParameter($name)->getComment());
            } else {
                $this->assertFalse($mt->getParameter($name)->hasComment());
            }
        }
        $this->assertSame($expectedToString[0], $mt->toString(Type::SHORT_TEXT));
        $this->assertSame($expectedToString[1], $mt->toString(Type::FULL_TEXT));
        $this->assertSame($expectedToString[2], $mt->toString(Type::FULL_TEXT_WITH_COMMENTS));
    }

    /**
     * Data provider for testParseMalformed.
     *
     * @return array<string,array<string>>
     */
    public static function parseMalformedProvider(): array
    {
        return [
            'empty string' => [''],
            'n' => ['n'],
            'no media' => ['/n'],
            'no sub type' => ['n/'],
            'no comment closing bracket a' => ['image (open ()/*'],
            'no comment closing bracket b' => ['image / * (open (())'],
        ];
    }

    #[DataProvider('parseMalformedProvider')]
    public function testParseMalformed(string $type): void
    {
        $this->expectException(MalformedTypeException::class);
        new Type($type);
    }

    public function testParseAgain(): void
    {
        $mt = new Type('application/ogg;description=Hello there!;asd=fgh');
        $this->assertCount(2, $mt->getParameters());

        $mt = new Type('text/plain;hello=there!');
        $this->assertCount(1, $mt->getParameters());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('HTML document', (new Type('text/html'))->getDescription());
        $this->assertSame('HTML document, HTML: HyperText Markup Language', (new Type('text/html'))->getDescription(true));

        $this->assertSame('GPX geographic data', (new Type('application/gpx+xml'))->getDescription());
        $this->assertSame('GPX geographic data, GPX: GPS Exchange Format', (new Type('application/gpx+xml'))->getDescription(true));
        $this->assertSame('GPX geographic data', (new Type('application/gpx'))->getDescription());
        $this->assertSame('GPX geographic data, GPX: GPS Exchange Format', (new Type('application/gpx'))->getDescription(true));
        $this->assertSame('GPX geographic data', (new Type('application/x-gpx'))->getDescription());
        $this->assertSame('GPX geographic data, GPX: GPS Exchange Format', (new Type('application/x-gpx'))->getDescription(true));
    }

    /**
     * Data provider for testMissingDescription.
     *
     * @return array<array<string>>
     */
    public static function missingDescriptionProvider(): array
    {
        return [
            ['*/*'],
            ['image/*'],
            ['application/java*'],
            ['application/x-t3vm-image'],
        ];
    }

    #[DataProvider('missingDescriptionProvider')]
    public function testMissingDescription(string $type): void
    {
        $t = new Type($type);
        $this->assertFalse($t->hasDescription());
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No description available for type: ' . $type);
        $desc = $t->getDescription();
    }

    public function testMissingMediaComment(): void
    {
        $t = new Type('text/plain');
        $this->assertFalse($t->hasMediaComment());
        $this->expectException(UndefinedException::class);
        $this->expectExceptionMessage('Media comment is not defined');
        $comment = $t->getMediaComment();
    }

    public function testMissingSubTypeComment(): void
    {
        $t = new Type('text/plain');
        $this->assertFalse($t->hasSubTypeComment());
        $this->expectException(UndefinedException::class);
        $this->expectExceptionMessage('Subtype comment is not defined');
        $comment = $t->getSubTypeComment();
    }

    public function testMissingParameters(): void
    {
        $t = new Type('text/plain');
        $this->assertFalse($t->hasParameters());
        $this->expectException(UndefinedException::class);
        $this->expectExceptionMessage('No parameters defined');
        $parameters = $t->getParameters();
    }

    public function testMissingParameter(): void
    {
        $t = new Type('text/plain');
        $this->assertFalse($t->hasParameter('foo'));
        $this->expectException(UndefinedException::class);
        $this->expectExceptionMessage('Parameter foo is not defined');
        $parameters = $t->getParameter('foo');
    }

    public function testSetComment(): void
    {
        $type = new Type('text/x-test');
        $type->setMediaComment('media comment');
        $this->assertSame('text (media comment)/x-test', $type->toString(Type::FULL_TEXT_WITH_COMMENTS));
        $type->setSubTypeComment('subtype comment');
        $this->assertSame('text (media comment)/x-test (subtype comment)', $type->toString(Type::FULL_TEXT_WITH_COMMENTS));
        $type->setMediaComment();
        $this->assertSame('text/x-test (subtype comment)', $type->toString(Type::FULL_TEXT_WITH_COMMENTS));
        $type->setSubTypeComment();
        $this->assertSame('text/x-test', $type->toString(Type::FULL_TEXT_WITH_COMMENTS));
    }

    public function testIsExperimental(): void
    {
        $this->assertTrue((new Type('text/x-test'))->isExperimental());
        $this->assertTrue((new Type('image/X-test'))->isExperimental());
        $this->assertFalse((new Type('text/plain'))->isExperimental());
    }

    public function testIsVendor(): void
    {
        $this->assertTrue((new Type('application/vnd.openoffice'))->isVendor());
        $this->assertFalse((new Type('application/vendor.openoffice'))->isVendor());
        $this->assertFalse((new Type('vnd/fsck'))->isVendor());
    }

    public function testIsWildcard(): void
    {
        $this->assertTrue((new Type('*/*'))->isWildcard());
        $this->assertTrue((new Type('image/*'))->isWildcard());
        $this->assertFalse((new Type('text/plain'))->isWildcard());

        $this->assertTrue((new Type('application/java*'))->isWildcard());
        $this->assertTrue((new Type('application/java-*'))->isWildcard());
    }

    public function testIsAlias(): void
    {
        $this->assertFalse((new Type('*/*'))->isAlias());
        $this->assertFalse((new Type('image/*'))->isAlias());
        $this->assertFalse((new Type('text/plain'))->isAlias());
        $this->assertFalse((new Type('application/java*'))->isAlias());
        $this->assertTrue((new Type('text/x-markdown'))->isAlias());
    }

    public function testWildcardMatch(): void
    {
        $this->assertTrue((new Type('image/png'))->wildcardMatch('*/*'));
        $this->assertTrue((new Type('image/png'))->wildcardMatch('image/*'));
        $this->assertFalse((new Type('text/plain'))->wildcardMatch('image/*'));
        $this->assertFalse((new Type('image/png'))->wildcardMatch('image/foo'));

        $this->assertTrue((new Type('application/javascript'))->wildcardMatch('application/java*'));
        $this->assertTrue((new Type('application/java-serialized-object'))->wildcardMatch('application/java-*'));
        $this->assertFalse((new Type('application/javascript'))->wildcardMatch('application/java-*'));
    }

    public function testAddParameter(): void
    {
        $mt = new Type('image/png; foo=bar');
        $mt->addParameter('baz', 'val', 'this is a comment');
        $res = $mt->toString(Type::FULL_TEXT_WITH_COMMENTS);
        $this->assertStringContainsString('foo=', $res);
        $this->assertStringContainsString('bar', $res);
        $this->assertStringContainsString('baz=', $res);
        $this->assertStringContainsString('val', $res);
        $this->assertStringContainsString('(this is a comment)', $res);
        $this->assertSame('image/png; foo="bar"; baz="val" (this is a comment)', $res);
    }

    public function testRemoveParameter(): void
    {
        $mt = new Type('image/png; foo=bar;baz=val(this is a comment)');
        $mt->removeParameter('foo');
        $res = $mt->toString(Type::FULL_TEXT_WITH_COMMENTS);
        $this->assertStringNotContainsString('foo=', $res);
        $this->assertStringNotContainsString('bar', $res);
        $this->assertStringContainsString('baz=', $res);
        $this->assertSame('image/png; baz="val" (this is a comment)', $res);
    }

    public function testGetAliases(): void
    {
        $this->assertSame(['image/x-wmf', 'image/x-win-metafile', 'application/x-wmf', 'application/wmf', 'application/x-msmetafile'], (new Type('image/wmf'))->getAliases());
    }

    public function testGetAliasesOnAlias(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot get aliases for 'image/x-wmf', it is an alias itself");
        $this->assertSame([], (new Type('image/x-wmf'))->getAliases());
    }

    public function testGetAliasesOnMissingType(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type found for foo/bar in map");
        $this->assertSame([], (new Type('foo/bar'))->getAliases());
    }

    public function testGetExtensions(): void
    {
        $this->assertEquals(['atom'], (new Type('application/atom+xml'))->getExtensions());
        $this->assertEquals(['pgp', 'gpg', 'asc', 'skr', 'pkr', 'key', 'sig'], (new Type('application/pgp*'))->getExtensions());
        $this->assertEquals(['asc', 'sig', 'pgp', 'gpg'], (new Type('application/pgp-s*'))->getExtensions());

        $this->assertEquals(['123', 'wk1', 'wk3', 'wk4', 'wks'], (new Type('application/vnd.lotus-1-2-3'))->getExtensions());
        $this->assertEquals(['602'], (new Type('application/x-t602'))->getExtensions());

        $this->assertSame(['smi', 'smil', 'sml', 'kino'], (new Type('application/smil+xml'))->getExtensions());
        $this->assertSame(['smi', 'smil', 'sml', 'kino'], (new Type('application/smil'))->getExtensions());
    }

    public function testGetExtensionsFail(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type found for application/a000 in map");
        $extensions = (new Type('application/a000'))->getExtensions();
    }

    public function testGetDefaultExtension(): void
    {
        $this->assertEquals('atom', (new Type('application/atom+xml'))->getDefaultExtension());
        $this->assertEquals('csv', (new Type('text/csv'))->getDefaultExtension());

        $this->assertSame('smi', (new Type('application/smil+xml'))->getDefaultExtension());
        $this->assertSame('smi', (new Type('application/smil'))->getDefaultExtension());
    }

    /**
     * Data provider for testGetDefaultExtensionFail.
     *
     * @return array<array<string>>
     */
    public static function getDefaultExtensionFailProvider()
    {
        return [
            ['*/*'],
            ['n/n'],
            ['image/*'],
            ['application/pgp*'],
        ];
    }

    #[DataProvider('getDefaultExtensionFailProvider')]
    public function testGetDefaultExtensionFail(string $type): void
    {
        $this->expectException(MappingException::class);
        $this->assertSame('', (new Type($type))->getDefaultExtension());
    }
}
