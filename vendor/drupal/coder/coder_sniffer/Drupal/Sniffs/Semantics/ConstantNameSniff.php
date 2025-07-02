<?php
/**
 * \Drupal\Sniffs\Semantics\ConstantNameSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Semantics;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that constants introduced with define() in module or install files start
 * with the module's name.
 *
 * Largely copied from
 * \PHP_CodeSniffer\Standards\Generic\Sniffs\NamingConventions\UpperCaseConstantNameSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ConstantNameSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            T_STRING,
            T_CONST,
        ];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void|int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $nameParts     = explode('.', basename($phpcsFile->getFilename()));
        $fileExtension = end($nameParts);
        // Only check in *.module files.
        if ($fileExtension !== 'module' && $fileExtension !== 'install') {
            return ($phpcsFile->numTokens + 1);
        }

        $tokens = $phpcsFile->getTokens();
        // Only check in the outer scope, not within classes.
        if (empty($tokens[$stackPtr]['conditions']) === false) {
            return;
        }

        $moduleName    = reset($nameParts);
        $expectedStart = strtoupper($moduleName);

        if ($tokens[$stackPtr]['code'] === T_CONST) {
            // This is a class constant.
            $constant = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
            if ($constant === false) {
                return;
            }

            $constName = $tokens[$constant]['content'];

            if (strpos($constName, $expectedStart) !== 0) {
                $warning = 'All constants defined by a module must be prefixed with the module\'s name, expected "%s" but found "%s"';
                $data    = [
                    $expectedStart."_$constName",
                    $constName,
                ];
                $phpcsFile->addWarning($warning, $stackPtr, 'ConstConstantStart', $data);
                return;
            }//end if
        }

        // Only interested in define statements now.
        if (strtolower($tokens[$stackPtr]['content']) !== 'define') {
            return;
        }

        // Make sure this is not a method call.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$prev]['code'] === T_OBJECT_OPERATOR
            || $tokens[$prev]['code'] === T_DOUBLE_COLON
            || $tokens[$prev]['code'] === T_NULLSAFE_OBJECT_OPERATOR
        ) {
            return;
        }

        // If the next non-whitespace token after this token
        // is not an opening parenthesis then it is not a function call.
        $openBracket = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($openBracket === false) {
            return;
        }

        // The next non-whitespace token must be the constant name.
        $constPtr = $phpcsFile->findNext(T_WHITESPACE, ($openBracket + 1), null, true);
        if ($tokens[$constPtr]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
            return;
        }

        // Get the constant name and remove single and double quotes.
        $constName = str_replace(["'", '"'], ['', ''], $tokens[$constPtr]['content']);

        if (strpos($constName, $expectedStart) !== 0) {
            $warning = 'All constants defined by a module must be prefixed with the module\'s name, expected "%s" but found "%s"';
            $data    = [
                $expectedStart."_$constName",
                $constName,
            ];
            $phpcsFile->addWarning($warning, $stackPtr, 'ConstantStart', $data);
        }//end if

    }//end process()


}//end class
