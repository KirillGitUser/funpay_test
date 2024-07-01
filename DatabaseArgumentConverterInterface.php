<?php

namespace FpDbTest;

interface DatabaseArgumentConverterInterface
{
    public function replaceQuestionsWithValues(string $query, array $args) : string;
    public function processConditionBrackets(string $query, string $skip) : string;
    public function correctSingleQuotes(string $query) : string;
}
