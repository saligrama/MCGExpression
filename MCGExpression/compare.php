<?php

    function compare($arr1, $arr2) {

        if ($arr1 == $arr2)
            return true;

        // TODO smart count with unary minus support

        else {

            $compares = [];
            $compares = block_compare($arr1, $arr2, $compares);
            return !in_array(0, $compares);

        }

    }
    // compare simplest order operations => $e1, $e2 each have one unary operator and two constant/numerical operands
    function simple_compare($e1, $e2) {

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
