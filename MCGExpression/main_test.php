#!/usr/bin/env php
<?php

    require("newslice.php");
    require("unary_minus.php");
    require("tokenizer.php");
    require("shunting_yard.php");
    require("compare.php");

    $input1 = "4 - 2PI";
    $input2 = "-2PI + 4";

    // always run this function first before doing anything else
    // relied upon by tokenizer and shunting_yard
    declare_globals();

    $tok_meta1 = tokenize($input1);
    $tok_meta2 = tokenize($input2);
    echo compare(shunting_yard($tok_meta1[0], $tok_meta1[1]), shunting_yard($tok_meta2[0], $tok_meta2[1])) ? "t" : "f";

?>
