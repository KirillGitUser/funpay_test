<?php

namespace FpDbTest;

use Exception;

class DatabaseArgumentConverter implements DatabaseArgumentConverterInterface {

    public function __construct() { }

    function convert(string $type, mixed $item): string {
        if (is_array($item)) {
            if ($type == ' ' || $type == 'a' || $type == '#') {
                if ($this->is_associative($item)) {
                    // for associative array lets unfold into string
                    $val = [];
                    foreach ($item as $key => $value) {
                        $val[] = $this->convert('#', $key).' = '.str_replace('`', '\'', $this->convert('#', $value));
                    }
    
                    return implode(', ', $val);
                }
                else {
                    // for regular array let's just go over
                    $val = [];
                    foreach ($item as $i) {
                        $val[] = $this->convert('#', $i);
                    }
    
                    return implode(', ', $val);
                }
            }
            else {
                throw new Exception('Substitution and argument type mismatch, expected [a, #, _], actual - '.$type);
            }
        }
        else if (gettype($item) == 'integer') {
            if ($type == ' ' || $type == 'd' || $type == '#') {
                // integer
                return (string)$item;
            }
            else {
                throw new Exception('Substitution and argument type mismatch, expected [d, #, _], actual - '.$type);
            }
        }
        else if (gettype($item) == 'double') {
            if ($type == ' ' || $type == 'f' || $type == '#') {
                // double/float
                return (string)$item;
            }
            else {
                throw new Exception('Substitution and argument type mismatch, expected [f, #, _], actual - '.$type);
            }
        }
        else if (gettype($item) == 'string' || gettype($item) == 'boolean' || gettype($item) == 'NULL') {
            if ($type == ' ' || $type == '#' || $type == 'd') {
                // string / bool / NULL
                switch (gettype($item)) {
                    case 'string'  : return '`'.$item.'`';
                    case 'boolean' : return $item == 'true' ? '1' : '0';
                    case 'NULL'    : return 'NULL';
                }
            }
            else {
                throw new Exception('Substitution and argument type mismatch, expected [#, _], actual - '.$type);
            }
        }
    
        return '';
    }

    public function replaceQuestionsWithValues(string $query, array $args) : string {
        $questionIndexes = $this->getSubtringIndexes($query, '?');
        
        foreach ($args as $item) {
            if (count($questionIndexes) > 0) {
                $questionIndex = $questionIndexes[0]["index"];
                $nextSymbol = $questionIndexes[0]["char"];
                $substitution = $this->convert($nextSymbol, $item);            
                $query = substr($query, 0, $questionIndex).$substitution.substr($query, $questionIndex + 1 + ($nextSymbol == ' ' ? 0 : 1));
                $questionIndexes = $this->getSubtringIndexes($query, '?');
            }
        }

        return $query;
    }

    public function processConditionBrackets(string $query, string $skip) : string {
        foreach ($this->getConditionsArray($query)[0] as $substring) {
            if (str_contains($substring, $skip)) {
                $query = str_replace($substring, '', $query);
            }
            else {
                $replacement = str_replace('{', '', $substring);
                $replacement = str_replace('}', '', $replacement);

                $query = str_replace($substring, $replacement, $query);
            }
        }

        return $query;
    }

    public function correctSingleQuotes(string $query) : string {
        $words = explode(' ', $query);

        for ($i = 0; $i < count($words); $i++) {
            if (str_contains($words[$i], '`') && $words[$i - 1] == '=') {
                $words[$i] = str_replace('`', '\'', $words[$i]);
            }
        }

        $query = implode(' ', $words);

        return $query;
    }

    function getConditionsArray(string $source) : array {
        $conditions = [];
        preg_match_all('#\{.*?\}#', $source, $conditions);
        return $conditions;
    }

    function getSubtringIndexes(string $source, string $pattern) : array {
        $indexes = [];
        $curIndex = 0;
        for ($i = 0; $i < substr_count($source, $pattern); $i++) {
            $curIndex = strpos($source, $pattern, $curIndex);
            if ($curIndex < strlen($source)) {
                $substr = substr($source, $curIndex + 1, 1);
                $indexes[$i] = array("index" => $curIndex, "char" => $substr);
            }
        }
        return $indexes;
    }
    
    function is_associative($array): bool {
        return array_values($array) !== $array;
    }
}