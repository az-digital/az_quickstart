<?php
/**
 * \DrupalPractice\Sniffs\Objects\GlobalFunctionSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\Objects;

use PHP_CodeSniffer\Files\File;
use DrupalPractice\Project;
use Drupal\Sniffs\Semantics\FunctionCall;

/**
 * Checks that global functions like t() are not used in forms or controllers.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class GlobalFunctionSniff extends FunctionCall
{

    /**
     * List of global functions that should not be called.
     *
     * @var string[]
     */
    protected $functions = [
        'drupal_get_destination'   => 'the "redirect.destination" service',
        'drupal_render'            => 'the "renderer" service',
        'entity_load'              => 'the "entity_type.manager" service',
        'file_load'                => 'the "entity_type.manager" service',
        'format_date'              => 'the "date.formatter" service',
        'node_load'                => 'the "entity_type.manager" service',
        'node_load_multiple'       => 'the "entity_type.manager" service',
        'node_type_load'           => 'the "entity_type.manager" service',
        't'                        => '$this->t()',
        'taxonomy_term_load'       => 'the "entity_type.manager" service',
        'taxonomy_vocabulary_load' => 'the "entity_type.manager" service',
        'user_load'                => 'the "entity_type.manager" service',
        'user_role_load'           => 'the "entity_type.manager" service',
    ];

    /**
     * List of global functions that are covered by traits.
     *
     * This is a subset of the global functions list. These functions can be
     * replaced by methods that are provided by the listed trait.
     *
     * @var string[]
     */
    protected $traitFunctions = ['t' => '\Drupal\Core\StringTranslation\StringTranslationTrait'];


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void|int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Only run this sniff on Drupal 8+.
        if (Project::getCoreVersion($phpcsFile) < 8) {
            // No need to check this file again, mark it as done.
            return ($phpcsFile->numTokens + 1);
        }

        // We just want to listen on function calls, nothing else.
        if ($this->isFunctionCall($phpcsFile, $stackPtr) === false
            // Ignore function calls in the global scope.
            || empty($tokens[$stackPtr]['conditions']) === true
            // Only our list of function names.
            || isset($this->functions[$tokens[$stackPtr]['content']]) === false
        ) {
            return;
        }

        // Check that this statement is not in a static function.
        foreach ($tokens[$stackPtr]['conditions'] as $conditionPtr => $conditionCode) {
            if ($conditionCode === T_FUNCTION && $phpcsFile->getMethodProperties($conditionPtr)['is_static'] === true) {
                return;
            }
        }

        // Check if the class extends another class and get the name of the class
        // that is extended.
        $classPtr = key($tokens[$stackPtr]['conditions']);
        if ($tokens[$classPtr]['code'] !== T_CLASS) {
            return;
        }

        if (isset($this->traitFunctions[$tokens[$stackPtr]['content']]) === false) {
            $extendsName = $phpcsFile->findExtendedClassName($classPtr);

            // Check if the class implements ContainerInjectionInterface.
            $implementedInterfaceNames = $phpcsFile->findImplementedInterfaceNames($classPtr);
            $canAccessContainer        = !empty($implementedInterfaceNames) && in_array('ContainerInjectionInterface', $implementedInterfaceNames);

            if (($extendsName === false
                || in_array($extendsName, GlobalDrupalSniff::$baseClasses) === false)
                && Project::isServiceClass($phpcsFile, $classPtr) === false
                && $canAccessContainer === false
            ) {
                return;
            }

            $warning = '%s() calls should be avoided in classes, use dependency injection and %s instead';
            $data    = [
                $tokens[$stackPtr]['content'],
                $this->functions[$tokens[$stackPtr]['content']],
            ];
        } else {
            $warning = '%s() calls should be avoided in classes, use %s and %s instead';
            $data    = [
                $tokens[$stackPtr]['content'],
                $this->traitFunctions[$tokens[$stackPtr]['content']],
                $this->functions[$tokens[$stackPtr]['content']],
            ];
        }//end if

        $phpcsFile->addWarning($warning, $stackPtr, 'GlobalFunction', $data);

    }//end process()


}//end class
