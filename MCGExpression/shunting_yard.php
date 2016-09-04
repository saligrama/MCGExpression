<?php

    // modified Shunting-Yard algorithm with support for prefix notation,
    // storing of operand locations, and unary functions
    // see https://en.wikipedia.org/wiki/Shunting-yard_algorithm
    function shunting_yard(array $tokens, $length) {

        $stack = new \SplStack();
        $output = new \SplQueue();

        // iterate through tokens in reverse order to convert to prefix (Polish) notation
        foreach (array_reverse($tokens) as $token) {
            // if token is an operand (number or special constant) add to output queue
            if (is_numeric($token) ||
                preg_match("/" . $GLOBALS["format_regexes"]["special"] . "/", $token)) {
                $output->enqueue([
                    'type' => 'operand',
                    'value' => $token
                ]);
                $last_popped_lparen = 0;
            }
            // if we have an operator push it to the stack while moving existing
            // operators on the stack to output queue
            elseif (isset($GLOBALS["operators"][$token])) {
                $o1 = $token;
                while (has_operator($stack) && ($o2 = $stack->top()) && has_lower_precedence($o1, $o2)) {
                    $c = count($output);
                    $output->enqueue([
                        'type' => 'operator',
                        'symbol' => $stack->pop(),
                        'op1_ndx' => $length - $c, // store metadata for easy comparison
                        'op2_ndx' => getoperand_base($output, $c)
                    ]);
                    $last_popped_lparen = 0;
                }
                $stack->push($o1);
            }
            elseif (')' === $token) {
                $stack->push($token); // wait untill we see an lparen
            }
            elseif ('(' === $token) {
                // if something is on the stack it is either an rparen or an operator
                // we now move the operators to the output queue, again storing metadata
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
            }
            // if it's a unary function (including minus) and the last argument
            // found was an lparen we directly move it to the stack while storing metadata
            elseif (preg_match("/" . preg_replace("/(\))/", "|-$1", $GLOBALS["format_regexes"]["function"]) . "/", $token)) {
                if ($last_popped_lparen || $stack->top() == ')') {
                    $c = count($output);
                    $output->enqueue([
                        'type' => 'function',
                        'name' => $token,
                        'op_ndx' => $c - 1
                    ]);
                }
                else {
                    throw new \InvalidArgumentException(sprintf('Function %s does not have parenthesis', $token));
                }
            }
            else {
                throw new \InvalidArgumentException(sprintf('Invalid token: %s', $token));
            }
        }
        // add to queue any last operators remaining in the stack
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
        // reverse the queue to make it normal prefix notation
        $pn = array_reverse(iterator_to_array($output));

        // fix all operand locations afer reverse
        // we couldn't do this above since finding operands relies on locations
        // set in the existing queue
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

    // check if there's an operator on the top of the stack
    function has_operator(\SplStack $stack) {
        return count($stack) > 0 && ($top = $stack->top()) && isset($GLOBALS["operators"][$top]);
    }

    // check if precedence of $o1 is less than that of $o2
    function has_lower_precedence($o1, $o2) {
        $op1 = $GLOBALS["operators"][$o1];
        $op2 = $GLOBALS["operators"][$o2];
        return ($op1['associativity'] === 'left'
                && $op1['precedence'] === $op2['precedence'])
                || $op1['precedence'] < $op2['precedence'];
    }


    // finds operands for an operator
    function getoperand_base(\SplQueue $output, $ndx) {

        // if the next item in queue is an operator, we find the end of its second operand
        if ($output[$ndx - 1]["type"] == "operator")
            return end_of_opchain($output, $output[$ndx - 1]["op2_ndx"]) - 1;
        // if it is a unary function, find the end of its other operand
        elseif ($output[$ndx - 1]["type"] == "function")
            return end_of_opchain($output, $output[$ndx - 1]["op_ndx"]) - 1;
        // otherwise if it is a value, return the next item after that value
        else
            return $ndx - 2;

    }

    // iterate until we find a number or special as an operator index (unary function)
    // or the second index of operand (for an operator)
    function end_of_opchain(\SplQueue $output, $ndx) {

        if ($output[$ndx]["type"] == "operator")
            return end_of_opchain($output, $output[$ndx]["op2_ndx"]);
        elseif($output[$ndx]["type"] == "function")
            return end_of_opchain($output, $output[$ndx]["op_ndx"]);
        else
            return $ndx;

    }


?>
