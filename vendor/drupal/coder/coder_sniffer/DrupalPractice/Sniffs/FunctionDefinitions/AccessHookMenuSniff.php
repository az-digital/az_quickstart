<?php
/**
 * \DrupalPractice\Sniffs\FunctionDefinitions\AccessHookMenuSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\FunctionDefinitions;

use PHP_CodeSniffer\Files\File;
use Drupal\Sniffs\Semantics\FunctionDefinition;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that there are no undocumented open access callbacks in hook_menu().
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class AccessHookMenuSniff extends FunctionDefinition
{


    /**
     * Process this function definition.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile   The file being scanned.
     * @param int                         $stackPtr    The position of the function name
     *                                                 in the stack.
     * @param int                         $functionPtr The position of the function keyword
     *                                                 in the stack.
     *
     * @return void
     */
    public function processFunction(File $phpcsFile, $stackPtr, $functionPtr)
    {
        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -6));
        // Only check in *.module files.
        if ($fileExtension !== 'module') {
            return;
        }

        $fileName = substr(basename($phpcsFile->getFilename()), 0, -7);
        $tokens   = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== ($fileName.'_menu')) {
            return;
        }

        // Search for 'access callback' => TRUE in the function body.
        $string = $phpcsFile->findNext(
            T_CONSTANT_ENCAPSED_STRING,
            $tokens[$functionPtr]['scope_opener'],
            $tokens[$functionPtr]['scope_closer']
        );
        while ($string !== false) {
            if (substr($tokens[$string]['content'], 1, -1) === 'access callback') {
                $arrayOperator = $phpcsFile->findNext(
                    Tokens::$emptyTokens,
                    ($string + 1),
                    null,
                    true
                );
                if ($arrayOperator !== false
                    && $tokens[$arrayOperator]['code'] === T_DOUBLE_ARROW
                ) {
                    $callback = $phpcsFile->findNext(
                        Tokens::$emptyTokens,
                        ($arrayOperator + 1),
                        null,
                        true
                    );
                    if ($callback !== false && $tokens[$callback]['code'] === T_TRUE) {
                        // Check if there is a comment before the line that might
                        // explain stuff.
                        $commentBefore = $phpcsFile->findPrevious(
                            T_WHITESPACE,
                            ($string - 1),
                            $tokens[$functionPtr]['scope_opener'],
                            true
                        );
                        if ($commentBefore !== false && in_array($tokens[$commentBefore]['code'], Tokens::$commentTokens) === false) {
                            $warning = 'Open page callback found, please add a comment before the line why there is no access restriction';
                            $phpcsFile->addWarning($warning, $callback, 'OpenCallback');
                        }
                    }
                }//end if
            }//end if

            $string = $phpcsFile->findNext(
                T_CONSTANT_ENCAPSED_STRING,
                ($string + 1),
                $tokens[$functionPtr]['scope_closer']
            );
        }//end while

    }//end processFunction()


}//end class
