<?php

    function compare($arr1, $arr2) {

        // immediately return true if arrays are the same
        if ($arr1 == $arr2)
            return true;

        elseif (count($arr1) != count($arr2))
            return false;

        else {

            // otherwise run usual block_compare
            $compares = [];
            $compares = block_compare($arr1, $arr2, $compares);
            return !in_array(0, $compares);

        }

    }

    // compare simplest order operation
    // $e1, $e2 each have one unary operator and two special constant/numerical operands
    function simple_compare($e1, $e2) {

        // get first item in array
        $e1_min = min(array_keys($e1));
        $e2_min = min(array_keys($e2));

        if ($e1[$e1_min]["symbol"] == $e2[$e2_min]["symbol"]) {
            // compare linearly (first op1 and second op1, then same with op2)
            if ($e1[$e1_min + 1]["value"] == $e2[$e2_min + 1]["value"] &&
                $e1[$e1_min + 2]["value"] == $e2[$e2_min + 2]["value"]) {
                return true;
            }
            // cross compare (first op2 and second op1 and converse) if commutative operator
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

        // minimum key in array
        $e1_min = min(array_keys($e1));
        $e2_min = min(array_keys($e2));

        // if lengths are different they are not the same
        if (count($e1) != count($e2)) {
            $compares[] = 0;
            return $compares;
        }
        // if each block is a unary function block only, go to unary_func_compare
        elseif ($e1[$e1_min]["type"] == "function" && $e2[$e2_min]["type"] == "function") {
            $compares = unary_func_compare($e1, $e2, $compares);
            return $compares;
        }
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

            $e1_op1_min = min(array_keys($e1_op1));
            $e1_op2_min = min(array_keys($e1_op2));
            $e2_op1_min = min(array_keys($e2_op1));
            $e2_op2_min = min(array_keys($e2_op2));

            // simple block, for comparison at simple_compare
            $e1_op1_issblock = (count($e1_op1) == 3) ? 1 : 0;
            $e1_op2_issblock = (count($e1_op2) == 3) ? 1 : 0;
            $e2_op1_issblock = (count($e2_op1) == 3) ? 1 : 0;
            $e2_op2_issblock = (count($e2_op2) == 3) ? 1 : 0;

            // complex block, should be returned to block_compare
            $e1_op1_iscblock = (count($e1_op1) > 3) ? 1 : 0;
            $e1_op2_iscblock = (count($e1_op2) > 3) ? 1 : 0;
            $e2_op1_iscblock = (count($e2_op1) > 3) ? 1 : 0;
            $e2_op2_iscblock = (count($e2_op2) > 3) ? 1 : 0;

            // numerical/special value only, can be compared straightaway
            $e1_op1_isval = (count($e1_op1) == 1) ? 1 : 0;
            $e1_op2_isval = (count($e1_op2) == 1) ? 1 : 0;
            $e2_op1_isval = (count($e2_op1) == 1) ? 1 : 0;
            $e2_op2_isval = (count($e2_op2) == 1) ? 1 : 0;

            // unary function only, for unary_func_compare
            $e1_op1_isublock = ($e1_op1[$e1_op1_min]["type"] == "function") ? 1 : 0;
            $e1_op2_isublock = ($e1_op2[$e1_op2_min]["type"] == "function") ? 1 : 0;
            $e2_op1_isublock = ($e2_op1[$e2_op1_min]["type"] == "function") ? 1 : 0;
            $e2_op2_isublock = ($e2_op2[$e2_op2_min]["type"] == "function") ? 1 : 0;

            if ($e1_operator["symbol"] != $e2_operator["symbol"])
                $compares[] = 0;

            else {

                // if we need to compare intermediately for purposes of
                // mutual exclusivity, we have an array without tainting $compares
                $interm_compares = [];

                if ($e1_op1_issblock && $e2_op1_issblock && simple_compare($e1_op1, $e2_op1)) {

                    $compares[] = 1;
                    $compares = template_compare($e1_op2, $e2_op2, $compares);


                }

                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*")
                         && $e1_op1_issblock && $e2_op2_issblock && simple_compare($e1_op1, $e2_op2)) {

                    $compares[] = 1;
                    $compares = template_compare($e1_op2, $e2_op1, $compares);


                }
                elseif ($e1_op1_iscblock && $e2_op1_iscblock &&
                        !in_array(0, block_compare($e1_op1, $e2_op1, $interm_compares))) {

                    $compares[] = 1;
                    $interm_compares = [];
                    $compares = template_compare($e1_op2, $e2_op2, $compares);


                }
                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*") &&
                         $e1_op1_iscblock && $e2_op2_iscblock &&
                         !in_array(0, block_compare($e1_op1, $e2_op2, $interm_compares))) {

                     $compares[] = 1;
                     $interm_compares = [];
                     $compares = template_compare($e1_op2, $e2_op1, $compares);


                }
                elseif ($e1_op1_isublock && $e2_op1_isublock &&
                        !in_array(0, unary_func_compare($e1_op1, $e2_op1, $interm_compares))) {

                    $compares[] = 1;
                    $interm_compares = [];
                    $compares = template_compare($e1_op2, $e2_op2, $compares);


                }
                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*") &&
                        $e1_op1_isublock && $e2_op2_isublock &&
                        !in_array(0, unary_func_compare($e1_op1, $e2_op2, $interm_compares))) {

                    $compares[] = 1;
                    $interm_compares = [];
                    $compares = template_compare($e1_op2, $e2_op1, $compares);


                }
                elseif ($e1_op1_isval && $e2_op1_isval &&
                        $e1_op1[$e1_op1_min]["value"] == $e2_op1[$e2_op1_min]["value"]) {

                    $compares[] = 1;
                    $compares = template_compare($e1_op2, $e2_op2, $compares);


                }
                elseif (($e1_operator["symbol"] == "+" || $e1_operator["symbol"] == "*")
                         && $e1_op1_isval && $e2_op2_isval &&
                         $e1_op1[$e1_op1_min]["value"] == $e2_op2[$e2_op2_min]["value"]) {

                    $compares[] = 1;
                    $compares = template_compare($e1_op2, $e2_op1, $compares);
                }

                else {
                    $compares[] = 0;
                }
                return $compares;
            }
        }
    }

    // compare unary functions
    function unary_func_compare($e1, $e2, $compares) {

        // find minimum value in numerical array keys
        $e1_min = min(array_keys($e1));
        $e2_min = min(array_keys($e2));

        // get function name
        $e1_function = $e1[$e1_min];
        $e2_function = $e2[$e2_min];

        // get operands
        $e1_op = newSlice($e1, $e1[$e1_min]["op_ndx"], NULL, true);
        $e2_op = newSlice($e2, $e2[$e2_min]["op_ndx"], NULL, true);

        $e1_op_min = min(array_keys($e1_op));
        $e2_op_min = min(array_keys($e2_op));

        if ($e1_function["name"] != $e2_function["name"]) {
            $compares[] = 0;
        }

        else
            $compares = template_compare($e1_op, $e2_op, $compares);

        return $compares;

    }

    function template_compare($b1, $b2, $compares) {

        $b1_min = min(array_keys($b1));
        $b2_min = min(array_keys($b2));

        // simple block
        $b1_issblock = (count($b1) == 3) ? 1 : 0;
        $b2_issblock = (count($b2) == 3) ? 1 : 0;

        // complex block with unary functions
        $b1_iscblock = (count($b1) > 3) ? 1 : 0;
        $b2_iscblock = (count($b2) > 3) ? 1 : 0;

        // numerical/special value only
        $b1_isval = (count($b1) == 1) ? 1 : 0;
        $b2_isval = (count($b2) == 1) ? 1 : 0;

        // unary function only
        $b1_isublock = ($b1[$b1_min]["type"] == "function") ? 1 : 0;
        $b2_isublock = ($b2[$b2_min]["type"] == "function") ? 1 : 0;

        // if simple block return to simple_compare
        if ($b1_issblock && $b2_issblock)
            $compares[] = simple_compare($b1, $b2) ? 1 : 0;
        // if complex block feed to block_compare
        elseif ($b1_iscblock && $b2_iscblock)
            $compares = block_compare($b1, $b2, $compares);
        // if unary function only block go to unary_func_compare
        elseif ($b1_isublock && $b2_isublock)
            $compares = unary_func_compare($b1, $b2, $compares);
        // if single value compare it right here
        elseif($b1_isval && $b2_isval)
            $compares[] = ($b1[$b1_min]["value"] == $b2[$b2_min]["value"]) ? 1 : 0;
        else
            $compares[] = 0;

        return $compares;

    }

?>
