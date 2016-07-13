<?php
/**
 * Predictor v 1.1.4
 * Sergey Ostapchik 2016
 * Public Profile https://www.linkedin.com/in/sergoman
 */
class Predictor {

    public $limit = 100;
    public $minLimit = 50;
    public $knowledge = null;
    public $steps = array();
    private $_flat_steps = array();
    public $variants = array();
    public $decisions = array();
    public $preffix = '';
    public $fastLearn = true;
    public $useART = true;

    public function __construct($steps, $variants, $preffix = '')
    {
        $this->variants = $variants;
        $this->preffix = $preffix;
        $this->steps = $steps;
        $this->knowledge = new StdClass();
        $this->knowledge->data = array();
        $this->knowledge->variants = array();
        $this->load();
        $this->_flatterizeSteps();
    }

    /**
     * Загружаем зснания, если знаний нет - генерируем
     * @return $this
     */
    public function load()
    {
        if(count($this->knowledge->variants) != count($this->variants)) {
            $knowledgeFile = 'knowledges/'.$this->preffix."_".count($this->variants)."_".count($this->steps).'.json';
            if(file_exists($knowledgeFile)) {
                $this->knowledge = json_decode(file_get_contents($knowledgeFile));
                return $this;
            }

            foreach($this->variants as $key => $variant) {
                $this->addVariant($variant);
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
        $data = json_encode($this->knowledge);
        $knowledgeFile = 'knowledges/'.$this->preffix."_".count($this->variants)."_".count($this->steps).'.json';
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

    public function addVariant($variant)
    {
        $this->knowledge->variants[] = $variant;
        $key = array_search($variant,$this->knowledge->variants);
        for($x = 0; $x < count($this->steps);$x++) {
            foreach($this->variants as $keyVar => $xVar) {
                $this->knowledge->data[$key][$x][$keyVar] = rand(10,50)/100;
            }
        }
        return $key;
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
        $keyVar = array_search($decision,$this->knowledge->variants);
        if($keyVar === false) {
            $keyVar = $this->addVariant($decision);
            $this->_flatterizeSteps();
        }

        if($this->useART) {
            $tmpSteps = $this->_flat_steps;
            foreach($this->knowledge->variants as $key_dec => $dec) {
                if($dec == $decision) {
                    continue;
                }
                for ($x = 0; $x < count($this->steps); $x++) {
                    foreach ($this->variants as $key => $xVar) {
                        if($this->knowledge->data[$key_dec][$x][$key] > 5) {
                            $this->_flat_steps[$x][$key] = round((float)$this->_flat_steps[$x][$key] / 1.1,2);
                        } else {
                            $this->_flat_steps[$x][$key] = round((float)$this->_flat_steps[$x][$key] / 0.8,2);
                        }
                    }
                }
            }
        }

        for($x = 0; $x < count($this->steps);$x++) {
            foreach($this->variants as $key => $xVar) {
                //Повышаем вес
                $this->knowledge->data[$keyVar][$x][$key] += (float)$this->_flat_steps[$x][$key] / 2;
            }
        }

        if($this->useART) {
             $this->_flat_steps = $tmpSteps;
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
        $keyVar = array_search($decision,$this->knowledge->variants);
        if($keyVar === false) {
            $keyVar = $this->addVariant($decision);
        }

        if($this->useART) {
            $tmpSteps = $this->_flat_steps;
            foreach($this->knowledge->variants as $key_dec => $dec) {
                if($dec == $decision) {
                    continue;
                }
                for ($x = 0; $x < count($this->steps); $x++) {
                    foreach ($this->variants as $key => $xVar) {
                        if($this->knowledge->data[$key_dec][$x][$key] > 5) {
                            $this->_flat_steps[$x][$key] = round((float)$this->_flat_steps[$x][$key] / 1.1,2);
                        } else {
                            $this->_flat_steps[$x][$key] = round((float)$this->_flat_steps[$x][$key] / 0.8,2);
                        }
                    }
                }
            }
        }

        for($x = 0; $x < count($this->steps);$x++) {
            foreach($this->variants as $key => $xVar) {
                $this->knowledge->data[$keyVar][$x][$key] -= (float)$this->_flat_steps[$x][$key] / 4;
            }
        }

        if($this->useART) {
            $this->_flat_steps = $tmpSteps;
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

        foreach($this->knowledge->variants as $keyVar => $variant) {
            $sum = 0;
            for($x = 0; $x < count($this->steps);$x++) {
                foreach($this->variants as $key => $xVar) {
                    $sum += (float)$this->knowledge->data[$keyVar][$x][$key] * $this->_flat_steps[$x][$key];
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
            if($sum > $this->minLimit) {
                $this->decisions[] = array('sum'=>$sum,'variant'=>$variant);
            }
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
        foreach($this->variants as $keyVar => $variant) {
            if($step == $variant) {
                $data[$keyVar] = 1;
            } else {
                $data[$keyVar] = 0;
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