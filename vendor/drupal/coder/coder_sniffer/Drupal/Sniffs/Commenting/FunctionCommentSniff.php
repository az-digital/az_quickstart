<?php
/**
 * Parses and verifies the doc comments for functions.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Parses and verifies the doc comments for functions. Largely copied from
 * PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class FunctionCommentSniff implements Sniff
{

    /**
     * A map of invalid data types to valid ones for param and return documentation.
     *
     * @var array<string, string>
     */
    public static $invalidTypes = [
        'Array'     => 'array',
        'array()'   => 'array',
        '[]'        => 'array',
        'boolean'   => 'bool',
        'Boolean'   => 'bool',
        'integer'   => 'int',
        'str'       => 'string',
        'number'    => 'int',
        'String'    => 'string',
        'type'      => 'mixed',
        'NULL'      => 'null',
        'FALSE'     => 'false',
        'TRUE'      => 'true',
        'Bool'      => 'bool',
        'Int'       => 'int',
        'Integer'   => 'int',
        // cspell:ignore TRUEFALSE
        'TRUEFALSE' => 'bool',
    ];


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_FUNCTION];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $ignore = (Tokens::$methodPrefixes + Tokens::$phpcsCommentTokens);
        $ignore[T_WHITESPACE] = T_WHITESPACE;
        $functionCodeStart    = $stackPtr;

        for ($commentEnd = ($stackPtr - 1); $commentEnd >= 0; $commentEnd--) {
            if (isset($ignore[$tokens[$commentEnd]['code']]) === true) {
                if (isset(Tokens::$phpcsCommentTokens[$tokens[$commentEnd]['code']]) === true) {
                    $functionCodeStart = $commentEnd;
                }

                continue;
            }

            if ($tokens[$commentEnd]['code'] === T_ATTRIBUTE_END
                && isset($tokens[$commentEnd]['attribute_opener']) === true
            ) {
                $commentEnd = $functionCodeStart = $tokens[$commentEnd]['attribute_opener'];
                continue;
            }

            // If there is a phpstan-ignore inline comment disregard it and continue searching backwards
            // to find the function comment.
            if ($this->tokenIsPhpstanComment($tokens[$commentEnd]) === true) {
                $functionCodeStart = $commentEnd;
                continue;
            }

            break;
        }//end for

        // Constructor methods are exempt from requiring a docblock.
        // @see https://www.drupal.org/project/coder/issues/3400560.
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === '__construct'
            && $tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            return;
        }

        $beforeCommentEnd = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($commentEnd - 1), null, true);
        if (($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT)
            || ($beforeCommentEnd !== false
            // If there is something more on the line than just the comment then the
            // comment does not belong to the function.
            && $tokens[$beforeCommentEnd]['line'] === $tokens[$commentEnd]['line'])
        ) {
            $fix = $phpcsFile->addFixableError('Missing function doc comment', $stackPtr, 'Missing');
            if ($fix === true) {
                $before = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), ($stackPtr + 1), true);
                $phpcsFile->fixer->addContentBefore($before, "/**\n *\n */\n");
            }

            return;
        }

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $fix = $phpcsFile->addFixableError('You must use "/**" style comments for a function comment', $stackPtr, 'WrongStyle');
            if ($fix === true) {
                // Convert the comment into a doc comment.
                $phpcsFile->fixer->beginChangeset();
                $comment = '';
                for ($i = $commentEnd; $tokens[$i]['code'] === T_COMMENT; $i--) {
                    $comment = ' *'.ltrim($tokens[$i]['content'], '/* ').$comment;
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->replaceToken($commentEnd, "/**\n".rtrim($comment, "*/\n")."\n */\n");
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'];
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            // This is a file comment, not a function comment.
            if ($tokens[$tag]['content'] === '@file') {
                $fix = $phpcsFile->addFixableError('Missing function doc comment', $stackPtr, 'Missing');
                if ($fix === true) {
                    $before = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), ($stackPtr + 1), true);
                    $phpcsFile->fixer->addContentBefore($before, "/**\n *\n */\n");
                }

                return;
            }

            if ($tokens[$tag]['content'] === '@see') {
                // Make sure the tag isn't empty.
                $string = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tag, $commentEnd);
                if ($string === false || $tokens[$string]['line'] !== $tokens[$tag]['line']) {
                    $error = 'Content missing for @see tag in function comment';
                    $phpcsFile->addError($error, $tag, 'EmptySees');
                }
            }
        }//end foreach

        if ($tokens[$commentEnd]['line'] !== ($tokens[$functionCodeStart]['line'] - 1)) {
            $error = 'There must be no blank lines after the function comment';
            $fix   = $phpcsFile->addFixableError($error, $commentEnd, 'SpacingAfter');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($commentEnd + 1), '');
            }
        }

        $this->processReturn($phpcsFile, $stackPtr, $commentStart);
        $this->processThrows($phpcsFile, $stackPtr, $commentStart);
        $this->processParams($phpcsFile, $stackPtr, $commentStart);
        $this->processSees($phpcsFile, $stackPtr, $commentStart);

    }//end process()


    /**
     * Determine if a token is a '@phpstan-' control comment.
     *
     * @param array<mixed> $token The token to be checked.
     *
     * @return bool True if the token contains a @phpstan comment.
     */
    public static function tokenIsPhpstanComment($token)
    {
        return ($token['code'] === T_COMMENT && strpos($token['content'], ' @phpstan-') !== false);

    }//end tokenIsPhpstanComment()


    /**
     * Process the return comment of this function comment.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token
     *                                                  in the stack passed in $tokens.
     * @param int                         $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processReturn(File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();
        $return = null;
        $end    = $stackPtr;

        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] === '@return') {
                if ($return !== null) {
                    $error = 'Only 1 @return tag is allowed in a function comment';
                    $phpcsFile->addError($error, $tag, 'DuplicateReturn');
                    return;
                }

                $return = $tag;
                // Any strings until the next tag belong to this comment.
                if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                    $skipTags = [
                        '@code',
                        '@endcode',
                    ];
                    $skipPos  = ($pos + 1);
                    while (isset($tokens[$commentStart]['comment_tags'][$skipPos]) === true
                        && in_array($tokens[$commentStart]['comment_tags'][$skipPos], $skipTags) === true
                    ) {
                        $skipPos++;
                    }

                    $end = $tokens[$commentStart]['comment_tags'][$skipPos];
                } else {
                    $end = $tokens[$commentStart]['comment_closer'];
                }
            }//end if
        }//end foreach

        if ($return !== null) {
            $returnType = trim($tokens[($return + 2)]['content']);
            if (empty($returnType) === true || $tokens[($return + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                $error = 'Return type missing for @return tag in function comment';
                $phpcsFile->addError($error, $return, 'MissingReturnType');
            } else if (strpos($returnType, ' ') === false) {
                // Check return type (can be multiple, separated by '|').
                $typeNames      = explode('|', $returnType);
                $suggestedNames = [];
                $hasNull        = false;
                // Do not check PHPStan types that contain any kind of brackets.
                // See https://phpstan.org/writing-php-code/phpdoc-types#general-arrays .
                $isPhpstanType = preg_match('/[<\[\{\(]/', $returnType) === 1;
                foreach ($typeNames as $i => $typeName) {
                    if (strtolower($typeName) === 'null') {
                        $hasNull = true;
                    }

                    $suggestedName = $this->suggestType($typeName);
                    if (in_array($suggestedName, $suggestedNames, true) === false
                        || $isPhpstanType === true
                    ) {
                        $suggestedNames[] = $suggestedName;
                    }
                }

                $suggestedType = implode('|', $suggestedNames);
                if ($returnType !== $suggestedType) {
                    $error = 'Expected "%s" but found "%s" for function return type';
                    $data  = [
                        $suggestedType,
                        $returnType,
                    ];
                    $fix   = $phpcsFile->addFixableError($error, $return, 'InvalidReturn', $data);
                    if ($fix === true) {
                        $replacement = $suggestedType;

                        $phpcsFile->fixer->replaceToken(($return + 2), $replacement);
                        unset($replacement);
                    }
                }

                // If the return type is void, make sure there is
                // no return statement in the function.
                if ($returnType === 'void') {
                    if (isset($tokens[$stackPtr]['scope_closer']) === true) {
                        $endToken = $tokens[$stackPtr]['scope_closer'];
                        for ($returnToken = $stackPtr; $returnToken < $endToken; $returnToken++) {
                            if ($tokens[$returnToken]['code'] === T_CLOSURE
                                || $tokens[$returnToken]['code'] === T_ANON_CLASS
                            ) {
                                $returnToken = $tokens[$returnToken]['scope_closer'];
                                continue;
                            }

                            if ($tokens[$returnToken]['code'] === T_RETURN
                                || $tokens[$returnToken]['code'] === T_YIELD
                                || $tokens[$returnToken]['code'] === T_YIELD_FROM
                            ) {
                                break;
                            }
                        }

                        if ($returnToken !== $endToken) {
                            // If the function is not returning anything, just
                            // exiting, then there is no problem.
                            $semicolon = $phpcsFile->findNext(T_WHITESPACE, ($returnToken + 1), null, true);
                            if ($tokens[$semicolon]['code'] !== T_SEMICOLON) {
                                $error = 'Function return type is void, but function contains return statement';
                                $phpcsFile->addError($error, $return, 'InvalidReturnVoid');
                            }
                        }
                    }//end if
                } else if ($returnType !== 'mixed'
                    && $returnType !== 'never'
                    && in_array('void', $typeNames, true) === false
                ) {
                    // If return type is not void, never, or mixed, there needs to be a
                    // return statement somewhere in the function that returns something.
                    if (isset($tokens[$stackPtr]['scope_closer']) === true) {
                        $endToken = $tokens[$stackPtr]['scope_closer'];
                        for ($returnToken = $stackPtr; $returnToken < $endToken; $returnToken++) {
                            if ($tokens[$returnToken]['code'] === T_CLOSURE
                                || $tokens[$returnToken]['code'] === T_ANON_CLASS
                            ) {
                                $returnToken = $tokens[$returnToken]['scope_closer'];
                                continue;
                            }

                            if ($tokens[$returnToken]['code'] === T_RETURN
                                || $tokens[$returnToken]['code'] === T_YIELD
                                || $tokens[$returnToken]['code'] === T_YIELD_FROM
                            ) {
                                break;
                            }
                        }

                        if ($returnToken === $endToken) {
                            $error = 'Function return type is not void, but function has no return statement';
                            $phpcsFile->addError($error, $return, 'InvalidNoReturn');
                        } else {
                            $semicolon = $phpcsFile->findNext(T_WHITESPACE, ($returnToken + 1), null, true);
                            // Void return is allowed if the @return type has null in it.
                            if ($tokens[$semicolon]['code'] === T_SEMICOLON && $hasNull === false) {
                                $error = 'Function return type is not void, but function is returning void here';
                                $phpcsFile->addError($error, $returnToken, 'InvalidReturnNotVoid');
                            }
                        }
                    }//end if
                }//end if
            }//end if

            $comment = '';
            for ($i = ($return + 3); $i < $end; $i++) {
                if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                    $indent = 0;
                    if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                        $indent = strlen($tokens[($i - 1)]['content']);
                    }

                    $comment       .= ' '.$tokens[$i]['content'];
                    $commentLines[] = [
                        'comment' => $tokens[$i]['content'],
                        'token'   => $i,
                        'indent'  => $indent,
                    ];
                    if ($indent < 3) {
                        $error = 'Return comment indentation must be 3 spaces, found %s spaces';
                        $fix   = $phpcsFile->addFixableError($error, $i, 'ReturnCommentIndentation', [$indent]);
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($i - 1), '   ');
                        }
                    }
                }
            }//end for

            // The first line of the comment must be indented no more than 3
            // spaces, the following lines can be more so we only check the first
            // line.
            if (empty($commentLines[0]['indent']) === false && $commentLines[0]['indent'] > 3) {
                $error = 'Return comment indentation must be 3 spaces, found %s spaces';
                $fix   = $phpcsFile->addFixableError($error, ($commentLines[0]['token'] - 1), 'ReturnCommentIndentation', [$commentLines[0]['indent']]);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($commentLines[0]['token'] - 1), '   ');
                }
            }

            if ($comment === '' && $returnType !== '$this' && $returnType !== 'static') {
                if (strpos($returnType, ' ') !== false) {
                    $error = 'Description for the @return value must be on the next line';
                } else {
                    $error = 'Description for the @return value is missing';
                }

                $phpcsFile->addError($error, $return, 'MissingReturnComment');
            } else if (strpos($returnType, ' ') !== false) {
                if (preg_match('/^([^\s]+)[\s]+(\$[^\s]+)[\s]*$/', $returnType, $matches) === 1) {
                    $error = 'Return type must not contain variable name "%s"';
                    $data  = [$matches[2]];
                    $fix   = $phpcsFile->addFixableError($error, ($return + 2), 'ReturnVarName', $data);
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken(($return + 2), $matches[1]);
                    }

                    // Do not check PHPStan types that contain any kind of brackets.
                    // See https://phpstan.org/writing-php-code/phpdoc-types#general-arrays .
                } else if (preg_match('/[<\[\{\(]/', $returnType) === 0) {
                    $error = 'Return type "%s" must not contain spaces';
                    $data  = [$returnType];
                    $phpcsFile->addError($error, $return, 'ReturnTypeSpaces', $data);
                }
            }//end if
        }//end if

    }//end processReturn()


    /**
     * Process any throw tags that this function comment has.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token
     *                                                  in the stack passed in $tokens.
     * @param int                         $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processThrows(File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@throws') {
                continue;
            }

            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                $error = 'Exception type missing for @throws tag in function comment';
                $phpcsFile->addError($error, $tag, 'InvalidThrows');
            } else {
                // Any strings until the next tag belong to this comment.
                if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                    $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                } else {
                    $end = $tokens[$commentStart]['comment_closer'];
                }

                $comment    = '';
                $throwStart = null;
                for ($i = ($tag + 3); $i < $end; $i++) {
                    if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                        if ($throwStart === null) {
                            $throwStart = $i;
                        }

                        $indent = 0;
                        if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                            $indent = strlen($tokens[($i - 1)]['content']);
                        }

                        $comment .= ' '.$tokens[$i]['content'];
                        if ($indent < 3) {
                            $error = 'Throws comment indentation must be 3 spaces, found %s spaces';
                            // cspell:ignore TrhowsCommentIndentation
                            $phpcsFile->addError($error, $i, 'TrhowsCommentIndentation', [$indent]);
                        }
                    }
                }

                $comment = trim($comment);

                if ($comment === '') {
                    if (str_word_count($tokens[($tag + 2)]['content'], 0, '\\_') > 1) {
                        $error = '@throws comment must be on the next line';
                        $phpcsFile->addError($error, $tag, 'ThrowsComment');
                    }

                    return;
                }

                // Starts with a capital letter and ends with a full stop.
                $firstChar = $comment[0];
                if (strtoupper($firstChar) !== $firstChar) {
                    $error = '@throws tag comment must start with a capital letter';
                    $phpcsFile->addError($error, $throwStart, 'ThrowsNotCapital');
                }

                $lastChar = substr($comment, -1);
                if (in_array($lastChar, ['.', '!', '?']) === false) {
                    $error = '@throws tag comment must end with a full stop';
                    $phpcsFile->addError($error, $throwStart, 'ThrowsNoFullStop');
                }
            }//end if
        }//end foreach

    }//end processThrows()


    /**
     * Process the function parameter comments.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token
     *                                                  in the stack passed in $tokens.
     * @param int                         $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processParams(File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        $params  = [];
        $maxType = 0;
        $maxVar  = 0;
        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@param') {
                continue;
            }

            $type         = '';
            $typeSpace    = 0;
            $var          = '';
            $varSpace     = 0;
            $comment      = '';
            $commentLines = [];
            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $matches = [];
                preg_match('/((?:(?![$.]|&(?=\$)).)*)(?:((?:\.\.\.)?(?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[($tag + 2)]['content'], $matches);

                $typeLen   = strlen($matches[1]);
                $type      = trim($matches[1]);
                $typeSpace = ($typeLen - strlen($type));
                $typeLen   = strlen($type);
                if ($typeLen > $maxType) {
                    $maxType = $typeLen;
                }

                // If there is more than one word then it is a comment that should be
                // on the next line.
                if (isset($matches[4]) === true && ($typeLen > 0
                    || preg_match('/[^\s]+[\s]+[^\s]+/', $matches[4]) === 1)
                ) {
                    $comment = $matches[4];
                    $error   = 'Parameter comment must be on the next line';
                    $fix     = $phpcsFile->addFixableError($error, ($tag + 2), 'ParamCommentNewLine');
                    if ($fix === true) {
                        $parts = $matches;
                        unset($parts[0]);
                        $parts[3] = "\n *   ";
                        $phpcsFile->fixer->replaceToken(($tag + 2), implode('', $parts));
                    }
                }

                if (isset($matches[2]) === true) {
                    $var = $matches[2];
                } else {
                    $var = '';
                }

                if (substr($var, -1) === '.') {
                    $error = 'Doc comment parameter name "%s" must not end with a dot';
                    $fix   = $phpcsFile->addFixableError($error, ($tag + 2), 'ParamNameDot', [$var]);
                    if ($fix === true) {
                        $content = $type.' '.substr($var, 0, -1);
                        $phpcsFile->fixer->replaceToken(($tag + 2), $content);
                    }

                    // Continue with the next parameter to avoid confusing
                    // overlapping errors further down.
                    continue;
                }

                $varLen = strlen($var);
                if ($varLen > $maxVar) {
                    $maxVar = $varLen;
                }

                // Any strings until the next tag belong to this comment.
                if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                    // Ignore code tags and include them within this comment.
                    $skipTags = [
                        '@code',
                        '@endcode',
                        '@link',
                    ];
                    $skipPos  = $pos;
                    while (isset($tokens[$commentStart]['comment_tags'][($skipPos + 1)]) === true) {
                        $skipPos++;
                        if (in_array($tokens[$tokens[$commentStart]['comment_tags'][$skipPos]]['content'], $skipTags) === false
                            // Stop when we reached the next tag on the outer @param level.
                            && $tokens[$tokens[$commentStart]['comment_tags'][$skipPos]]['column'] === $tokens[$tag]['column']
                        ) {
                            break;
                        }
                    }

                    if ($tokens[$tokens[$commentStart]['comment_tags'][$skipPos]]['column'] === ($tokens[$tag]['column'] + 2)) {
                        $end = $tokens[$commentStart]['comment_closer'];
                    } else {
                        $end = $tokens[$commentStart]['comment_tags'][$skipPos];
                    }
                } else {
                    $end = $tokens[$commentStart]['comment_closer'];
                }//end if

                for ($i = ($tag + 3); $i < $end; $i++) {
                    if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                        $indent = 0;
                        if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                            $indent = strlen($tokens[($i - 1)]['content']);
                            // There can be @code or @link tags within an @param comment.
                            if ($tokens[($i - 2)]['code'] === T_DOC_COMMENT_TAG) {
                                $indent = 0;
                                if ($tokens[($i - 3)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                                    $indent = strlen($tokens[($i - 3)]['content']);
                                }
                            }
                        }

                        $comment       .= ' '.$tokens[$i]['content'];
                        $commentLines[] = [
                            'comment' => $tokens[$i]['content'],
                            'token'   => $i,
                            'indent'  => $indent,
                        ];
                        if ($indent < 3) {
                            $error = 'Parameter comment indentation must be 3 spaces, found %s spaces';
                            $fix   = $phpcsFile->addFixableError($error, $i, 'ParamCommentIndentation', [$indent]);
                            if ($fix === true) {
                                $phpcsFile->fixer->replaceToken(($i - 1), '   ');
                            }
                        }
                    }//end if
                }//end for

                // The first line of the comment must be indented no more than 3
                // spaces, the following lines can be more so we only check the first
                // line.
                if (empty($commentLines[0]['indent']) === false && $commentLines[0]['indent'] > 3) {
                    $error = 'Parameter comment indentation must be 3 spaces, found %s spaces';
                    $fix   = $phpcsFile->addFixableError($error, ($commentLines[0]['token'] - 1), 'ParamCommentIndentation', [$commentLines[0]['indent']]);
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken(($commentLines[0]['token'] - 1), '   ');
                    }
                }

                if ($comment === '') {
                    $error = 'Missing parameter comment';
                    $phpcsFile->addError($error, $tag, 'MissingParamComment');
                    $commentLines[] = ['comment' => ''];
                }//end if

                $variableArguments = false;
                // Allow the "..." @param doc for a variable number of parameters.
                // This could happen with type defined as @param array ... or
                // without type defined as @param ...
                if ($tokens[($tag + 2)]['content'] === '...'
                    || (substr($tokens[($tag + 2)]['content'], -3) === '...'
                    && count(explode(' ', $tokens[($tag + 2)]['content'])) === 2)
                ) {
                    $variableArguments = true;
                }

                if ($typeLen === 0 && $variableArguments === false) {
                    $error = 'Missing parameter type';
                    // If there is just one word as comment at the end of the line
                    // then this is probably the data type. Move it before the
                    // variable name.
                    if (isset($matches[4]) === true && preg_match('/[^\s]+[\s]+[^\s]+/', $matches[4]) === 0) {
                        $fix = $phpcsFile->addFixableError($error, $tag, 'MissingParamType');
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($tag + 2), $matches[4].' '.$var);
                        }
                    } else {
                        $phpcsFile->addError($error, $tag, 'MissingParamType');
                    }
                }

                if (empty($matches[2]) === true && $variableArguments === false) {
                    $error = 'Missing parameter name';
                    $phpcsFile->addError($error, $tag, 'MissingParamName');
                }
            } else {
                $error = 'Missing parameter type';
                $phpcsFile->addError($error, $tag, 'MissingParamType');
            }//end if

            $params[] = [
                'tag'          => $tag,
                'type'         => $type,
                'var'          => $var,
                'comment'      => $comment,
                'commentLines' => $commentLines,
                'type_space'   => $typeSpace,
                'var_space'    => $varSpace,
            ];
        }//end foreach

        $realParams  = $phpcsFile->getMethodParameters($stackPtr);
        $foundParams = [];

        $checkPos = 0;
        foreach ($params as $pos => $param) {
            if ($param['var'] === '') {
                continue;
            }

            $foundParams[] = $param['var'];

            // If the type is empty, the whole line is empty.
            if ($param['type'] === '') {
                continue;
            }

            // Make sure the param name is correct.
            $matched = false;
            // Parameter documentation can be omitted for some parameters, so we have
            // to search the rest for a match.
            $realName = '<undefined>';
            while (isset($realParams[($checkPos)]) === true) {
                $realName = $realParams[$checkPos]['name'];

                if ($realName === $param['var']
                    || ($realParams[$checkPos]['pass_by_reference'] === true
                    && ('&'.$realName) === $param['var'])
                    || ($realParams[$checkPos]['variable_length'] === true
                    && ('...'.$realName) === $param['var'])
                ) {
                    $matched = true;
                    break;
                }

                $checkPos++;
            }

            // Support variadic arguments.
            if (preg_match('/(\s+)\.{3}$/', $param['type'], $matches) === 1) {
                $param['type_space'] = strlen($matches[1]);
                $param['type']       = preg_replace('/\s+\.{3}$/', '', $param['type']);
            }

            // Check the param type value. This could also be multiple parameter
            // types separated by '|'.
            $typeNames      = explode('|', $param['type']);
            $suggestedNames = [];
            foreach ($typeNames as $i => $typeName) {
                $suggestedNames[] = static::suggestType($typeName);
            }

            $suggestedType = implode('|', $suggestedNames);

            if (preg_match('/\s/', $param['type']) === 1) {
                // Do not check PHPStan types that contain any kind of brackets.
                // See https://phpstan.org/writing-php-code/phpdoc-types#general-arrays .
                if (preg_match('/[<\[\{\(]/', $param['type']) === 0) {
                    $error = 'Parameter type "%s" must not contain spaces';
                    $data  = [$param['type']];
                    $phpcsFile->addError($error, $param['tag'], 'ParamTypeSpaces', $data);
                }
            } else if ($param['type'] !== $suggestedType) {
                $error = 'Expected "%s" but found "%s" for parameter type';
                $data  = [
                    $suggestedType,
                    $param['type'],
                ];
                $fix   = $phpcsFile->addFixableError($error, $param['tag'], 'IncorrectParamVarName', $data);
                if ($fix === true) {
                    $content  = $suggestedType;
                    $content .= str_repeat(' ', $param['type_space']);
                    $content .= $param['var'];
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
                }
            }//end if

            // Check number of spaces after the type.
            $spaces = 1;
            if ($param['type_space'] !== $spaces) {
                $error = 'Expected %s spaces after parameter type; %s found';
                $data  = [
                    $spaces,
                    $param['type_space'],
                ];

                $fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamType', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();

                    $content  = $param['type'];
                    $content .= str_repeat(' ', $spaces);
                    $content .= $param['var'];
                    $content .= str_repeat(' ', $param['var_space']);
                    // At this point there is no description expected in the
                    // param line so no need to append comment.
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);

                    // Fix up the indent of additional comment lines.
                    foreach ($param['commentLines'] as $lineNum => $line) {
                        if ($lineNum === 0
                            || $param['commentLines'][$lineNum]['indent'] === 0
                        ) {
                            continue;
                        }

                        $newIndent = max(($param['commentLines'][$lineNum]['indent'] + $spaces - $param['type_space']), 0);
                        $phpcsFile->fixer->replaceToken(
                            ($param['commentLines'][$lineNum]['token'] - 1),
                            str_repeat(' ', $newIndent)
                        );
                    }

                    $phpcsFile->fixer->endChangeset();
                }//end if
            }//end if

            if ($matched === false) {
                if ($checkPos >= $pos) {
                    $code = 'ParamNameNoMatch';
                    $data = [
                        $param['var'],
                        $realName,
                    ];

                    $error = 'Doc comment for parameter %s does not match ';
                    if (strtolower($param['var']) === strtolower($realName)) {
                        $error .= 'case of ';
                        $code   = 'ParamNameNoCaseMatch';
                    }

                    $error .= 'actual variable name %s';

                    $phpcsFile->addError($error, $param['tag'], $code, $data);
                    // Reset the parameter position to check for following
                    // parameters.
                    $checkPos = ($pos - 1);
                } else if (substr($param['var'], -4) !== ',...') {
                    // We must have an extra parameter comment.
                    $error = 'Superfluous parameter comment';
                    $phpcsFile->addError($error, $param['tag'], 'ExtraParamComment');
                }//end if
            }//end if

            $checkPos++;

            if ($param['comment'] === '') {
                continue;
            }

            // Param comments must start with a capital letter and end with the full stop.
            if (isset($param['commentLines'][0]['comment']) === true) {
                $firstChar = $param['commentLines'][0]['comment'];
            } else {
                $firstChar = $param['comment'];
            }

            if (preg_match('|\p{Lu}|u', $firstChar) === 0) {
                $error = 'Parameter comment must start with a capital letter';
                if (isset($param['commentLines'][0]['token']) === true) {
                    $commentToken = $param['commentLines'][0]['token'];
                } else {
                    $commentToken = $param['tag'];
                }

                $phpcsFile->addError($error, $commentToken, 'ParamCommentNotCapital');
            }

            $lastChar = substr($param['comment'], -1);
            if (in_array($lastChar, ['.', '!', '?', ')']) === false) {
                $error = 'Parameter comment must end with a full stop';
                if (empty($param['commentLines']) === true) {
                    $commentToken = ($param['tag'] + 2);
                } else {
                    $lastLine     = end($param['commentLines']);
                    $commentToken = $lastLine['token'];
                }

                // Don't show an error if the end of the comment is in a code
                // example.
                if ($this->isInCodeExample($phpcsFile, $commentToken, $param['tag']) === false) {
                    $fix = $phpcsFile->addFixableError($error, $commentToken, 'ParamCommentFullStop');
                    if ($fix === true) {
                        // Add a full stop as the last character of the comment.
                        $phpcsFile->fixer->addContent($commentToken, '.');
                    }
                }
            }
        }//end foreach

        // Missing parameters only apply to methods and not function because on
        // functions it is allowed to leave out param comments for form constructors
        // for example.
        // It is also allowed to omit param tags completely, in which case we don't
        // throw errors. Only throw errors if param comments exists but are
        // incomplete on class methods.
        if ($tokens[$stackPtr]['level'] > 0 && empty($foundParams) === false) {
            foreach ($realParams as $realParam) {
                $realParamKeyName = $realParam['name'];
                if (in_array($realParamKeyName, $foundParams) === false
                    && (($realParam['pass_by_reference'] === true
                    && in_array("&$realParamKeyName", $foundParams) === true)
                    || ($realParam['variable_length'] === true
                    && in_array("...$realParamKeyName", $foundParams) === true)) === false
                ) {
                    $error = 'Parameter %s is not described in comment';
                    $phpcsFile->addError($error, $commentStart, 'ParamMissingDefinition', [$realParam['name']]);
                }
            }
        }//end if

    }//end processParams()


    /**
     * Process the function "see" comments.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token
     *                                                  in the stack passed in $tokens.
     * @param int                         $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processSees(File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            if ($tokens[$tag]['content'] !== '@see') {
                continue;
            }

            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $comment = $tokens[($tag + 2)]['content'];
                if (strpos($comment, ' ') !== false) {
                    $error = 'The @see reference should not contain any additional text';
                    $phpcsFile->addError($error, $tag, 'SeeAdditionalText');
                    continue;
                }

                if (preg_match('/[\.!\?]$/', $comment) === 1) {
                    $error = 'Trailing punctuation for @see references is not allowed.';
                    $fix   = $phpcsFile->addFixableError($error, $tag, 'SeePunctuation');
                    if ($fix === true) {
                        // Replace the last character from the comment which is
                        // already tested to be a punctuation.
                        $content = substr($comment, 0, -1);
                        $phpcsFile->fixer->replaceToken(($tag + 2), $content);
                    }//end if
                }
            }
        }//end foreach

    }//end processSees()


    /**
     * Returns a valid variable type for param/var tag.
     *
     * @param string $type The variable type to process.
     *
     * @return string
     */
    public static function suggestType($type)
    {
        if (isset(static::$invalidTypes[$type]) === true) {
            return static::$invalidTypes[$type];
        }

        if ($type === '$this') {
            return $type;
        }

        // Also allow some more characters for special type hints supported by
        // PHPStan:
        // https://phpstan.org/writing-php-code/phpdoc-types#basic-types .
        $type = preg_replace('/[^a-zA-Z0-9_\\\[\]\-<> ,"\{\}\?\':\*\|\&\(\)]/', '', $type);

        return $type;

    }//end suggestType()


    /**
     * Determines if a comment line is part of an @code/@endcode example.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the current token
     *                                                  in the stack passed in $tokens.
     * @param int                         $commentStart The position of the start of the comment
     *                                                  in the stack passed in $tokens.
     *
     * @return boolean Returns true if the comment line is within a @code block,
     *                 false otherwise.
     */
    protected function isInCodeExample(File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();
        if (strpos($tokens[$stackPtr]['content'], '@code') !== false) {
            return true;
        }

        $prevTag = $phpcsFile->findPrevious([T_DOC_COMMENT_TAG], ($stackPtr - 1), $commentStart);
        if ($prevTag === false) {
            return false;
        }

        if ($tokens[$prevTag]['content'] === '@code') {
            return true;
        }

        return false;

    }//end isInCodeExample()


}//end class
