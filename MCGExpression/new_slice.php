<?php

    // PHP's default array_slice doesn't support arrays without zero index.
    // Therefore, this is a new function, written in PHP, to add this functionality.
    // Assume keys are all numerical, consecutive, and in increasing order.
    function new_slice($input_arr, $offset, $length = NULL, $preserve_keys = false) {

        // error checking for $offset
        if (!isset($input_arr[$offset]))
            throw new \InvalidArgumentException(sprintf("Offset %d doesn't exist in array.", $offset));

        else {

            $output_arr = [];

            // if length is set, we will iterate through ($offset + $length)
            if (isset($length)) {
                // if $preserve_keys is set to true, $output_arr is treated like
                // an associative array with numerical keys
                if ($preserve_keys) {
                    for ($cur = $offset, $end = $offset + $length; $cur < $end; $cur++) {
                        $output_arr[$cur] = $input_arr[$cur];
                    }
                }
                // otherwise the keys are ignored and elements are simply added
                // to $output_arr
                else {
                    for ($cur = $offset, $end = $offset + $length; $cur < $end; $cur++) {
                        $output_arr[] = $input_arr[$cur];
                    }
                }
            }
            // same as above, but length is not set so we iterate through the
            // end of the array
            else {
                if ($preserve_keys) {
                    for ($cur = $offset, $end = max(array_keys($input_arr)) + 1; $cur < $end; $cur++) {
                        $output_arr[$cur] = $input_arr[$cur];
                    }
                }
                else {
                    for ($cur = $offset, $end = max(array_keys($input_arr)) + 1; $cur < $end; $cur++) {
                        $output_arr[] = $input_arr[$cur];
                    }
                }
            }
            return $output_arr;
        }
    }

?>
