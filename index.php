<?php
include 'Predictor.php';
$variants = array('up','left','right','down');
$predictor = new Predictor(array('','',''),$variants,'test');
$predictor
    ->setSteps(array('up','up','down'));
$predictor->decide();

$predictor
    ->setSteps(array('up','up','down'))->learn('left',true);
?>
<html>
<head>
    <script type="text/javascript" src="predictor.js"></script>
    <title>Predictor</title>
</head>
<body>
<h1>Predictor</h1>
<div id="js-form">
    <script type="text/javascript">
        Predictor.init(['','',''],<?php echo json_encode($variants)?>,'test');
        Predictor.steps = [];
    </script>
    <form>
        <?php foreach($variants as $step):?>
            <button onclick="Predictor.addStep('<?=$step?>')" type="button" value="<?=$step?>"><?=$step?></button>
        <?php endforeach;?>
    </form>
    <div id="log" style="overflow-y: scroll;border:1px solid grey">

    </div>
</div>
</body>
</html>
