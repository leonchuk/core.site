<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trust base</title>
    <style>
        body { margin:10px 10px; background-color: #fefefe; }
        form { }
    </style>
</head>
<body>

<table border="1" cellspacing="0" cellpadding="10" style="border-collapse:collapse;">
    <tr>
        <td>menu</td>
    </tr>
    <tr>
        <td>content</td>
    </tr>
</table>

<?php
#dump($this->container())."<br>";

echo $this->spec('mime');
#echo $this->template('test');

#echo $this->method('test');
#dump(get_defined_vars());
echo '<br>';


dump(array(
    $space->current->have('varNameFloat','float'),
    $space->current->have('varNameStr','string'),
    $space->current->have('varNameText','text'),
    $space->current->have('varNameArray','array'),
    $space->current->have('varNameObj','object')
));
dump(array(
    $space->current->var('varNameFloat','float'),
    $space->current->var('varNameStr'),
    $space->current->var('varNameText','text'),
    $space->current->var('varNameArray','array'),
    $space->current->var('varNameObj','object')
));


#dump((bool)1);
#dump((bool)0);
#dump((bool)null);
?>

</body>
</html>
