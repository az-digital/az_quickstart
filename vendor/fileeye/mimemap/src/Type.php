<?php declare(strict_types=1);

namespace FileEye\MimeMap;

use FileEye\MimeMap\Map\MimeMapInterface;

/**
 * Class for working with MIME types
 */
class Type implements TypeInterface
{
    /**
     * Short format [e.g. image/jpeg] for strings.
     *
     * @phpstan-var int
     */
    const SHORT_TEXT = 0;

    /**
     * Full format [e.g. image/jpeg; p="1"] for strings.
     *
     * @phpstan-var int
     */
    const FULL_TEXT = 1;

    /**
     * Full format with comments [e.g. image/jpeg; p="1" (comment)] for strings.
     *
     * @phpstan-var int
     */
    const FULL_TEXT_WITH_COMMENTS = 2;

    /**
     * The MIME media type.
     */
    protected string $media;

    /**
     * The MIME media type comment.
     */
    protected ?string $mediaComment = null;

    /**
     * The MIME media sub-type.
     */
    protected string $subType;

    /**
     * The MIME media sub-type comment.
     */
    protected ?string $subTypeComment = null;

    /**
     *  MIME type descriptions.
     *
     * @var list<string>
     */
    protected array $descriptions = [];

    /**
     * Optional MIME parameters.
     *
     * @var array<string,TypeParameter>
     */
    protected array $parameters = [];

    /**
     * The MIME types map.
     *
     * @var MimeMapInterface
     */
    protected readonly MimeMapInterface $map;

    public function __construct(string $typeString, ?string $mapClass = null)
    {
        TypeParser::parse($typeString, $this);
        $this->map = MapHandler::map($mapClass);
    }

    public function getMedia(): string
    {
        return $this->media;
    }

    public function setMedia(string $media): TypeInterface
    {
        $this->media = $media;
        return $this;
    }

    public function hasMediaComment(): bool
    {
        return $this->mediaComment !== null;
    }

    public function getMediaComment(): string
    {
        if ($this->hasMediaComment()) {
            assert(is_string($this->mediaComment));
            return $this->mediaComment;
        }
        throw new UndefinedException('Media comment is not defined');
    }

    public function setMediaComment(?string $comment = null): TypeInterface
    {
        $this->mediaComment = $comment;
        return $this;
    }

    public function getSubType(): string
    {
        return $this->subType;
    }

    public function setSubType(string $subType): TypeInterface
    {
        $this->subType = $subType;
        return $this;
    }

    public function hasSubTypeComment(): bool
    {
        return $this->subTypeComment !== null;
    }

    public function getSubTypeComment(): string
    {
        if ($this->hasSubTypeComment()) {
            assert(is_string($this->subTypeComment));
            return $this->subTypeComment;
        }
        throw new UndefinedException('Subtype comment is not defined');
    }

    public function setSubTypeComment(?string $comment = null): TypeInterface
    {
        $this->subTypeComment = $comment;
        return $this;
    }

    public function hasParameters(): bool
    {
        return (bool) $this->parameters;
    }

    public function getParameters(): array
    {
        if ($this->hasParameters()) {
            return $this->parameters;
        }
        throw new UndefinedException("No parameters defined");
    }

    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    public function getParameter(string $name): TypeParameter
    {
        if ($this->hasParameter($name)) {
            return $this->parameters[$name];
        }
        throw new UndefinedException("Parameter $name is not defined");
    }

    public function addParameter(string $name, string $value, ?string $comment = null): void
    {
        $this->parameters[$name] = new TypeParameter($name, $value, $comment);
    }

    public function removeParameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    public function toString(int $format = Type::FULL_TEXT): string
    {
        $type = strtolower($this->media);
        if ($format > Type::FULL_TEXT && $this->hasMediaComment()) {
            $type .= ' (' .  $this->getMediaComment() . ')';
        }
        $type .= '/' . strtolower($this->subType);
        if ($format > Type::FULL_TEXT && $this->hasSubTypeComment()) {
            $type .= ' (' .  $this->getSubTypeComment() . ')';
        }
        if ($format > Type::SHORT_TEXT && count($this->parameters)) {
            foreach ($this->parameters as $parameter) {
                $type .= '; ' . $parameter->toString($format);
            }
        }
        return $type;
    }

