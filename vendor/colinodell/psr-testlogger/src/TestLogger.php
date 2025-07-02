<?php

declare(strict_types=1);

namespace ColinODell\PsrTestLogger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;

/**
 * Used for testing purposes.
 *
 * It records all records and gives you access to them for verification.
 *
 * @method bool hasEmergency(string|array $record)
 * @method bool hasAlert(string|array $record)
 * @method bool hasCritical(string|array $record)
 * @method bool hasError(string|array $record)
 * @method bool hasWarning(string|array $record)
 * @method bool hasNotice(string|array $record)
 * @method bool hasInfo(string|array $record)
 * @method bool hasDebug(string|array $record)
 * @method bool hasEmergencyRecords()
 * @method bool hasAlertRecords()
 * @method bool hasCriticalRecords()
 * @method bool hasErrorRecords()
 * @method bool hasWarningRecords()
 * @method bool hasNoticeRecords()
 * @method bool hasInfoRecords()
 * @method bool hasDebugRecords()
 * @method bool hasEmergencyThatContains(string $message)
 * @method bool hasAlertThatContains(string $message)
 * @method bool hasCriticalThatContains(string $message)
 * @method bool hasErrorThatContains(string $message)
 * @method bool hasWarningThatContains(string $message)
 * @method bool hasNoticeThatContains(string $message)
 * @method bool hasInfoThatContains(string $message)
 * @method bool hasDebugThatContains(string $message)
 * @method bool hasEmergencyThatMatches(string $regex)
 * @method bool hasAlertThatMatches(string $regex)
 * @method bool hasCriticalThatMatches(string $regex)
 * @method bool hasErrorThatMatches(string $regex)
 * @method bool hasWarningThatMatches(string $regex)
 * @method bool hasNoticeThatMatches(string $regex)
 * @method bool hasInfoThatMatches(string $regex)
 * @method bool hasDebugThatMatches(string $regex)
 * @method bool hasEmergencyThatPasses(callable $predicate)
 * @method bool hasAlertThatPasses(callable $predicate)
 * @method bool hasCriticalThatPasses(callable $predicate)
 * @method bool hasErrorThatPasses(callable $predicate)
 * @method bool hasWarningThatPasses(callable $predicate)
 * @method bool hasNoticeThatPasses(callable $predicate)
 * @method bool hasInfoThatPasses(callable $predicate)
 * @method bool hasDebugThatPasses(callable $predicate)
 *
 * Adapted from psr/log,
 * Copyright (c) 2012 PHP Framework Interoperability Group
 * Used under the MIT license
 */
final class TestLogger extends AbstractLogger
{
    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    /** @var array<string|int, array<int, array<string, mixed>>> */
    public array $recordsByLevel = [];

    /**
     * {@inheritDoc}
     *
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        if (! (\is_string($level) || \is_int($level))) {
            throw new InvalidArgumentException('Unsupported log level. The psr-testlogger library only supports string and integer log levels; passed ' . \print_r($level, true));
        }

        $record = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->recordsByLevel[$record['level']][] = $record;
        $this->records[]                          = $record;
    }

    public function hasRecords(string|int|null $level = null): bool
    {
        if ($level === null) {
            return \count($this->records) !== 0;
        }

        return isset($this->recordsByLevel[$level]);
    }

    /**
     * @param string|array<string, mixed> $record
     */
    public function hasRecord(string|array $record, string|int|null $level = null): bool
    {
        if (\is_string($record)) {
            $record = ['message' => $record];
        }

        return $this->hasRecordThatPasses(static function (array $rec) use ($record) {
            if ((string) $rec['message'] !== (string) $record['message']) {
                return false;
            }

            return ! isset($record['context']) || $rec['context'] === $record['context'];
        }, $level);
    }

    public function hasRecordThatContains(string $message, string|int|null $level = null): bool
    {
        return $this->hasRecordThatPasses(static function (array $rec) use ($message) {
            return \str_contains((string) $rec['message'], $message);
        }, $level);
    }

    public function hasRecordThatMatches(string $regex, string|int|null $level = null): bool
    {
        return $this->hasRecordThatPasses(static function ($rec) use ($regex) {
            return \preg_match($regex, (string) $rec['message']) > 0;
        }, $level);
    }

    /**
     * @param callable(array<string, mixed>, int): bool $predicate
     */
    public function hasRecordThatPasses(callable $predicate, string|int|null $level = null): bool
    {
        if (! $this->hasRecords($level)) {
            return false;
        }

        foreach ($level === null ? $this->records : $this->recordsByLevel[$level] as $i => $rec) {
            if (\call_user_func($predicate, $rec, $i)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $args
     */
    public function __call(string $method, array $args): bool
    {
        if (\preg_match('/(.*)(Debug|Info|Notice|Warning|Error|Critical|Alert|Emergency)(.*)/', $method, $matches) > 0) {
            $genericMethod = $matches[1] . ($matches[3] !== 'Records' ? 'Record' : '') . $matches[3];
            $callable      = [$this, $genericMethod];
            $level         = \strtolower($matches[2]);
            if (\is_callable($callable)) {
                $args[] = $level;

                return \call_user_func_array($callable, $args);
            }
        }

        throw new \BadMethodCallException('Call to undefined method ' . self::class . '::' . $method . '()');
    }

    public function reset(): void
    {
        $this->records        = [];
        $this->recordsByLevel = [];
    }
}
