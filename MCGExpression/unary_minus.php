<?php

    function fix_minus($input) {

        // get total minus count
        $total_minus_count = get_total_minus_count($input);

        // if there are no minuses return input without modifications
        if ($total_minus_count == 0)
            return $input;

        // get count of fixed minuses
        $fixed_minus_count = get_fixed_minus_count($input);

        // while there are unfixed minus go through and correct them
        while ($fixed_minus_count < $total_minus_count) {

            // get first unfixed minus
            $pos = get_unfixed_minus($input);
            if ($pos !== null) {

                // add plus before the minus if unary minus is not at beginning
                if (add_plus($input, $pos))
                    array_splice($input, $pos, 0, '+');

                // add lparen after minus
                array_splice($input, $pos + (!(add_plus($input, $pos)) ? 1 : 2), 0, '(');

                // complete the rparen
                $input = complete_minus_parens($input, $pos + (!add_plus($input, $pos) ? 2 : 3));

            }
            $fixed_minus_count = get_fixed_minus_count($input);

        }

        return $input;

    }

    // minus is fixed once there is an lparen after it
    // matching rparen is always added before function is run
    function is_fixed($input, $pos) { return ($input[$pos] == '-' && $input[$pos + 1] == '('); }

    // get first unfixed minus in array for correction
    function get_unfixed_minus($input) {
        for ($i = 0, $c = count($input); $i < $c; $i++) {
            if ($input[$i] == '-' && !is_fixed($input, $i)) {
                return $i;
            }
        }
        return null;
    }

    // get number of fixed minuses
    function get_fixed_minus_count($input) {
        $fixed_minus_count = 0;
        for ($i = 0, $c = count($input); $i < $c; $i++) {
            $fixed_minus_count += is_fixed($input, $i) ? 1 : 0;
        }
        return $fixed_minus_count;
    }

    // get total number of minuses
    // PHP builtin array_count_values throws errors if there are no minuses,
    // so we build this simple function to do it
    function get_total_minus_count($input) {
        $total_minus_count = 0;
        for ($i = 0, $c = count($input); $i < $c; $i++) {
            $total_minus_count += ($input[$i] == '-') ? 1 : 0;
        }
        return $total_minus_count;
    }

    // check if a plus should be added before a minus
    // do not add a plus if the minus is the first thing in the array
    // or if it is the first thing after a left parenthesis
    // or if it is the first thing after a lower precedence operator
    function add_plus($input, $pos) {
        return !($pos == 0 ||
                 (isset($input[$pos-1]) && ($input[$pos-1] == '(' ||
                  isset($GLOBALS['operators'][$input[$pos-1]]) &&
                  $GLOBALS['operators'][$input[$pos-1]]['precedence'] < 0)));
    }

    // inject rparen to complete minus operand wrapping
    function complete_minus_parens($input, $op_start) {
        $pos = $op_start;
        // track open parens
        $open_paren_count = 0;
        // track if parens were ever open (for case of minuses in parenthesis)
        $ever_open_paren = false;
        for ($i = $pos, $c = count($input); $i < $c; $i++) {
            // if no parens are open and we hit something with <= precedence as minus
            // complete the rparen
            if ($open_paren_count == 0 && isset($GLOBALS['operators'][$input[$i]]) &&
                $GLOBALS['operators'][$input[$i]]['precedence'] <= 1) {
                array_splice($input, $i, 0, ')');
                break;
            }
            // if we hit the end of the array, complete the rparen
            elseif ($i == max(array_keys($input))) {
                array_splice($input, $i+1, 0, ')');
                break;
            }
            // note if any parens were open
            elseif ($input[$i] == '(') {
                $open_paren_count++;
                $ever_open_paren = true;
            }
            // if we hit an rparen there are two ways to insert the rparen
            elseif($input[$i] == ')') {
                // if there was never a paren open insert the minus rparen
                // before rparen at $i
                if (!$ever_open_paren) {
                    array_splice($input, $i, 0, ')');
                    break;
                }
                // mark that any open paren has been closed
                $open_paren_count--;
                // if an rparen has been closed, insert the minus rparen
                // after rparen at $i
                if ($open_paren_count == 0) {
                    array_splice($input, $i+1, 0, ')');
                    break;
                }
            }
        }

        return $input;

    }

?>
