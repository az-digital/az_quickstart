<?php

/**
 * \DrupalPractice\Sniffs\General\ExceptionTSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\General;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that exceptions aren't translated.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ExceptionTSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_THROW];

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

        $tokens = $phpcsFile->getTokens();
        $endPtr = $phpcsFile->findEndOfStatement($stackPtr);

        $newPtr = $phpcsFile->findNext(T_NEW, ($stackPtr + 1), $endPtr);
        if ($newPtr !== false) {
            $openPtr = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($newPtr + 1), $endPtr);
            if ($openPtr !== false) {
                for ($i = ($openPtr + 1); $i < $tokens[$openPtr]['parenthesis_closer']; $i++) {
                    if ($tokens[$i]['code'] === T_STRING && $tokens[$i]['content'] === 't') {
                        $warning = 'Exceptions should not be translated';
                        $phpcsFile->addWarning($warning, $stackPtr, 'ExceptionT');
                        return;
                    }
                }
            }
        }

    }//end process()


}//end class
