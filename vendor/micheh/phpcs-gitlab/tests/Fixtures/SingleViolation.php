<?php

/**
 * @copyright Copyright (c) 2020, Michel Hunziker <info@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

declare(strict_types=1);

namespace MichehTest\PhpCodeSniffer\Fixtures;

class SingleViolation implements ReportFixture
{
    public function getReportData(): array
    {
        return [
            'filename' => 'src/Report/Gitlab.php',
            'errors' => 1,
            'warnings' => 0,
            'fixable' => 1,
            'messages' => [
                18 => [
                    25 => [
                        0 => [
                            'message' => 'Header blocks must not contain blank lines',
                            'source' => 'PSR12.Files.FileHeader.SpacingInsideBlock',
                            'severity' => 5,
                            'fixable' => true,
                            'type' => 'ERROR',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getExpectedOutput(): string
    {
        return '{"type":"issue","categories":["Style"],"check_name":"PSR12.Files.FileHeader.SpacingInsideBlock","fingerprint":"21f4cd5b7d88d9eb67402be5d56caa8b","severity":"major","description":"Header blocks must not contain blank lines","location":{"path":"src/Report/Gitlab.php","lines":{"begin":18,"end":18}}},';
    }
}
