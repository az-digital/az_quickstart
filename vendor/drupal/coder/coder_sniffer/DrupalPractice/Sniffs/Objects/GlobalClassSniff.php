<?php
/**
 * \DrupalPractice\Sniffs\Objects\GlobalClassSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\Objects;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use DrupalPractice\Project;

/**
 * Checks that Node::load() calls and friends are not used in forms, controllers or
 * services.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class GlobalClassSniff implements Sniff
{

    /**
     * Core class names that should not be called statically, mostly entity
     * classes.
     *
     * @var string[]
     */
    protected $coreClasses = [
        'Drupal\Core\Datetime\Entity\DateFormat',
        'Drupal\Core\Entity\Entity\EntityFormDisplay',
        'Drupal\Core\Entity\Entity\EntityFormMode',
        'Drupal\Core\Entity\Entity\EntityViewDisplay',
        'Drupal\Core\Entity\Entity\EntityViewMode',
        'Drupal\Core\Field\Entity\BaseFieldOverride',
        'Drupal\aggregator\Entity\Feed',
        'Drupal\aggregator\Entity\Item',
        'Drupal\block\Entity\Block',
        'Drupal\block_content\Entity\BlockContent',
        'Drupal\block_content\Entity\BlockContentType',
        'Drupal\comment\Entity\Comment',
        'Drupal\comment\Entity\CommentType',
        'Drupal\contact\Entity\ContactForm',
        'Drupal\contact\Entity\Message',
        'Drupal\content_moderation\Entity\ContentModerationState',
        'Drupal\editor\Entity\Editor',
        'Drupal\field\Entity\FieldConfig',
        'Drupal\field\Entity\FieldStorageConfig',
        'Drupal\file\Entity\File',
        'Drupal\filter\Entity\FilterFormat',
        'Drupal\image\Entity\ImageStyle',
        'Drupal\language\Entity\ConfigurableLanguage',
        'Drupal\language\Entity\ContentLanguageSettings',
        'Drupal\media\Entity\Media',
        'Drupal\media\Entity\MediaType',
        'Drupal\menu_link_content\Entity\MenuLinkContent',
        'Drupal\node\Entity\Node',
        'Drupal\node\Entity\NodeType',
        'Drupal\path_alias\Entity\PathAlias',
        'Drupal\rdf\Entity\RdfMapping',
        'Drupal\responsive_image\Entity\ResponsiveImageStyle',
        'Drupal\rest\Entity\RestResourceConfig',
        'Drupal\search\Entity\SearchPage',
        'Drupal\shortcut\Entity\Shortcut',
        'Drupal\shortcut\Entity\ShortcutSet',
        'Drupal\system\Entity\Action',
        'Drupal\system\Entity\Menu',
        'Drupal\taxonomy\Entity\Term',
        'Drupal\taxonomy\Entity\Vocabulary',
        'Drupal\tour\Entity\Tour',
        'Drupal\user\Entity\Role',
        'Drupal\user\Entity\User',
        'Drupal\views\Entity\View',
        'Drupal\workflows\Entity\Workflow',
        'Drupal\workspaces\Entity\Workspace',
    ];

    /**
     * Class names that should not be called statically.
     *
     * @var string[]
     */
    public $classes = [];


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_STRING];

    }//end register()


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

        // We are only interested in static class method calls, not in the global
        // scope.
        if ($tokens[($stackPtr + 1)]['code'] !== T_DOUBLE_COLON
            || isset($tokens[($stackPtr + 2)]) === false
            || $tokens[($stackPtr + 2)]['code'] !== T_STRING
            || in_array($tokens[($stackPtr + 2)]['content'], ['load', 'loadMultiple']) === false
            || isset($tokens[($stackPtr + 3)]) === false
            || $tokens[($stackPtr + 3)]['code'] !== T_OPEN_PARENTHESIS
            || empty($tokens[$stackPtr]['conditions']) === true
        ) {
            return;
        }

        // Check that this statement is not in a static function.
        foreach ($tokens[$stackPtr]['conditions'] as $conditionPtr => $conditionCode) {
            if ($conditionCode === T_FUNCTION && $phpcsFile->getMethodProperties($conditionPtr)['is_static'] === true) {
                return;
            }
        }

        $fullName = $this->getFullyQualifiedName($phpcsFile, $tokens[$stackPtr]['content']);
        if (in_array($fullName, $this->coreClasses) === false && in_array($fullName, $this->classes) === false) {
            return;
        }

        // Check if the class extends another class and get the name of the class
        // that is extended.
        $classPtr    = key($tokens[$stackPtr]['conditions']);
        $extendsName = $phpcsFile->findExtendedClassName($classPtr);

        // Check if the class implements a container injection interface.
        $containerInterfaces       = [
            'ContainerInjectionInterface',
            'ContainerFactoryPluginInterface',
            'ContainerDeriverInterface',
        ];
        $implementedInterfaceNames = $phpcsFile->findImplementedInterfaceNames($classPtr);
        $canAccessContainer        = !empty($implementedInterfaceNames) && !empty(array_intersect($containerInterfaces, $implementedInterfaceNames));

        if (($extendsName === false
            || in_array($extendsName, GlobalDrupalSniff::$baseClasses) === false)
            && Project::isServiceClass($phpcsFile, $classPtr) === false
            && $canAccessContainer === false
        ) {
            return ($phpcsFile->numTokens + 1);
        }

        $warning = '%s::%s calls should be avoided in classes, use dependency injection instead';
        $data    = [
            $tokens[$stackPtr]['content'],
            $tokens[($stackPtr + 2)]['content'],
        ];
        $phpcsFile->addWarning($warning, $stackPtr, 'GlobalClass', $data);

    }//end process()


    /**
     * Retrieve the fully qualified name of the given classname.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param string                      $className The classname for which to retrieve the FQN.
     *
     * @return string
     */
    protected function getFullyQualifiedName(File $phpcsFile, $className)
    {
        $useStatement = $phpcsFile->findNext(T_USE, 0);
        while ($useStatement !== false) {
            $endPtr      = $phpcsFile->findEndOfStatement($useStatement);
            $useEnd      = ($phpcsFile->findNext([T_STRING, T_NS_SEPARATOR, T_WHITESPACE], ($useStatement + 1), null, true) - 1);
            $useFullName = trim($phpcsFile->getTokensAsString(($useStatement + 1), ($useEnd - $useStatement)), '\\ ');

            // Check if use statement contains an alias.
            $asPtr = $phpcsFile->findNext(T_AS, ($useEnd + 1), $endPtr);
            if ($asPtr !== false) {
                $aliasName = trim($phpcsFile->getTokensAsString(($asPtr + 1), ($endPtr - 1 - $asPtr)));
                if ($aliasName === $className) {
                    return $useFullName;
                }
            }

            $parts        = explode('\\', $useFullName);
            $useClassName = end($parts);

            // Check if the resulting classname is the classname we're looking
            // for.
            if ($useClassName === $className) {
                return $useFullName;
            }

            // Check if we're currently in a multi-use statement.
            $tokens = $phpcsFile->getTokens();
            if ($tokens[$endPtr]['code'] === T_COMMA) {
                $useStatement = $endPtr;
                continue;
            }

            $useStatement = $phpcsFile->findNext(T_USE, ($useStatement + 1));
        }//end while

        return $className;

    }//end getFullyQualifiedName()


}//end class
