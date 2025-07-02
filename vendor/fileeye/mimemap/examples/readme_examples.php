<?php declare(strict_types=1);
/**
 * Examples for the README file.
 */

use FileEye\MimeMap\Type;
use FileEye\MimeMap\Extension;

require_once __DIR__ . '/../vendor/autoload.php';

// -------------------

$type = new Type('image/jpeg');

print_r($type->getExtensions());
// will print ['jpeg', 'jpg', 'jpe']

print_r($type->getDefaultExtension());
// will print 'jpeg'

// When passing an alias to a MIME type, the API will
// return the extensions to the parent type:
$type = new Type('image/pdf');

print_r($type->getDefaultExtension());
// will print 'pdf' which is the default extension for 'application/pdf'

// -------------------

$ext = new Extension('xar');

print_r($ext->getTypes());
// will return ['application/vnd.xara', 'application/x-xar']

print_r($ext->getDefaultType());
// will return 'application/vnd.xara'

// -------------------

$type = new Type('text / (Unstructured text)  plain  ; charset = (UTF8, not ASCII) utf-8');
$type->addParameter('lang', 'it', 'Italian');

echo $type->toString(Type::SHORT_TEXT);
// will print 'text/plain'

echo $type->toString(Type::FULL_TEXT);
// will print 'text/plain; charset="utf-8"; lang="it"'

echo $type->toString(Type::FULL_TEXT_WITH_COMMENTS);
// will print 'text/plain (Unstructured text); charset="utf-8" (UTF8, not ASCII), lang="it" (Italian)'

// -------------------

$type = new Type('text/html');

$type_desc = $type->getDescription();
$type->setSubTypeComment($type_desc);
echo $type->toString(Type::FULL_TEXT_WITH_COMMENTS);
// will print 'text/html (HTML document)'

// Setting the $include_acronym parameter of getDescription to true
// will extend the description to include the meaning of the acronym
$type_desc = $type->getDescription(true);
$type->setSubTypeComment($type_desc);
echo $type->toString(Type::FULL_TEXT_WITH_COMMENTS);
// will print 'text/html (HTML document, HTML: HyperText Markup Language)'
