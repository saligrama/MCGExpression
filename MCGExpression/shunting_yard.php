<?php

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


?>
