# MCGExpression
This is a PHP expression parser and comparison tool, written to grade MathCounts answers for http://github.com/saligrama/mathcountsgrading

Setup
-----

Clone the repository.
```bash
$ git clone http://github.com/saligrama/MCGExpression
```

Usage
-----

In `MCGExpression/main.php`, you'll find these contents.
```php
#!/usr/bin/env php
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
```
In this state, the system does nothing. You can add something like this to the end.

```php
$expr1 = "-3-7SQRT(8PI-1)";
$expr2 = "-7SQRT(-1+8PI)-3";

$e1_tok = tokenize($expr1);
$e2_tok = tokenize($expr2);

echo compare(
    shunting_yard($e1_tok[0], $e1_tok[1]),
    shunting_yard($e2_tok[0], $e2_tok[1])
);
```

Run the program:

```bash
$ ./main.php
```

The result from the code above:

    1

You can change the values for `$expr1` and `$expr2` and play around with the system.

Constants and functions can be entered as below:

| Constant/function | Enter as |
| ----------------- | -------- |
| π | `PI` |
| √ | `SQRT` |
| e | `E` |
| i | `I` |
| sin, cos, tan, and other functions | Uppercased text representation |

For more details, please view the `declare_globals()` function in `MCGExpression/tokenizer.php`.
