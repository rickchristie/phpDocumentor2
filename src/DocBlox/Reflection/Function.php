<?php
/**
 * DocBlox
 *
 * PHP Version 5
 *
 * @category  DocBlox
 * @package   Reflection
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://docblox-project.org
 */

/**
 * Reflection class for a function declaration.
 *
 * @category DocBlox
 * @package  Reflection
 * @author   Mike van Riel <mike.vanriel@naenius.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT
 * @link     http://docblox-project.org
 */
class DocBlox_Reflection_Function extends DocBlox_Reflection_BracesAbstract
{
    /** @var string identifier for the 'type' value of FUNCTION */
    const TYPE_FUNCTION = 'function';

    /** @var string identifier for the 'type' value of CLOSURE */
    const TYPE_CLOSURE = 'closure';

    /** @var int Index of the first token in the argument list*/
    protected $arguments_token_start = 0;

    /** @var int Index of the last token in the argument list*/
    protected $arguments_token_end = 0;

    /** @var DocBlox_Reflection_Argument[] contains all arguments */
    protected $arguments = array();

    /** @var string Whether this is a 'function' or 'closure' */
    protected $type = self::TYPE_FUNCTION;

    /**
     * Retrieves the generic information.
     *
     * Finds out which name and arguments this function has on top of the
     * information found using the DocBlox_Reflection_BracesAbstract parent
     * method.
     *
     * @param DocBlox_Reflection_TokenIterator $tokens Tokens to interpret with
     *     the pointer at the token to be processed.
     *
     * @see DocBlox_ReflectionBracesAbstract::processGenericInformation
     *
     * @return void
     */
    protected function processGenericInformation(
        DocBlox_Reflection_TokenIterator $tokens
    ) {
        $this->setName($this->findName($tokens));

        parent::processGenericInformation($tokens);

        list($start_index, $end_index) = $tokens->getTokenIdsOfParenthesisPair();
        $this->arguments_token_start = $start_index;
        $this->arguments_token_end = $end_index;
    }

    /**
     * Extracts the arguments from this function.
     *
     * @param DocBlox_Reflection_TokenIterator $tokens Tokens to interpret with
     *     the pointer at the token to be processed.
     *
     * @return void
     */
    public function processVariable(DocBlox_Reflection_TokenIterator $tokens)
    {
        // is the variable occurs within arguments parenthesis then it is
        // an argument
        if (($tokens->key() > $this->arguments_token_start)
            && ($tokens->key() < $this->arguments_token_end)
        ) {
            $argument = new DocBlox_Reflection_Argument();
            $argument->parseTokenizer($tokens);
            $this->arguments[$argument->getName()] = $argument;
        }
    }

    /**
     * Finds the name of this function starting from the T_FUNCTION token.
     *
     * If a function has no name it is probably a Closure and will have the
     * name Closure.
     *
     * @param DocBlox_Reflection_TokenIterator $tokens Tokens to interpret with
     *     the pointer at the token to be processed.
     *
     * @return string
     */
    protected function findName(DocBlox_Reflection_TokenIterator $tokens)
    {
        $name = $tokens->findNextByType(T_STRING, 5, array('{', ';'));

        $this->setType($name ? self::TYPE_FUNCTION : self::TYPE_CLOSURE);

        return $name ? $name->content : 'Closure';
    }

    /**
     * Sets whether this is a function or closure.
     *
     * @param string $type Must be either 'function' or 'closure'.
     *
     * @return void
     */
    public function setType($type)
    {
        if (!in_array($type, array(self::TYPE_CLOSURE, self::TYPE_FUNCTION))) {
            throw new InvalidArgumentException(
                'Expected type of function to either match "'
                . self::TYPE_FUNCTION . '" or "' . self::TYPE_CLOSURE
                . '", received: ' . $type
            );
        }

        $this->type = $type;
    }

    /**
     * Returns whether this is a function or closure.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the arguments for this element.
     *
     * @return DocBlox_Reflection_Argument[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns the XML representation of this object or false if an error
     * occurred.
     *
     * @return string|boolean
     */
    public function __toXml()
    {
        $xml = new SimpleXMLElement('<function></function>');
        $xml['namespace'] = $this->getNamespace();
        $xml['line'] = $this->getLineNumber();
        $xml->name = $this->getName();
        $xml->type = $this->getType();
        $this->addDocblockToSimpleXmlElement($xml);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml->asXML());

        foreach ($this->arguments as $argument) {
            $this->mergeXmlToDomDocument($dom, $argument->__toXml());
        }

        return trim($dom->saveXML());
    }

}