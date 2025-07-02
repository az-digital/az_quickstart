<?php declare(strict_types=1);

namespace FileEye\MimeMap\Test;

use FileEye\MimeMap\Extension;
use FileEye\MimeMap\MappingException;

class ExtensionTest extends MimeMapTestBase
{
    public function testGetDefaultType(): void
    {
        $this->assertSame('text/plain', (new Extension('txt'))->getDefaultType());
        $this->assertSame('text/plain', (new Extension('TXT'))->getDefaultType());
        $this->assertSame('image/png', (new Extension('png'))->getDefaultType());
        $this->assertSame('application/vnd.oasis.opendocument.text', (new Extension('odt'))->getDefaultType());
    }

    public function testGetStrictDefaultTypeUnknownExtension(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type mapped to extension ohmygodthatisnoextension");
        $this->assertSame('application/octet-stream', (new Extension('ohmygodthatisnoextension'))->getDefaultType());
    }

    public function testGetTypes(): void
    {
        $this->assertSame(['text/vnd.dvb.subtitle', 'image/vnd.dvb.subtitle', 'text/x-microdvd', 'text/x-mpsub', 'text/x-subviewer'], (new Extension('sub'))->getTypes());
        $this->assertSame(['text/vnd.dvb.subtitle', 'image/vnd.dvb.subtitle', 'text/x-microdvd', 'text/x-mpsub', 'text/x-subviewer'], (new Extension('sUb'))->getTypes());
    }

    public function testGetStrictTypesUnknownExtension(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No MIME type mapped to extension ohmygodthatisnoextension");
        $this->assertSame(['application/octet-stream'], (new Extension('ohmygodthatisnoextension'))->getTypes());
    }
}
