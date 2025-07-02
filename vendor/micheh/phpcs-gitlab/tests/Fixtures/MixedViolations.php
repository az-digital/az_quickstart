<?php

/**
 * @copyright Copyright (c) 2020, Michel Hunziker <info@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

declare(strict_types=1);

namespace MichehTest\PhpCodeSniffer\Fixtures;

class MixedViolations implements ReportFixture
{
    public function getReportData(): array
    {
        return [
            'filename' => 'tests/Report/GitlabTest.php',
            'errors' => 1,
            'warnings' => 1,
            'fixable' => 1,
            'messages' => [
                23 => [
                    32 => [
                        0 => [
                            'message' => 'There must be a single space between the colon and type in a return type declaration',
                            'source' => 'PSR12.Functions.ReturnTypeDeclaration.SpaceBeforeReturnType',
                            'severity' => 5,
                            'fixable' => true,
                            'type' => 'ERROR',
                        ],
                    ],
                ],
                34 => [
                    9 => [
                        0 => [
                            'message' => 'Line exceeds 120 characters; contains 124 characters',
                            'source' => 'Generic.Files.LineLength.TooLong',
                            'severity' => 5,
                            'fixable' => false,
                            'type' => 'WARNING',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getExpectedOutput(): string
    {
        return '{"type":"issue","categories":["Style"],"check_name":"PSR12.Functions.ReturnTypeDeclaration.SpaceBeforeReturnType","fingerprint":"766bf1f7eff186fd667447bfb0291078","severity":"major","description":"There must be a single space between the colon and type in a return type declaration","location":{"path":"tests/Report/GitlabTest.php","lines":{"begin":23,"end":23}}},{"type":"issue","categories":["Style"],"check_name":"Generic.Files.LineLength.TooLong","fingerprint":"517eda4b85a33cd09fc933259f873191","severity":"minor","description":"Line exceeds 120 characters; contains 124 characters","location":{"path":"tests/Report/GitlabTest.php","lines":{"begin":34,"end":34}}},';
    }
}
