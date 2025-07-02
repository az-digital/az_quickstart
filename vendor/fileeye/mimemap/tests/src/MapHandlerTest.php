<?php declare(strict_types=1);

namespace FileEye\MimeMap\Test;

use FileEye\MimeMap\Extension;
use FileEye\MimeMap\MalformedTypeException;
use FileEye\MimeMap\Map\DefaultMap;
use FileEye\MimeMap\Map\EmptyMap;
use FileEye\MimeMap\Map\MimeMapInterface;
use FileEye\MimeMap\MapHandler;
use FileEye\MimeMap\MappingException;
use FileEye\MimeMap\Type;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\DataProvider;

#[BackupStaticProperties(true)]
class MapHandlerTest extends MimeMapTestBase
{
    protected readonly MimeMapInterface $map;

    public function setUp(): void
    {
        $this->map = MapHandler::map();
    }

    public function testSetDefaultMapClass(): void
    {
        MapHandler::setDefaultMapClass(EmptyMap::class);
        $this->assertInstanceOf(EmptyMap::class, MapHandler::map());
        MapHandler::setDefaultMapClass(DefaultMap::class);
        // @phpstan-ignore method.impossibleType
        $this->assertInstanceOf(DefaultMap::class, MapHandler::map());
    }

    public function testMap(): void
    {
        $this->assertStringContainsString('DefaultMap.php', $this->map->getFileName());
    }

    public function testSort(): void
    {
        $this->map->addTypeExtensionMapping('aaa/aaa', '000a')->sort();
        $this->assertSame('aaa/aaa', $this->map->listTypes()[0]);
        $this->assertSame('000a', $this->map->listExtensions()[0]);
    }

    public function testAdd(): void
    {
        // Adding a new type with a new extension.
        $this->map->addTypeExtensionMapping('bingo/bongo', 'bngbng');
        $this->map->addTypeDescription('bingo/bongo', 'Bingo, Bongo!');
        $this->assertSame(['bngbng'], (new Type('bingo/bongo'))->getExtensions());
        $this->assertSame('bngbng', (new Type('bingo/bongo'))->getDefaultExtension());
        $this->assertSame('Bingo, Bongo!', (new Type('bingo/bongo'))->getDescription());
        $this->assertSame(['bingo/bongo'], (new Extension('bngbng'))->getTypes());
        $this->assertSame('bingo/bongo', (new Extension('bngbng'))->getDefaultType());

        // Adding an already existing mapping should not duplicate entries.
        $this->map->addTypeExtensionMapping('bingo/bongo', 'bngbng');
        $this->assertSame(['bngbng'], (new Type('bingo/bongo'))->getExtensions());
        $this->assertSame(['bingo/bongo'], (new Extension('bngbng'))->getTypes());

        // Adding another extension to existing type.
        $this->map->addTypeExtensionMapping('bingo/bongo', 'bigbog');
        $this->assertSame(['bngbng', 'bigbog'], (new Type('bingo/bongo'))->getExtensions());
        $this->assertSame(['bingo/bongo'], (new Extension('bigbog'))->getTypes());

        // Adding another type to existing extension.
        $this->map->addTypeExtensionMapping('boing/being', 'bngbng');
        $this->assertSame(['bngbng'], (new Type('boing/being'))->getExtensions());
        $this->assertSame(['bingo/bongo', 'boing/being'], (new Extension('bngbng'))->getTypes());
    }

    public function testRemove(): void
    {
        $this->assertSame(['txt', 'text', 'conf', 'def', 'list', 'log', 'in', 'asc'], (new Type('text/plain'))->getExtensions());
        $this->assertSame('txt', (new Type('text/plain'))->getDefaultExtension());
        $this->assertSame(['text/plain'], (new Extension('txt'))->getTypes());
        $this->assertSame('text/plain', (new Extension('txt'))->getDefaultType());

        // Remove an existing type-extension pair.
        $this->assertTrue($this->map->removeTypeExtensionMapping('text/plain', 'txt'));
        $this->assertSame(['text', 'conf', 'def', 'list', 'log', 'in', 'asc'], (new Type('text/plain'))->getExtensions());

        // Try removing a non-existing extension.
        $this->assertFalse($this->map->removeTypeExtensionMapping('text/plain', 'axx'));

        // Remove an existing alias.
        $this->assertSame(['application/x-pdf', 'image/pdf', 'application/acrobat', 'application/nappdf'], (new Type('application/pdf'))->getAliases());
        $this->assertTrue($this->map->removeTypeAlias('application/pdf', 'application/x-pdf'));
        $this->assertSame(['image/pdf', 'application/acrobat', 'application/nappdf'], (new Type('application/pdf'))->getAliases());

        // Try removing a non-existing alias.
        $this->assertFalse($this->map->removeTypeAlias('application/pdf', 'foo/bar'));
        $this->assertSame(['image/pdf', 'application/acrobat', 'application/nappdf'], (new Type('application/pdf'))->getAliases());

        // Try removing a non-existing type.
        $this->assertFalse($this->map->removeType('axx/axx'));
    }

