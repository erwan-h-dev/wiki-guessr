<?php

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class JsonContains extends FunctionNode
{

    private Node $jsonDoc;
    private Node $val;

    /**
     * @param Parser $parser
     * @return void
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->jsonDoc = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->val = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /**
     * @param SqlWalker $sqlWalker
     * @return string
     * @throws QueryException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'JSON_CONTAINS(' .
            $this->jsonDoc->dispatch($sqlWalker) . ', ' .
            $this->val->dispatch($sqlWalker) .
        ')';
    }
}
