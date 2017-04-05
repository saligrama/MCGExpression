<?php

    function declare_globals() {

        // operators
        // presence of OR as operator for answers with multiple answers (i.e., roots of a quadratic)
        // - is used as a function, not as an operator, to deal with unary minuses (i.e "-3+12PI")
        $GLOBALS["operators"] = [
            '+' => ['precedence' => 0, 'associativity' => 'left', 'commutativity' => true],
            '*' => ['precedence' => 1, 'associativity' => 'left', 'commutativity' => true],
            '/' => ['precedence' => 1, 'associativity' => 'left', 'commutativity' => false],
            '%' => ['precedence' => 1, 'associativity' => 'left'], 'commutativity' => false],
            '^' => ['precedence' => 2, 'associativity' => 'right', 'commutativity' => false],
            'OR' => ['precedence' => 3, 'associativity' => 'left', 'commutativity' => true],
        ];

        // specials
        $GLOBALS["specials"] = ['PI', 'E', 'I'];

        // functions
        $GLOBALS["functions"] = [
            'SQRT',
            'ABS',
            'ACOS',
            'COS',
            'TAN',
            'ATAN',
            'ASIN',
            'SIN',
            'CEIL',
            'FLOOR',
            'LOG',
            'LN'
        ];

        // regexes for matching each type of token
        $GLOBALS["format_regexes"] = [
            "number" => "(\d+\.?\d*)",
            "special" => "(PI|E|I)",
            "lparen" => "(\()",
            "rparen" => "(\))",
            "function" => "(SQRT|ABS|ACOS|COS|ATAN|TAN|ASIN|SIN|CEIL|FLOOR|LOG|LN)",
            "operator" => "(\+|\-|\*|\/|\^|OR)",
            "suffix" => "(%|$)"
        ];

        // replace patterns for preg_replace() for formatting tokens
        $GLOBALS["format_replaces"] = [
            "generic_implicit" => "$1 * $2",
            "number_lparen_implicit" => "$1 * (",
            "operator_space" =>  " $1 $2",
            "paren_space" => " $1 "
        ];

    }

    function format($expr) {

        // implicit multiplication

        $regexes = [
            // implicit multiplication
            "/" . $GLOBALS["format_regexes"]["number"] . $GLOBALS["format_regexes"]["special"] . "/", // number, then special
            "/" . $GLOBALS["format_regexes"]["special"] . $GLOBALS["format_regexes"]["number"] . "/", // special, then number
            "/" . $GLOBALS["format_regexes"]["special"] . $GLOBALS["format_regexes"]["special"] . "/", // special, then special
            "/(" . $GLOBALS["format_regexes"]["number"] . "|" . $GLOBALS["format_regexes"]["special"] . ")" . $GLOBALS["format_regexes"]["lparen"] . "/", // number or special, then lparen
            "/" . $GLOBALS["format_regexes"]["rparen"] . "(" . $GLOBALS["format_regexes"]["number"] . "|" . $GLOBALS["format_regexes"]["special"] . ")/", // rparen, then number or special
            "/" . $GLOBALS["format_regexes"]["number"] . $GLOBALS["format_regexes"]["function"] . "/", // number, then unary function
            "/" . $GLOBALS["format_regexes"]["special"] . $GLOBALS["format_regexes"]["function"] . "/", // special, then unary function

            // operator spacing
            "/" . $GLOBALS["format_regexes"]["operator"] . "/", // add spaces before and after each operator

            // parenthesis spacing
            "/(" . $GLOBALS["format_regexes"]["lparen"] . "|" . $GLOBALS["format_regexes"]["rparen"] . ")/" // add spaces before and after lparen and rparen

        ];

        $replaces = [
            // implicit multiplication
            $GLOBALS["format_replaces"]["generic_implicit"], // number, then special
            $GLOBALS["format_replaces"]["generic_implicit"], // special, then number
            $GLOBALS["format_replaces"]["generic_implicit"], // special, then special
            $GLOBALS["format_replaces"]["number_lparen_implicit"], // number or special, then lparen
            $GLOBALS["format_replaces"]["generic_implicit"], // rparen, then number or special
            $GLOBALS["format_replaces"]["generic_implicit"], // number, then unary function
            $GLOBALS["format_replaces"]["generic_implicit"], // special, then unary function


            // operator spacing
            $GLOBALS["format_replaces"]["operator_space"], //add spaces before and after each operator

            // parenthesis spacing
            $GLOBALS["format_replaces"]["paren_space"], // add spaces before and after lparen and rparen
        ];

        return strtoupper(preg_replace($regexes, $replaces, $expr));

    }

    function tokenize($expr) {

        // remove leading and trailing spaces
        $expr = preg_replace('/\G\s|\s(?=\s*$)/', '', format($expr));

        // replace >=2 spaces with only one space and explode to array
        // then make all minuses unary
        $tokens = fix_minus(array_map('trim', explode(' ', preg_replace('/[ ]+/', ' ', $expr))));

        // get count removing parentheses to reduce steps in shunting_yard()
        $count = 0;
        foreach ($tokens as $token) {
            $count = (!preg_match("/(" . $GLOBALS["format_regexes"]["lparen"] . "|" . $GLOBALS["format_regexes"]["rparen"] . ")/", $token)) ? $count + 1 : $count;
        }
        // return array with tokenized array and count
        return [$tokens, $count];

    }


?>
