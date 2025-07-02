# psr-testlogger

[![Latest Version](https://img.shields.io/packagist/v/colinodell/psr-testlogger.svg?style=flat-square)](https://packagist.org/packages/colinodell/psr-testlogger)
[![Total Downloads](https://img.shields.io/packagist/dt/colinodell/psr-testlogger.svg?style=flat-square)](https://packagist.org/packages/colinodell/psr-testlogger)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/colinodell/psr-testlogger/Tests/main.svg?style=flat-square)](https://github.com/colinodell/psr-testlogger/actions?query=workflow%3ATests+branch%3Amain)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/colinodell/psr-testlogger.svg?style=flat-square)](https://scrutinizer-ci.com/g/colinodell/psr-testlogger/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/colinodell/psr-testlogger.svg?style=flat-square)](https://scrutinizer-ci.com/g/colinodell/psr-testlogger)
[![Psalm Type Coverage](https://shepherd.dev/github/colinodell/psr-testlogger/coverage.svg)](https://shepherd.dev/github/colinodell/psr-testlogger)
[![Sponsor development of this project](https://img.shields.io/badge/sponsor%20this%20package-%E2%9D%A4-ff69b4.svg?style=flat-square)](https://www.colinodell.com/sponsor)

PSR-3 compliant test logger based on psr/log v1's, but compatible with v2 and v3 too!

## 📦 Installation

This project requires PHP 8.0 or higher.  To install it via Composer simply run:

``` bash
$ composer require --dev colinodell/psr-testlogger
```

## Usage

This package provides a PSR-3 compliant logger useful for testing.  Simply log messages to it like usual, and use one of the many available methods to perform assertions on the logged messages.

```
hasRecords(string|int $level): bool

hasEmergencyRecords(): bool
hasAlertRecords(): bool
hasCriticalRecords(): bool
hasErrorRecords(): bool
hasWarningRecords(): bool
hasNoticeRecords(): bool
hasInfoRecords(): bool
hasDebugRecords(): bool

hasRecord(string|array $record, string|int $level): bool

hasEmergency(string|array $record): bool
hasAlert(string|array $record): bool
hasCritical(string|array $record): bool
hasError(string|array $record): bool
hasWarning(string|array $record): bool
hasNotice(string|array $record): bool
hasInfo(string|array $record): bool
hasDebug(string|array $record): bool

hasRecordThatContains(string $message, string|int|null $level = null): bool

hasEmergencyThatContains(string $message): bool
hasAlertThatContains(string $message): bool
hasCriticalThatContains(string $message): bool
hasErrorThatContains(string $message): bool
hasWarningThatContains(string $message): bool
hasNoticeThatContains(string $message): bool
hasInfoThatContains(string $message): bool
hasDebugThatContains(string $message): bool

hasRecordThatMatches(string $regex, string|int|null $level = null): bool

hasEmergencyThatMatches(string $regex): bool
hasAlertThatMatches(string $regex): bool
hasCriticalThatMatches(string $regex): bool
hasErrorThatMatches(string $regex): bool
hasWarningThatMatches(string $regex): bool
hasNoticeThatMatches(string $regex): bool
hasInfoThatMatches(string $regex): bool
hasDebugThatMatches(string $regex): bool

hasRecordThatPasses(callable $predicate, string|int|null $level = null): bool

hasEmergencyThatPasses(callable $predicate): bool
hasAlertThatPasses(callable $predicate): bool
hasCriticalThatPasses(callable $predicate): bool
hasErrorThatPasses(callable $predicate): bool
hasWarningThatPasses(callable $predicate): bool
hasNoticeThatPasses(callable $predicate): bool
hasInfoThatPasses(callable $predicate): bool
hasDebugThatPasses(callable $predicate): bool
```

In addition to the standard PSR-3 log levels, this test logger also supports custom `string` and `int` levels when using `log()`. Other types are not supported.

## Release Cycle

This library is considered stable. No new development is currently planned unless needed to fix issues or maintain compatibility with the psr/log library.

Issues and PRs for bug fixes are welcome and will be merged/released on an as-needed basis.

## Backward Compatibility

This library strictly follows SemVer using [Symfony's BC Promise](https://symfony.com/doc/current/contributing/code/bc.html) as a guide.

## Reporting Security Issues

Please report security issues directly to the library author instead of using the issue tracker. Contact info can be found in `composer.json`.