    public function testGetExtensionTypesAfterTypeExtensionMappingRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type mapped to extension txt");
        $this->assertTrue($this->map->removeTypeExtensionMapping('text/plain', 'txt'));
        $types = (new Extension('txt'))->getTypes();
    }

    public function testGetExtensionDefaultTypeAfterTypeExtensionMappingRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type mapped to extension txt");
        $this->assertTrue($this->map->removeTypeExtensionMapping('text/plain', 'txt'));
        $defaultType = (new Extension('txt'))->getDefaultType();
    }

    public function testGetTypeExtensionsAfterTypeRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type found for text/plain in map");
        $this->assertTrue($this->map->removeType('text/plain'));
        $extensions = (new Type('text/plain'))->getExtensions();
    }

    public function testGetExtensionTypesAfterTypeRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No MIME type mapped to extension def');
        $this->assertTrue($this->map->removeType('text/plain'));
        $types = (new Extension('DEf'))->getTypes();
    }

    public function testGetExtensionDefaultTypeAfterTypeRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No MIME type mapped to extension def');
        $this->assertTrue($this->map->removeType('text/plain'));
        $defaultType = (new Extension('DeF'))->getDefaultType();
    }

    public function testGetTypeExtensionsAfterTypeWithAliasRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No MIME type found for text/markdown in map');
        $this->assertTrue($this->map->hasAlias('text/x-markdown'));
        $this->assertTrue($this->map->removeType('text/markdown'));
        $this->assertFalse($this->map->hasAlias('text/x-markdown'));
        $extensions = (new Type('text/markdown'))->getExtensions();
    }

    public function testGetAliasExtensionsAfterTypeWithAliasRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type found for text/x-markdown in map");
        $this->assertTrue($this->map->hasAlias('text/x-markdown'));
        $this->assertTrue($this->map->removeType('text/markdown'));
        $this->assertFalse($this->map->hasAlias('text/x-markdown'));
        $extensions = (new Type('text/x-markdown'))->getExtensions();
    }

    public function testGetExtensionTypesAfterTypeWithAliasRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type mapped to extension lyx");
        $this->assertTrue($this->map->hasAlias('text/x-lyx'));
        $this->assertTrue($this->map->removeType('application/x-lyx'));
        $this->assertFalse($this->map->hasAlias('text/x-lyx'));
        $types = (new Extension('LYX'))->getTypes();
    }

    public function testGetExtensionDefaultTypeAfterTypeWithAliasRemoval(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type mapped to extension lyx");
        $this->assertTrue($this->map->hasAlias('text/x-lyx'));
        $this->assertTrue($this->map->removeType('application/x-lyx'));
        $this->assertFalse($this->map->hasAlias('text/x-lyx'));
        $defaultType = (new Extension('lyx'))->getDefaultType();
    }

    public function testHasType(): void
    {
        $this->assertTrue($this->map->hasType('text/plain'));
        $this->assertFalse($this->map->hasType('foo/bar'));
    }

    public function testHasAlias(): void
    {
        $this->assertTrue($this->map->hasAlias('application/acrobat'));
        $this->assertFalse($this->map->hasAlias('foo/bar'));
    }

    public function testHasExtension(): void
    {
        $this->assertTrue($this->map->hasExtension('jpg'));
        $this->assertFalse($this->map->hasExtension('jpgjpg'));
    }

    public function testSetExtensionDefaultType(): void
    {
        $this->assertSame(['text/vnd.dvb.subtitle', 'image/vnd.dvb.subtitle', 'text/x-microdvd', 'text/x-mpsub', 'text/x-subviewer'], (new Extension('sub'))->getTypes());
        $this->map->setExtensionDefaultType('SUB', 'image/vnd.dvb.subtitle');
        $this->assertSame(['image/vnd.dvb.subtitle', 'text/vnd.dvb.subtitle', 'text/x-microdvd', 'text/x-mpsub', 'text/x-subviewer'], (new Extension('SUB'))->getTypes());
    }

    public function testAddAliasToType(): void
    {
        $this->assertSame(['image/psd', 'image/x-psd', 'image/photoshop', 'image/x-photoshop', 'application/photoshop', 'application/x-photoshop',], (new Type('image/vnd.adobe.photoshop'))->getAliases());
        $this->map->addTypeAlias('image/vnd.adobe.photoshop', 'application/x-foo-bar');
        $this->assertSame(['image/psd', 'image/x-psd', 'image/photoshop', 'image/x-photoshop', 'application/photoshop', 'application/x-photoshop', 'application/x-foo-bar',], (new Type('image/vnd.adobe.photoshop'))->getAliases());
        $this->assertContains('application/x-foo-bar', $this->map->listAliases());
    }

    public function testReAddAliasToType(): void
    {
        $this->assertSame(['image/psd', 'image/x-psd', 'image/photoshop', 'image/x-photoshop', 'application/photoshop', 'application/x-photoshop',], (new Type('image/vnd.adobe.photoshop'))->getAliases());
        $this->map->addTypeAlias('image/vnd.adobe.photoshop', 'application/x-photoshop');
        $this->assertSame(['image/psd', 'image/x-psd', 'image/photoshop', 'image/x-photoshop', 'application/photoshop', 'application/x-photoshop',], (new Type('image/vnd.adobe.photoshop'))->getAliases());
    }

    public function testAddAliasToMultipleTypes(): void
    {
        $this->assertSame([], (new Type('text/plain'))->getAliases());
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot set 'application/x-photoshop' as alias for 'text/plain', it is an alias of 'image/vnd.adobe.photoshop' already");
        $this->map->addTypeAlias('text/plain', 'application/x-photoshop');
        $this->assertSame([], (new Type('text/plain'))->getAliases());
    }

    public function testAddAliasToMissingType(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot set 'baz/qoo' as alias for 'bar/foo', 'bar/foo' not defined");
        $this->map->addTypeAlias('bar/foo', 'baz/qoo');
    }

    public function testAddAliasIsATypeAlready(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot set 'text/plain' as alias for 'text/richtext', 'text/plain' is already defined as a type");
        $this->map->addTypeAlias('text/richtext', 'text/plain');
    }

    public function testAddDescriptionToAlias(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot add description for 'application/acrobat', 'application/acrobat' is an alias");
        $this->map->addTypeDescription('application/acrobat', 'description of alias');
    }

    public function testSetExtensionDefaultTypeNoExtension(): void
    {
        $this->expectException(MappingException::class);
        $this->map->setExtensionDefaultType('zxzx', 'image/vnd.dvb.subtitle');
    }

    public function testSetExtensionDefaultTypeNoType(): void
    {
        $this->expectException(MappingException::class);
        $this->map->setExtensionDefaultType('sub', 'image/bingo');
    }

    /**
     * Check that a type alias can be set as extension default.
     */
    public function testSetExtensionDefaultTypeToAlias(): void
    {
        $this->assertSame(['application/pdf'], (new Extension('pdf'))->getTypes());

        $this->map->setExtensionDefaultType('pdf', 'application/x-pdf');
        $this->assertSame(['application/x-pdf', 'application/pdf'], (new Extension('pdf'))->getTypes());
        $this->assertSame('application/x-pdf', (new Extension('pdf'))->getDefaultType());

        $this->map->setExtensionDefaultType('pdf', 'image/pdf');
        $this->assertSame(['image/pdf', 'application/x-pdf', 'application/pdf'], (new Extension('pdf'))->getTypes());
        $this->assertSame('image/pdf', (new Extension('pdf'))->getDefaultType());

        // Remove the alias, should be removed from extension types.
        $this->assertTrue($this->map->removeTypeAlias('application/pdf', 'application/x-pdf'));
        $this->assertSame(['image/pdf', 'application/pdf'], (new Extension('pdf'))->getTypes());
        $this->assertSame('image/pdf', (new Extension('pdf'))->getDefaultType());

        // Add a fake MIME type to 'psd', an alias to that, then remove
        // 'image/vnd.adobe.photoshop'.
        $this->assertSame(['image/vnd.adobe.photoshop'], (new Extension('psd'))->getTypes());
        $this->assertSame('image/vnd.adobe.photoshop', (new Extension('psd'))->getDefaultType());
        $this->map->setExtensionDefaultType('psd', 'image/psd');
        $this->assertSame(['image/psd', 'image/vnd.adobe.photoshop'], (new Extension('psd'))->getTypes());
        $this->assertSame('image/psd', (new Extension('psd'))->getDefaultType());
        $this->map->addTypeExtensionMapping('bingo/bongo', 'psd');
        $this->assertSame(['image/psd', 'image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->addTypeAlias('bingo/bongo', 'bar/foo');
        $this->assertSame(['image/psd', 'image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->setExtensionDefaultType('psd', 'bar/foo');
        $this->assertSame(['bar/foo', 'image/psd', 'image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->assertTrue($this->map->removeType('image/vnd.adobe.photoshop'));
        $this->assertSame(['bar/foo', 'bingo/bongo'], (new Extension('psd'))->getTypes());
    }

    /**
     * Check removing an aliased type mapping.
     */
    public function testRemoveAliasedTypeMapping(): void
    {
        $this->map->addTypeExtensionMapping('bingo/bongo', 'psd');
        $this->assertSame(['image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->addTypeAlias('bingo/bongo', 'bar/foo');
        $this->assertSame(['image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->setExtensionDefaultType('psd', 'bar/foo');
        $this->assertSame(['bar/foo', 'image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->removeTypeExtensionMapping('bar/foo', 'psd');
        $this->assertSame(['image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
    }

    /**
     * Check that a removing a type mapping also remove its aliases.
     */
    public function testRemoveUnaliasedTypeMapping(): void
    {
        // Add a fake MIME type to 'psd', an alias to that, then remove
        // 'image/vnd.adobe.photoshop'.
        $this->map->addTypeExtensionMapping('bingo/bongo', 'psd');
        $this->assertSame(['image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->addTypeAlias('bingo/bongo', 'bar/foo');
        $this->assertSame(['image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->setExtensionDefaultType('psd', 'bar/foo');
        $this->assertSame(['bar/foo', 'image/vnd.adobe.photoshop', 'bingo/bongo'], (new Extension('psd'))->getTypes());
        $this->map->removeTypeExtensionMapping('bingo/bongo', 'psd');
        $this->assertSame(['image/vnd.adobe.photoshop'], (new Extension('psd'))->getTypes());
    }

    public function testSetExtensionDefaultTypeToInvalidAlias(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot set 'image/psd' as default type for extension 'pdf', its unaliased type 'image/vnd.adobe.photoshop' is not associated to 'pdf'");
        $this->map->setExtensionDefaultType('pdf', 'image/psd');
    }

    public function testSetTypeDefaultExtension(): void
    {
        $this->assertSame(['jpeg', 'jpg', 'jpe', 'jfif'], (new Type('image/jpeg'))->getExtensions());
        $this->map->setTypeDefaultExtension('image/jpeg', 'jpg');
        $this->assertSame(['jpg', 'jpeg', 'jpe', 'jfif'], (new Type('image/JPEG'))->getExtensions());
    }

    public function testSetTypeDefaultExtensionNoExtension(): void
    {
        $this->expectException(MappingException::class);
        $this->map->setTypeDefaultExtension('image/jpeg', 'zxzx');
    }

    public function testSetTypeDefaultExtensionNoType(): void
    {
        $this->expectException(MappingException::class);
        $this->map->setTypeDefaultExtension('image/bingo', 'jpg');
    }

    public function testAddExtensionToAlias(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Cannot map 'pdf' to 'application/acrobat', 'application/acrobat' is an alias");
        $this->map->addTypeExtensionMapping('application/acrobat', 'pdf');
    }

    /**
     * Data provider for testAddMalformedTypeExtensionMapping.
     *
     * @return array<string,array<string>>
     */
    public static function malformedTypeProvider(): array
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

    #[DataProvider('malformedTypeProvider')]
    public function testAddMalformedTypeExtensionMapping(string $type): void
    {
        $this->expectException(MalformedTypeException::class);
        $this->map->addTypeExtensionMapping($type, 'xxx');
    }
}
