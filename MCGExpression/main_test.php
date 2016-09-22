#!/usr/bin/env php
<?php

    require("new_slice.php");
    require("unary_minus.php");
    require("tokenizer.php");
    require("shunting_yard.php");
    require("compare.php");

    $input1 = "-3LOG(4-2PI)-SQRT(2-4PI)";
    $input2 = "-SQRT(2-3PI)-3LOG(-2PI+4)";

    // always run this function first before doing anything else
    // relied upon by tokenizer and shunting_yard
    declare_globals();

    $tok_meta1 = tokenize($input1);
    $tok_meta2 = tokenize($input2);
    echo compare(shunting_yard($tok_meta1[0], $tok_meta1[1]),
                 shunting_yard($tok_meta2[0], $tok_meta2[1])) ? "true" : "false";

?>
