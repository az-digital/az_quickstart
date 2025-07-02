<?php
/**
 * \Drupal\Sniffs\NamingConventions\ValidClassNameSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * \Drupal\Sniffs\NamingConventions\ValidClassNameSniff.
 *
 * Ensures class, enum, interface and trait names start with a capital letter
 * and do not use _ separators.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ValidClassNameSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            T_CLASS,
            T_ENUM,
            T_INTERFACE,
            T_TRAIT,
        ];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being processed.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $className = $phpcsFile->findNext(T_STRING, $stackPtr);
        $name      = trim($tokens[$className]['content']);
        $errorData = [ucfirst($tokens[$stackPtr]['content'])];

        // Make sure the first letter is a capital.
        if (preg_match('|^[A-Z]|', $name) === 0) {
            $error = '%s name must use UpperCamel naming and begin with a capital letter';
            $phpcsFile->addError($error, $stackPtr, 'StartWithCapital', $errorData);
        }

        // Search for underscores.
        if (strpos($name, '_') !== false) {
            $error = '%s name must use UpperCamel naming without underscores';
            $phpcsFile->addError($error, $stackPtr, 'NoUnderscores', $errorData);
        }

        // Ensure the name is not all uppercase.
        // @todo We could make this more strict to check if there are more than
        // 2 upper case characters in a row anywhere, but not decided yet.
        // See https://www.drupal.org/project/coder/issues/3497433
        if (preg_match('|^[A-Z]{3}[^a-z]*$|', $name) === 1) {
            $error = '%s name must use UpperCamel naming and not contain multiple upper case letters in a row';
            $phpcsFile->addError($error, $stackPtr, 'NoUpperAcronyms', $errorData);
        }

    }//end process()


}//end class
