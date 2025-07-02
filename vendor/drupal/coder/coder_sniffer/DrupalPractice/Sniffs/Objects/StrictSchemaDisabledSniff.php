<?php
/**
 * DrupalPractice_Sniffs_Objects_StrictSchemaDisabledSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\Objects;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;

/**
 * Checks that $strictConfigSchema is not set to FALSE in test classes.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class StrictSchemaDisabledSniff extends AbstractVariableSniff
{

    /**
     * The name of the variable in the test base class to disable config schema checking.
     */
    const STRICT_CONFIG_SCHEMA_NAME = '$strictConfigSchema';


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function processMemberVar(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (($tokens[$stackPtr]['content'] === static::STRICT_CONFIG_SCHEMA_NAME) && ($this->isTestClass($phpcsFile, $stackPtr) === true)) {
            $find = [
                T_FALSE,
                T_TRUE,
                T_NULL,
                T_SEMICOLON,
            ];
            $next = $phpcsFile->findNext($find, ($stackPtr + 1));
            // If this variable is being set, the only allowed value is TRUE.
            // Otherwise if FALSE or NULL, schema checking is disabled.
            if ($tokens[$next]['code'] !== T_TRUE) {
                $error = 'Do not disable strict config schema checking in tests. Instead ensure your module properly declares its schema for configurations.';
                $data  = [$tokens[$stackPtr]['content']];
                $phpcsFile->addError(
                    $error,
                    $stackPtr,
                    'StrictConfigSchema',
                    $data
                );
            }
        }//end if

    }//end processMemberVar()


    /**
     * Determine if this class is a test class.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return bool
     *   Returns TRUE if the current class is a test class.
     */
    protected function isTestClass(File $phpcsFile, $stackPtr)
    {
        // Only applies to test classes, which have Test in the name.
        $tokens   = $phpcsFile->getTokens();
        $classPtr = key($tokens[$stackPtr]['conditions']);
        $name     = $phpcsFile->findNext([T_STRING], $classPtr);
        return strpos($tokens[$name]['content'], 'Test') !== false;

    }//end isTestClass()


    /**
     * Called to process normal member vars.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where this
     *                                               token was found.
     * @param int                         $stackPtr  The position where the token was found.
     *
     * @return void|int Optionally returns a stack pointer. The sniff will not be
     *                  called again on the current file until the returned stack
     *                  pointer is reached. Return ($phpcsFile->numTokens + 1) to skip
     *                  the rest of the file.
     */
    protected function processVariable(File $phpcsFile, $stackPtr)
    {

    }//end processVariable()


    /**
     * Called to process variables found in double quoted strings or heredocs.
     *
     * Note that there may be more than one variable in the string, which will
     * result only in one call for the string or one call per line for heredocs.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where this
     *                                               token was found.
     * @param int                         $stackPtr  The position where the double quoted
     *                                               string was found.
     *
     * @return void|int Optionally returns a stack pointer. The sniff will not be
     *                  called again on the current file until the returned stack
     *                  pointer is reached. Return ($phpcsFile->numTokens + 1) to skip
     *                  the rest of the file.
     */
    protected function processVariableInString(File $phpcsFile, $stackPtr)
    {

    }//end processVariableInString()


}//end class
