<?php

    function fix_minus($arr) {

        // iterate through until we find a minus
        $i = 0;
        $unmatched_minus = false;
        while (isset($arr[$i])) {

            if ($arr[$i] == '-' && !$unmatched_minus) {
                // add plus before the minus if unary minus is not at beginning

                if ($i != 0)
                    array_splice($arr, $i, 0, '+');

                // add lparen after minus (different for i=0 versus i>0)
                array_splice($arr, $i + (($i == 0) ? 1 : 2), 0, '(');

                // flag that we have a minus
                $unmatched_minus = true;

                $i = $i + 3;
                continue;

            }
            else {

                // if we have an unmatched minus
                if ($unmatched_minus) {

                    // if we find a plus
                    if ($arr[$i] == '+') {

                        // insert rparen before plus
                        array_splice($arr, $i, 0, ')');

                        // minus is matched
                        $unmatched_minus = false;

                        $i++;
                        continue;

                    }
                    elseif ($arr[$i] == '-') {
                        // insert rparen before minus
                        array_splice($arr, $i, 0, ')');

                        // minus is matched
                        $unmatched_minus = false;

                        // do not increment since we have to deal with this minus
                        continue;

                    }
                    elseif ($i == max(array_keys($arr))) {
                        // insert rparen after last element in array
                        array_splice($arr, $i+1, 0, ')');

                        // minus is matched
                        $unmatched_minus = false;

                        $i++;
                        continue;

                    }
                    else {
                        $i++;
                        continue;
                    }

                }
                else {
                    $i++;
                    continue;
                }

            }

        }

        return $arr;

    }

?>
