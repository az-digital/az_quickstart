<?php
/**
 * \Drupal\Sniffs\Functions\MultiLineFunctionDeclarationSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Functions;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Functions\OpeningFunctionBraceKernighanRitchieSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Functions\FunctionDeclarationSniff as PearFunctionDeclarationSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff as SquizFunctionDeclarationSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Multi-line function declarations need to have a trailing comma on the last
 * parameter. Modified from Squiz, whenever there is a function declaration
 * closing parenthesis on a new line we treat it as multi-line.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class MultiLineFunctionDeclarationSniff extends SquizFunctionDeclarationSniff
{


    /**
     * The number of spaces code should be indented.
     *
     * @var integer
     */
    public $indent = 2;


    /**
     * Processes single-line declarations.
     *
     * Just uses the Generic Kernighan Ritchie sniff.
     *
     * @param \PHP_CodeSniffer\Files\File      $phpcsFile The file being scanned.
     * @param int                              $stackPtr  The position of the current token
     *                                                    in the stack passed in $tokens.
     * @param array<int, array<string, mixed>> $tokens    The stack of tokens that make up
     *                                                    the file.
     *
     * @return void
     */
    public function processSingleLineDeclaration($phpcsFile, $stackPtr, $tokens)
    {
        $sniff = new OpeningFunctionBraceKernighanRitchieSniff();
        $sniff->checkClosures = true;
        $sniff->process($phpcsFile, $stackPtr);

    }//end processSingleLineDeclaration()


    /**
     * Determine if this is a multi-line function declaration.
     *
     * @param \PHP_CodeSniffer\Files\File      $phpcsFile   The file being scanned.
     * @param int                              $stackPtr    The position of the current token
     *                                                      in the stack passed in $tokens.
     * @param int                              $openBracket The position of the opening bracket
     *                                                      in the stack passed in $tokens.
     * @param array<int, array<string, mixed>> $tokens      The stack of tokens that make up
     *                                                      the file.
     *
     * @return bool
     */
    public function isMultiLineDeclaration($phpcsFile, $stackPtr, $openBracket, $tokens)
    {
        $function = $tokens[$stackPtr];
        if ($tokens[$function['parenthesis_opener']]['line'] === $tokens[$function['parenthesis_closer']]['line']) {
            return false;
        }

        return true;

    }//end isMultiLineDeclaration()


    /**
     * Processes multi-line declarations.
     *
     * @param \PHP_CodeSniffer\Files\File      $phpcsFile The file being scanned.
     * @param int                              $stackPtr  The position of the current token
     *                                                    in the stack passed in $tokens.
     * @param array<int, array<string, mixed>> $tokens    The stack of tokens that make up
     *                                                    the file.
     *
     * @return void
     */
    public function processMultiLineDeclaration($phpcsFile, $stackPtr, $tokens)
    {
        // We do everything the grandparent sniff does, and a bit more.
        PearFunctionDeclarationSniff::processMultiLineDeclaration($phpcsFile, $stackPtr, $tokens);

        $openBracket = $tokens[$stackPtr]['parenthesis_opener'];
        $this->processBracket($phpcsFile, $openBracket, $tokens, 'function');

        // Trailing commas on the last function parameter are only possible in
        // PHP 8.0+.
        if (version_compare(PHP_VERSION, '8.0.0') < 0) {
            return;
        }

        $function = $tokens[$stackPtr];

        $lastTrailingComma = $phpcsFile->findPrevious(
            Tokens::$emptyTokens,
            ($function['parenthesis_closer'] - 1),
            $function['parenthesis_opener'],
            true
        );
        if ($tokens[$lastTrailingComma]['code'] !== T_COMMA) {
            $error = 'Multi-line function declarations must have a trailing comma after the last parameter';
            $fix   = $phpcsFile->addFixableError($error, $lastTrailingComma, 'MissingTrailingComma');
            if ($fix === true) {
                $phpcsFile->fixer->addContent($lastTrailingComma, ',');
            }
        }

    }//end processMultiLineDeclaration()


}//end class
