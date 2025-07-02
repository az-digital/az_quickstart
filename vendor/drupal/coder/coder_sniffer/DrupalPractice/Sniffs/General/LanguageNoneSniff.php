<?php
/**
 * \DrupalPractice\Sniffs\General\LanguageNoneSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\General;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that ['und'] is not used, should be LANGUAGE_NONE.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class LanguageNoneSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            T_OPEN_SQUARE_BRACKET,
            T_OPEN_SHORT_ARRAY,
        ];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the function
     *                                               name in the stack.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $sequence = $phpcsFile->getTokensAsString($stackPtr, 3);
        if ($sequence === "['und']" || $sequence === '["und"]') {
            $warning = "Are you accessing field values here? Then you should use LANGUAGE_NONE instead of 'und'";
            $phpcsFile->addWarning($warning, ($stackPtr + 1), 'Und');
        }

    }//end process()


}//end class
