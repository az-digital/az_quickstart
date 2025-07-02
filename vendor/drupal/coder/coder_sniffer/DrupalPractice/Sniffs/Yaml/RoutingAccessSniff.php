<?php

/**
 * \DrupalPractice\Sniffs\Yaml\RoutingAccessSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\Yaml;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that there are no undocumented open access callbacks in *.routing.yml files.
 *
 * Also adds a warning if the permission "access administration pages" is used.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class RoutingAccessSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_INLINE_HTML];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being processed.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void|int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -12));
        if ($fileExtension !== '.routing.yml') {
            return ($phpcsFile->numTokens + 1);
        }

        if (preg_match('/^[\s]+_access: \'TRUE\'/', $tokens[$stackPtr]['content']) === 1
            && isset($tokens[($stackPtr - 1)]) === true
            && preg_match('/^[\s]*#/', $tokens[($stackPtr - 1)]['content']) === 0
        ) {
            $warning = 'Open page callback found, please add a comment before the line why there is no access restriction';
            $phpcsFile->addWarning($warning, $stackPtr, 'OpenCallback');
        }

        if (preg_match('/^[\s]+_permission: \'access administration pages\'/', $tokens[$stackPtr]['content']) === 1) {
            $warning = 'The administration page callback should probably use "administer site configuration" - which implies the user can change something - rather than "access administration pages" which is about viewing but not changing configurations.';
            $phpcsFile->addWarning($warning, $stackPtr, 'PermissionFound');
        }

    }//end process()


}//end class
