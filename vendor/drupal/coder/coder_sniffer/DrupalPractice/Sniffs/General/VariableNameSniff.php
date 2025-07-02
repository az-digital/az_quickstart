<?php
/**
 * \DrupalPractice\Sniffs\General\VariableNameSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\General;

use PHP_CodeSniffer\Files\File;
use Drupal\Sniffs\Semantics\FunctionCall;
use DrupalPractice\Project;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks the usage of variable_get() in forms and the variable name.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class VariableNameSniff extends FunctionCall
{


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array<string>
     */
    public function registerFunctionNames()
    {
        return ['variable_get'];

    }//end registerFunctionNames()


    /**
     * Processes this function call.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the function call in
     *                                                  the stack.
     * @param int                         $openBracket  The position of the opening
     *                                                  parenthesis in the stack.
     * @param int                         $closeBracket The position of the closing
     *                                                  parenthesis in the stack.
     *
     * @return void
     */
    public function processFunctionCall(
        File $phpcsFile,
        $stackPtr,
        $openBracket,
        $closeBracket
    ) {
        $tokens = $phpcsFile->getTokens();

        // We assume that the sequence '#default_value' => variable_get(...)
        // indicates a variable that the module owns.
        $arrow = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($arrow === false || $tokens[$arrow]['code'] !== T_DOUBLE_ARROW) {
            return;
        }

        $arrayKey = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($arrow - 1), null, true);
        if ($arrayKey === false
            || $tokens[$arrayKey]['code'] !== T_CONSTANT_ENCAPSED_STRING
            || substr($tokens[$arrayKey]['content'], 1, -1) !== '#default_value'
        ) {
            return;
        }

        $argument = $this->getArgument(1);

        // Variable name is not a literal string, so we return early.
        if ($argument === false || $tokens[$argument['start']]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
            return;
        }

        $moduleName = Project::getName($phpcsFile);
        if ($moduleName === false) {
            return;
        }

        $variableName = substr($tokens[$argument['start']]['content'], 1, -1);
        if (strpos($variableName, $moduleName) !== 0) {
            $warning = 'All variables defined by your module must be prefixed with your module\'s name to avoid name collisions with others. Expected start with "%s" but found "%s"';
            $data    = [
                $moduleName,
                $variableName,
            ];
            $phpcsFile->addWarning($warning, $argument['start'], 'VariableName', $data);
        }

    }//end processFunctionCall()


}//end class
