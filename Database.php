<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private DatabaseArgumentConverterInterface $converter;
    private mysqli $mysqli;

    public function __construct(DatabaseArgumentConverterInterface $converter, mysqli $mysqli)
    {
        $this->$mysqli = $mysqli;
        $this->converter = $converter;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (substr_count($query, '?') != count($args)) {
            throw new Exception("Argument number does not match the number of substitutions!");
        }

        $query = $this->converter->replaceQuestionsWithValues($query, $args);
        $query = $this->converter->processConditionBrackets($query, $this->skip());

        return $this->converter->correctSingleQuotes($query);
    }


    public function skip()
    {
        return '!!!NONONO_WE_SKIP_THIS_URGENTLY!!!';
    }
}
