<?php

    require("new_slice.php");
    require("unary_minus.php");
    require("tokenizer.php");
    require("shunting_yard.php");
    require("compare.php");

    // always run this function first before doing anything else
    // relied upon by tokenizer and shunting_yard
    declare_globals();

?>
