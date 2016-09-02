#!/usr/bin/env php
<?php

    require("newslice.php");
    require("unary_minus.php");

    declare_globals();
    $input1 = "4PI-4E";
    $input2 = "-(4E) + 4PI";
    $tok_meta1 = tokenize($input1);
    $tok_meta2 = tokenize($input2);
    //echo $tok_meta1[1] . "\n";
    //echo $tok_meta2[1] . "\n";
    //print_r(shunting_yard($tok_meta2[0], $tok_meta2[1]));
    echo compare(shunting_yard($tok_meta1[0], $tok_meta1[1]), shunting_yard($tok_meta2[0], $tok_meta2[1])) ? "t" : "f";

    function declare_globals() {

        // operators
        $GLOBALS["operators"] = [
            '+' => ['precedence' => 0, 'associativity' => 'left'],
            '*' => ['precedence' => 1, 'associativity' => 'left'],
            '/' => ['precedence' => 1, 'associativity' => 'left'],
            '%' => ['precedence' => 1, 'associativity' => 'left'],
            '^' => ['precedence' => 2, 'associativity' => 'right'],
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
            "operator" => "(\+|\-|\*|\/)",
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

        return preg_replace($regexes, $replaces, $expr);

    }

    function tokenize($expr) {

        $expr = preg_replace('/\G\s|\s(?=\s*$)/', '', format($expr));
        $tokens = array_map('trim', explode(' ', preg_replace('/[ ]+/', ' ', $expr)));
        $tokens_ret = fix_minus($tokens);
        $count = 0;
        foreach ($tokens_ret as $token) {
            $count = (!preg_match("/(" . $GLOBALS["format_regexes"]["lparen"] . "|" . $GLOBALS["format_regexes"]["rparen"] . ")/", $token)) ? $count + 1 : $count;
        }
        return [$tokens_ret, $count];

    }

    function shunting_yard(array $tokens, $length) {

        $stack = new \SplStack();
        $output = new \SplQueue();
        foreach (array_reverse($tokens) as $token) {
            if (is_numeric($token) || preg_match("/" . $GLOBALS["format_regexes"]["special"] . "/", $token)) {
                $output->enqueue([
                                  'type' => 'operand',
                                  'value' => $token
                                ]);
                $last_popped_lparen = 0;
            } elseif (isset($GLOBALS["operators"][$token])) {
                $o1 = $token;
                while (has_operator($stack) && ($o2 = $stack->top()) && has_lower_precedence($o1, $o2)) {
                    $c = count($output);
                    $output->enqueue([
                        'type' => 'operator',
                        'symbol' => $stack->pop(),
                        'op1_ndx' => $length - $c,
                        'op2_ndx' => getoperand_base($output, $c)
                    ]);
                    $last_popped_lparen = 0;
                }
                $stack->push($o1);
            } elseif (')' === $token) {
                $stack->push($token);
            } elseif ('(' === $token) {
                while (count($stack) > 0 && ')' !== $stack->top()) {
                    $c = count($output);
                    $output->enqueue([
                        'type' => 'operator',
                        'symbol' => $stack->pop(),
                        'op1_ndx' => $length - $c,
                        'op2_ndx' => getoperand_base($output, $c)
                    ]);
                }
                if (count($stack) === 0) {
                    throw new \InvalidArgumentException(sprintf('Mismatched parenthesis in input: %s', json_encode($tokens)));
                }
                // pop off lparen
                $stack->pop();
                $last_popped_lparen = 1;
            } elseif (preg_match("/" . preg_replace("/(\))/", "|-$1", $GLOBALS["format_regexes"]["function"]) . "/", $token)) {
                if ($last_popped_lparen || $stack->top() == ')') {
                    $c = count($output);
                    $output->enqueue([
                        'type' => 'function',
                        'name' => $token,
                        'op_ndx' => $c - 1
                    ]);
                } else {
                    throw new \InvalidArgumentException(sprintf('Function %s does not have parenthesis', $token));
                }
            } else {
                throw new \InvalidArgumentException(sprintf('Invalid token: %s', $token));
            }
        }
        while (has_operator($stack)) {
            $c = count($output);
            $output->enqueue([
                'type' => 'operator',
                'symbol' => $stack->pop(),
                'op1_ndx' => $length - $c,
                'op2_ndx' => getoperand_base($output, $c)
            ]);
        }
        if (count($stack) > 0) {
            throw new \InvalidArgumentException(sprintf('Mismatched parenthesis or misplaced number in input: %s', json_encode($tokens)));
        }

        $pn = array_reverse(iterator_to_array($output));
        $i = 0;
        while(isset($pn[$i])) {

            if ($pn[$i]["type"] == "operator")
                $pn[$i]["op2_ndx"] = $length - $pn[$i]["op2_ndx"] - 1;
            elseif($pn[$i]["type"] == "function")
                $pn[$i]["op_ndx"] = $length - $pn[$i]["op_ndx"] - 1;

            $i++;

        }

        return $pn;

    }

    function has_operator(\SplStack $stack) {
        return count($stack) > 0 && ($top = $stack->top()) && isset($GLOBALS["operators"][$top]);
    }

    function has_lower_precedence($o1, $o2) {
        $op1 = $GLOBALS["operators"][$o1];
        $op2 = $GLOBALS["operators"][$o2];
        return ('left' === $op1['associativity'] && $op1['precedence'] === $op2['precedence']) || $op1['precedence'] < $op2['precedence'];
    }

    function getoperand_base(\SplQueue $output, $ndx) {

        if ($output[$ndx - 1]["type"] == "operator")
            return end_of_opchain($output, $output[$ndx - 1]["op2_ndx"]) - 1;
        elseif ($output[$ndx - 1]["type"] == "function")
            return end_of_opchain($output, $output[$ndx - 1]["op_ndx"]) - 1;
        else
            return $ndx - 2;

    }

    function end_of_opchain(\SplQueue $output, $ndx) {

        if ($output[$ndx]["type"] == "operator")
            return end_of_opchain($output, $output[$ndx]["op2_ndx"]);
        elseif($output[$ndx]["type"] == "function")
            return end_of_opchain($output, $output[$ndx]["op_ndx"]);
        else
            return $ndx;

    }

    function compare($arr1, $arr2) {

        if ($arr1 == $arr2)
            return true;

        // TODO smart count with unary minus support

        else {

            $compares = [];
            $compares = block_compare($arr1, $arr2, $compares);
            print_r($compares);
            return !in_array(0, $compares);

        }

    }
    // compare simplest order operations => $e1, $e2 each have one unary operator and two constant/numerical operands
    function simple_compare($e1, $e2) {

        //echo "E1: "; print_r($e1);
        //echo "E2: "; print_r($e2);

        $e1_min = min(array_keys($e1));
        $e2_min = min(array_keys($e2));

        if ($e1[$e1_min]["symbol"] == $e2[$e2_min]["symbol"]) {
            if ($e1[$e1_min + 1]["value"] == $e2[$e2_min + 1]["value"] &&
                $e1[$e1_min + 2]["value"] == $e2[$e2_min + 2]["value"]) {
                return true;
            }
            elseif (($e1[$e1_min]["symbol"] == "+" ||
                     $e1[$e1_min]["symbol"] == "*") &&
                     $e1[$e1_min + 1] == $e2[$e2_min + 2] &&
                     $e1[$e1_min + 2] == $e2[$e2_min + 1]) {
                return true;
            }
            else {
                return false;
            }

        }
        else {
            return false;
        }

    }

    // compare blocks
    function block_compare($e1, $e2, $compares) {

        echo "E1: "; print_r($e1);
        echo "E2: "; print_r($e2);

        $e1_min = min(array_keys($e1));
        $e2_min = min(array_keys($e2));

        if ($e1[$e1_min]["type"] == "function" && $e2[$e2_min]["type"] == "function")
            $compares = unary_func_compare($e1, $e2, $compares);

        else {

            // expr operators
            $e1_operator = $e1[$e1_min];
            $e2_operator = $e2[$e2_min];

            // find expr operand blocks
            $e1_op1 = newSlice($e1, $e1[$e1_min]["op1_ndx"],
                                  ($e1[$e1_min]["op2_ndx"] - $e1[$e1_min]["op1_ndx"]), true);
            $e1_op2 = newSlice($e1, $e1[$e1_min]["op2_ndx"], NULL, true);
            $e2_op1 = newSlice($e2, $e2[$e2_min]["op1_ndx"],
                                  ($e2[$e2_min]["op2_ndx"] - $e2[$e2_min]["op1_ndx"]), true);
            $e2_op2 = newSlice($e2, $e2[$e2_min]["op2_ndx"], NULL, true);

            echo "E1_OP1: "; print_r($e1_op1);
            echo "E1_OP2: "; print_r($e1_op2);
            echo "E2_OP1: "; print_r($e2_op1);
            echo "E2_OP2: "; print_r($e2_op2);

            $e1_op1_min = min(array_keys($e1_op1));
            $e1_op2_min = min(array_keys($e1_op2));
            $e2_op1_min = min(array_keys($e2_op1));
            $e2_op2_min = min(array_keys($e2_op2));

            // simple block
            $e1_op1_issblock = (count($e1_op1) == 3) ? 1 : 0;
            $e1_op2_issblock = (count($e1_op2) == 3) ? 1 : 0;
            $e2_op1_issblock = (count($e2_op1) == 3) ? 1 : 0;
            $e2_op2_issblock = (count($e2_op2) == 3) ? 1 : 0;

            // complex block with unary functions
            $e1_op1_iscblock = (count($e1_op1) > 3) ? 1 : 0;
            $e1_op2_iscblock = (count($e1_op2) > 3) ? 1 : 0;
            $e2_op1_iscblock = (count($e2_op1) > 3) ? 1 : 0;
            $e2_op2_iscblock = (count($e2_op2) > 3) ? 1 : 0;

            // numerical/special value only
            $e1_op1_isval = (count($e1_op1) == 1) ? 1 : 0;
            $e1_op2_isval = (count($e1_op2) == 1) ? 1 : 0;
            $e2_op1_isval = (count($e2_op1) == 1) ? 1 : 0;
            $e2_op2_isval = (count($e2_op2) == 1) ? 1 : 0;

            // unary function only
            $e1_op1_isublock = ($e1_op1[$e1_op1_min]["type"] == "function") ? 1 : 0;
            $e1_op2_isublock = ($e1_op2[$e1_op2_min]["type"] == "function") ? 1 : 0;
            $e2_op1_isublock = ($e2_op1[$e2_op1_min]["type"] == "function") ? 1 : 0;
            $e2_op2_isublock = ($e2_op2[$e2_op2_min]["type"] == "function") ? 1 : 0;

            if ($e1_operator["symbol"] != $e2_operator["symbol"])
                $compares[] = 0;

            else {

                $interm_compares = [];

                if ($e1_op1_issblock && $e2_op1_issblock && simple_compare($e1_op1, $e2_op1)) {

                    $compares[] = 1;

                    if ($e1_op2_issblock && $e2_op2_issblock)
                        $compares[] = simple_compare($e1_op2, $e2_op2) ? 1 : 0;
                    elseif ($e1_op2_iscblock && $e2_op2_iscblock)
                        $compares = block_compare($e1_op2, $e2_op2, $compares);
                    elseif ($e1_op2_isublock && $e2_op2_isublock)
                        $compares = unary_func_compare($e1_op2, $e2_op2, $compares);
                    elseif($e1_op2_isval && $e2_op2_isval)
                        $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op2[$e2_op2_min]["value"]) ? 1 : 0;
                    else
                        $compares[] = 0;

                }

                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*")
                         && $e1_op1_issblock && $e2_op2_issblock && simple_compare($e1_op1, $e2_op2)) {

                    $compares[] = 1;

                    if ($e1_op2_issblock && $e2_op1_issblock)
                        $compares[] = simple_compare($e1_op2, $e2_op1) ? 1 : 0;
                    elseif ($e1_op2_iscblock && $e2_op1_iscblock)
                        $compares = block_compare($e1_op2, $e2_op1, $compares);
                    elseif ($e1_op2_isublock && $e2_op2_isublock)
                        $compares = unary_func_compare($e1_op2, $e2_op1, $compares);
                    elseif($e1_op2_isval && $e2_op1_isval)
                        $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op1[$e2_op1_min]["value"]) ? 1 : 0;
                    else
                        $compares[] = 0;

                }
                elseif ($e1_op1_iscblock && $e2_op1_iscblock &&
                        !in_array(0, block_compare($e1_op1, $e2_op1, $interm_compares))) {

                    $compares[] = 1;
                    $interm_compares = [];

                    if ($e1_op2_issblock && $e2_op2_issblock)
                        $compares[] = simple_compare($e1_op2, $e2_op2) ? 1 : 0;
                    elseif ($e1_op2_iscblock && $e2_op2_iscblock)
                        $compares = block_compare($e1_op2, $e2_op2, $compares);
                    elseif ($e1_op2_isublock && $e2_op2_isublock)
                        $compares = unary_func_compare($e1_op2, $e2_op2, $compares);
                    elseif($e1_op2_isval && $e2_op2_isval)
                        $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op2[$e2_op2_min]["value"]) ? 1 : 0;
                    else
                        $compares[] = 0;

                }
                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*") &&
                         $e1_op1_iscblock && $e2_op2_iscblock &&
                         !in_array(0, block_compare($e1_op1, $e2_op2, $interm_compares))) {

                     $compares[] = 1;
                     $interm_compares = [];

                     if ($e1_op2_issblock && $e2_op1_issblock)
                         $compares[] = simple_compare($e1_op2, $e2_op1) ? 1 : 0;
                     elseif ($e1_op2_iscblock && $e2_op1_iscblock)
                         $compares = block_compare($e1_op2, $e2_op1, $compares);
                     elseif ($e1_op2_isublock && $e2_op2_isublock)
                         $compares = unary_func_compare($e1_op2, $e2_op1, $compares);
                     elseif($e1_op2_isval && $e2_op1_isval)
                         $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op1[$e2_op1_min]["value"]) ? 1 : 0;
                     else
                         $compares[] = 0;

                }
                elseif ($e1_op1_isublock && $e2_op1_isublock &&
                        !in_array(0, unary_func_compare($e1_op1, $e2_op1, $interm_compares))) {

                    $compares[] = 1;
                    $interm_compares = [];

                    if ($e1_op2_issblock && $e2_op2_issblock)
                        $compares[] = simple_compare($e1_op2, $e2_op2) ? 1 : 0;
                    elseif ($e1_op2_iscblock && $e2_op2_iscblock)
                        $compares = block_compare($e1_op2, $e2_op2, $compares);
                    elseif ($e1_op2_isublock && $e2_op2_isublock)
                        $compares = unary_func_compare($e1_op2, $e2_op2, $compares);
                    elseif($e1_op2_isval && $e2_op2_isval)
                        $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op2[$e2_op2_min]["value"]) ? 1 : 0;
                    else
                        $compares[] = 0;

                }
                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*") &&
                        $e1_op1_iscblock && $e2_op2_iscblock &&
                        !in_array(unary_func_compare($e1_op1, $e2_op2, $interm_compares))) {

                    $compares[] = 1;
                    $interm_compares = [];

                    if ($e1_op2_issblock && $e2_op1_issblock)
                        $compares[] = simple_compare($e1_op2, $e2_op1) ? 1 : 0;
                    elseif ($e1_op2_iscblock && $e2_op1_iscblock)
                        $compares = block_compare($e1_op2, $e2_op1, $compares);
                    elseif ($e1_op2_isublock && $e2_op2_isublock)
                        $compares = unary_func_compare($e1_op2, $e2_op1, $compares);
                    elseif($e1_op2_isval && $e2_op1_isval)
                        $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op1[$e2_op1_min]["value"]) ? 1 : 0;
                    else
                        $compares[] = 0;

                }
                elseif ($e1_op1_isval && $e2_op1_isval &&
                        $e1_op1[$e1_op1_min]["value"] == $e2_op1[$e2_op1_min]["value"]) {

                    $compares[] = 1;

                    if ($e1_op2_issblock && $e2_op2_issblock)
                        $compares[] = simple_compare($e1_op2, $e2_op2) ? 1 : 0;
                    elseif ($e1_op2_iscblock && $e2_op2_iscblock)
                        $compares = block_compare($e1_op2, $e2_op2, $compares);
                    elseif ($e1_op2_isublock && $e2_op2_isublock)
                        $compares = unary_func_compare($e1_op2, $e2_op2, $compares);
                    elseif($e1_op2_isval && $e2_op2_isval)
                        $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op2[$e2_op2_min]["value"]) ? 1 : 0;
                    else
                        $compares[] = 0;

                }

                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*")
                         && $e1_op1_isval && $e2_op2_isval &&
                         $e1_op1[$e1_op1_min]["value"] == $e2_op2[$e2_op2_min]["value"]) {

                     $compares[] = 1;

                     if ($e1_op2_issblock && $e2_op1_issblock)
                         $compares[] = simple_compare($e1_op2, $e2_op1) ? 1 : 0;
                     elseif ($e1_op2_iscblock && $e2_op1_iscblock)
                         $compares = block_compare($e1_op2, $e2_op1, $compares);
                     elseif ($e1_op2_isublock && $e2_op2_isublock)
                         $compares = unary_func_compare($e1_op2, $e2_op1, $compares);
                     elseif($e1_op2_isval && $e2_op1_isval)
                         $compares[] = ($e1_op2[$e1_op2_min]["value"] == $e2_op1[$e2_op1_min]["value"]) ? 1 : 0;
                     else
                         $compares[] = 0;

                }

                else {
                    $compares[] = 0;
                }

            }
        }
        return $compares;

    }

    // compare unary functions
    function unary_func_compare($e1, $e2, $compares) {

        $e1_min = min(array_keys($e1));
        $e2_min = min(array_keys($e2));

        $e1_function = $e1[$e1_min];
        $e2_function = $e2[$e2_min];

        $e1_op = newSlice($e1, $e1[$e1_min]["op_ndx"], NULL, true);
        $e2_op = newSlice($e2, $e2[$e2_min]["op_ndx"], NULL, true);

        $e1_op_min = min(array_keys($e1_op));
        $e2_op_min = min(array_keys($e2_op));

        if ($e1_function["name"] != $e2_function["name"])
            $compares[] = 0;

        else {

            if (count($e1_op) == 1 && count($e2_op) == 1)
                $compares[] = ($e1_op[$e1_op_min]["value"] == $e2_op[$e2_op_min]["value"]) ? 1 : 0;
            elseif (count($e1_op) == 3 && count($e2_op) == 3)
                $compares[] = simple_compare($e1_op, $e2_op) ? 1 : 0;
            elseif (count($e1_op) == count($e2_op) && count($e1_op) > 3)
                $compares = block_compare($e1_op, $e2_op, $compares);
            else
                $compares[] = 0;

        }

        return $compares;

    }

?>
