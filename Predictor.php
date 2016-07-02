<?php
/**
 * Predictor v 1.0.0
 * Sergey Ostapchik 2016
 * Public Profile https://www.linkedin.com/in/sergoman
 */
class Predictor {

    public $limit = 100;
    public $knowledge = array();
    public $steps = array();
    private $_flat_steps = array();
    public $variants = array();
    public $decisions = array();
    public $preffix = '';
    public $fastLearn = true;


    public function __construct($steps, $variants, $preffix = '')
    {
        $this->variants = $variants;
        $this->preffix = $preffix;
        $this->steps = $steps;
        $this->load();
        $this->_flatterizeSteps();
    }

    /**
     * Загружаем зснания, если знаний нет - генерируем
     * @return $this
     */
    public function load()
    {
        if( ! count($this->knowledge)) {
            $knowledgeFile = 'knowledges/'.$this->preffix."_".count($this->variants)."_".count($this->steps).'.dat';
            if(file_exists($knowledgeFile)) {
                $this->knowledge = unserialize(file_get_contents($knowledgeFile));
                return $this;
            }

            foreach($this->variants as $variant) {
                for($x = 0; $x < count($this->steps);$x++) {
                    foreach($this->variants as $xVar) {
                        $this->knowledge[$variant][$x][$xVar] = 0;
                    }
                }
            }

            $this->save();
        }
        return $this;
    }

    /**
     * Сохраняем знания сети
     *
     * @return $this
     */
    public function save()
    {
        //Вместо использования файлов можно сохранять знания в базу данных
        $data = serialize($this->knowledge);
        $knowledgeFile = 'knowledges/'.$this->preffix."_".count($this->variants)."_".count($this->steps).'.dat';
        file_put_contents($knowledgeFile,$data);
        return $this;
    }

    /**
     * Возвращает один лучший вариант
     * @return mixed
     */
    public function getBestDecision()
    {
        $max = array('sum'=>0,'variant'=>'');
        foreach ($this->decisions as $decision) {
            if($max['sum'] < $decision['sum']) {
                $max = $decision;
            }
        }
        return $max['variant'];
    }

    /**
     * Запомнить решение
     * @param      $decision
     * @param bool $forgetFlag
     *
     * @return $this|Predictor
     */
    public function learn($decision, $forgetFlag = false)
    {
        $best = 0;
        if($forgetFlag) {
            $best = $this->decide(true);
        }

        //Забываем неверное решение
        if($best && $best != $decision) {
            $this->forget($best);
        }

        for($x = 0; $x < count($this->steps);$x++) {
            foreach($this->variants as $xVar) {
                //Повышаем вес
                $this->knowledge[$decision][$x][$xVar] += (float)$this->_flat_steps[$x][$xVar] / 2;
            }
        }

        //ускоренное обучение
        if($this->fastLearn) {
            $best = $this->decide(true);
        }
        if($this->fastLearn && $best != $decision) {
            return $this->learn($decision,$forgetFlag);
        }

        $this->save();
        return $this;
    }

    /**
     * Забыть решение
     * @param $decision
     */
    public function forget($decision)
    {
        for($x = 0; $x < count($this->steps);$x++) {
            foreach($this->variants as $xVar) {
                $this->knowledge[$decision][$x][$xVar] -= (float)$this->_flat_steps[$x][$xVar] / 4;
            }
        }
    }

    /**
     * Решить, какой вариант подходит к входным данным
     * @param bool $limit
     *
     * @return mixed
     */
    public function decide($limit = false)
    {
        $this->decisions = array();

        foreach($this->variants as $variant) {
            $sum = 0;
            for($x = 0; $x < count($this->steps);$x++) {
                foreach($this->variants as $xVar) {
                    $sum += (float)$this->knowledge[$variant][$x][$xVar] * $this->_flat_steps[$x][$xVar];
                }
            }

            if($limit) {
                //Проверка. Если сумма весов превышает пороговое значение.
                //Можно использовать доп. функцию для большей гибкости
                if($sum > $this->limit) {
                    $this->decisions[] = array('sum'=>$sum,'variant'=>$variant);
                }
                continue;
            }
            $this->decisions[] = array('sum'=>$sum,'variant'=>$variant);
        }
        //Возвращаем один наилучший вариант
        return $this->getBestDecision();
    }


    /**
     * Преобразование входных данных
     *
     */
    protected function _flatterizeSteps()
    {
        $this->_flat_steps = array();
        foreach($this->steps as $step) {
            $this->_flat_steps[] = $this->_flatterizeOneStep($step);
        }
    }

    /**
     * Преобразование 1 единицы входных данных
     * преобразование 1 из N
     * @param $step
     *
     * @return array
     */
    protected function _flatterizeOneStep($step)
    {
        $data = array();
        foreach($this->variants as $variant) {
            if($step == $variant) {
                $data[$variant] = 1;
            } else {
                $data[$variant] = 0;
            }
        }
        return $data;
    }

    /**
     * @param $steps
     *
     * @return $this
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;
        $this->_flatterizeSteps();
        return $this;
    }
}