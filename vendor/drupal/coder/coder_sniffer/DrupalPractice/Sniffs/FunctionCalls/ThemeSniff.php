<?php
/**
 * ThemeSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\FunctionCalls;

use PHP_CodeSniffer\Files\File;
use Drupal\Sniffs\Semantics\FunctionCall;

/**
 * \DrupalPractice\Sniffs\FunctionCalls\Checks that theme functions are not directly called.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ThemeSniff extends FunctionCall
{

    /**
     * List of functions starting with "theme_" that don't generate theme output.
     *
     * @var array<string>
     */
    protected $reservedFunctions = [
        'theme_get_registry',
        'theme_get_setting',
        'theme_render_template',
        'theme_enable',
        'theme_disable',
        'theme_get_suggestions',
    ];


    /**
     * Processes this function call.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the function call in
     *                                               the stack.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens       = $phpcsFile->getTokens();
        $functionName = $tokens[$stackPtr]['content'];
        if (strpos($functionName, 'theme_') !== 0
            || in_array($functionName, $this->reservedFunctions) === true
            || $this->isFunctionCall($phpcsFile, $stackPtr) === false
        ) {
            return;
        }

        $themeName = substr($functionName, 6);
        $warning   = "Do not call theme functions directly, use theme('%s', ...) instead";
        $phpcsFile->addWarning($warning, $stackPtr, 'ThemeFunctionDirect', [$themeName]);

    }//end process()


}//end class
