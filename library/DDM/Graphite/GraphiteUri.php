<?php

namespace DDM\Graphite;

use DDM\Graphite\Exception\RuntimeException;

class GraphiteUri extends \Zend_Uri_Http
{
    /**
     * Flag that determines whether to use square bracket notation
     * for arrays
     *
     * @var boolean
     */
    protected $_useSquareBracketNotation = true;

    /**
     * If set to false then param arrays will not include square brackets
     * instead of param[]=123&param[]=321 you have param=123&param=321
     *
     * NOTE: array keys will be lost
     * param[x1]=123 x1 will be lost
     *
     * @param boolean $flag
     */
    public function setUseSquareBracketNotation($flag)
    {
        $this->_useSquareBracketNotation = $flag;
    }

    /**
     * Allows for disabling square bracket notation
     *
     * (non-PHPdoc)
     * @see Zend_Uri_Http::setQuery()
     */
    public function setQuery($query)
    {
        $oldQuery = parent::setQuery($query);

        if (! $this->_useSquareBracketNotation) {
            $this->_query = preg_replace('#%5B.*?%5D#', '', $this->_query);
        }

        return $oldQuery;
    }

	/**
     * Creates a GraphiteUri from a string
     *

     * @param  string $scheme either 'http' | 'https'
     * @param  string $schemeSpecific The scheme-specific part of the URI
     *
     *                                   does not start with http:// or https://
     * @throws RuntimeException          When the given $uri is invalid
     * @return GraphiteUri
     */
    public static function fromScheme($scheme, $schemeSpecific='')
    {
        if (in_array($scheme, array('http', 'https')) === false) {
            throw new RuntimeException("Invalid scheme: '$scheme'");
        }

        return new GraphiteUri($scheme, $schemeSpecific);
    }
}