    public function isExperimental(): bool
    {
        return substr($this->getMedia(), 0, 2) == 'x-' || substr($this->getSubType(), 0, 2) == 'x-';
    }

    public function isVendor(): bool
    {
        return substr($this->getSubType(), 0, 4) == 'vnd.';
    }

    public function isWildcard(): bool
    {
        return ($this->getMedia() === '*' && $this->getSubtype() === '*') || strpos($this->getSubtype(), '*') !== false;
    }

    public function isAlias(): bool
    {
        return $this->map->hasAlias($this->toString(static::SHORT_TEXT));
    }

    public function wildcardMatch(string $wildcard): bool
    {
        $wildcardType = new static($wildcard);

        if (!$wildcardType->isWildcard()) {
            return false;
        }

        $wildcardRe = strtr($wildcardType->toString(static::SHORT_TEXT), [
            '/' => '\\/',
            '*' => '.*',
        ]);
        $subject = $this->toString(static::SHORT_TEXT);

        return preg_match("/{$wildcardRe}/", $subject) === 1;
    }

    public function buildTypesList(): array
    {
        $subject = $this->toString(static::SHORT_TEXT);

        // Find all types.
        $types = [];
        if (!$this->isWildcard()) {
            if ($this->map->hasType($subject)) {
                $types[] = $subject;
            }
        } else {
            foreach ($this->map->listTypes($subject) as $t) {
                $types[] = $t;
            }
        }

        if (!empty($types)) {
            return $types;
        }

        throw new MappingException('No MIME type found for ' . $subject . ' in map');
    }

    /**
     * Returns the unaliased MIME type.
     *
     * @return TypeInterface
     *   $this if the current type is not an alias, the parent type if the
     *   current type is an alias.
     */
    protected function getUnaliasedType(): TypeInterface
    {
        return $this->isAlias() ? new static($this->map->getAliasTypes($this->toString(static::SHORT_TEXT))[0]) : $this;
    }

    public function hasDescription(): bool
    {
        if ($this->descriptions === []) {
            $this->descriptions = $this->map->getTypeDescriptions($this->getUnaliasedType()->toString(static::SHORT_TEXT));
        }
        return isset($this->descriptions[0]);
    }

    public function getDescription(bool $includeAcronym = false): string
    {
        if (!$this->hasDescription()) {
            throw new MappingException('No description available for type: ' . $this->toString(static::SHORT_TEXT));
        }

        $res = $this->descriptions[0];
        if ($includeAcronym && isset($this->descriptions[1])) {
            $res .= ', ' . $this->descriptions[1];
        }

        return $res;
    }

    public function getAliases(): array
    {
        // Fail if the current type is an alias already.
        if ($this->isAlias()) {
            $subject = $this->toString(static::SHORT_TEXT);
            throw new MappingException("Cannot get aliases for '{$subject}', it is an alias itself");
        }

        // Build the array of aliases.
        $aliases = [];
        foreach ($this->buildTypesList() as $t) {
            foreach ($this->map->getTypeAliases((string) $t) as $a) {
                $aliases[$a] = $a;
            }
        }

        return array_keys($aliases);
    }

    public function getDefaultExtension(): string
    {
        $unaliasedType = $this->getUnaliasedType();
        $subject = $unaliasedType->toString(static::SHORT_TEXT);

        if (!$unaliasedType->isWildcard()) {
            $proceed = $this->map->hasType($subject);
        } else {
            $proceed = count($this->map->listTypes($subject)) === 1;
        }

        if ($proceed) {
            return $unaliasedType->getExtensions()[0];
        }

        throw new MappingException('Cannot determine default extension for type: ' . $unaliasedType->toString(static::SHORT_TEXT));
    }

    public function getExtensions(): array
    {
        // Build the array of extensions.
        $extensions = [];
        foreach ($this->getUnaliasedType()->buildTypesList() as $t) {
            foreach ($this->map->getTypeExtensions((string) $t) as $e) {
                $extensions[$e] = $e;
            }
        }
        return array_values($extensions);
    }
}
