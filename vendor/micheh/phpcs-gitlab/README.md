
Gitlab Report for PHP_CodeSniffer
---------------------------------
![Main workflow](https://github.com/micheh/phpcs-gitlab/workflows/Main%20workflow/badge.svg)
[![codecov](https://codecov.io/gh/micheh/phpcs-gitlab/branch/master/graph/badge.svg)](https://codecov.io/gh/micheh/phpcs-gitlab)


This library adds a custom report to [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) (phpcs) to generate a codequality artifact, which can be used by Gitlab CI/CD.
The custom report will be generated in the Code Climate format and allows Gitlab CI/CD to display the violations in the Code Quality report.

## Installation

Install this library using [Composer](https://getcomposer.org):

```shell script
composer require --dev micheh/phpcs-gitlab
```

Then adjust your `.gitlab-ci.yml` to run PHP_CodeSniffer with the custom reporter and to gather the codequality artifacts:

```yaml
phpcs:
  script: vendor/bin/phpcs --report=emacs --report-\\Micheh\\PhpCodeSniffer\\Report\\Gitlab=phpcs-quality-report.json
  artifacts:
    reports:
      codequality: phpcs-quality-report.json
```

The example above uses two reports, one to display in the build log (emacs) and one to generate the codequality artifact file in the Code Climate format.

> **Note:** Gitlab currently does not support multiple codequality artifacts. 
> You will not be able to display the violations of multiple tools (e.g. PHP Code Sniffer & PHPStan) in the Code Quality report.


## References

- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [Gitlab CI/CD Code Quality](https://docs.gitlab.com/ee/user/project/merge_requests/code_quality.html)
- [Code Climate Specification](https://github.com/codeclimate/platform/blob/master/spec/analyzers/SPEC.md#data-types)


## License

The files in this archive are licensed under the BSD-3-Clause license.
You can find a copy of this license in [LICENSE.md](LICENSE.md).
