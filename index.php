<?php
include 'Predictor.php';
$variants = array('up','left','right','down');
$predictor = new Predictor(array('','',''),$variants,'test1');
$predictor
    ->setSteps(array('up','up','down'));
$predictor->decide();

$predictor
    ->setSteps(array('up','up','down'))->learn('left',true);
?>
<html>
<head>
    <script type="text/javascript" src="predictor.js"></script>
    <title>Predictor - 1.1.2</title>
</head>
<body>
<h1>Predictor</h1>
<div id="js-form">
    <script type="text/javascript">
        var myPredictor = Predictor.init(['','',''],<?php echo json_encode($variants)?>,'test1');
        myPredictor.steps = [];
    </script>
    <form>
        <?php foreach($variants as $step):?>
            <button onclick="myPredictor.addStep('<?=$step?>')" type="button" value="<?=$step?>"><?=$step?></button>
        <?php endforeach;?>
    </form>
    <div id="log" style="height: 600px;overflow-y: scroll;border:1px solid grey">

    </div>
</div>
<br>
<footer>
    Ostapchik Sergey <br>
    Public Profile: <a href="https://www.linkedin.com/in/sergoman">https://www.linkedin.com/in/sergoman</a><br>
    Source Code: <a href="https://bitbucket.org/dvman8bit/neuro_php_js">https://bitbucket.org/dvman8bit/neuro_php_js</a>

</footer>
</body>
</html>
