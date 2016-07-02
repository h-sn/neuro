/**
 * Predictor v 1.0.0
 * Sergey Ostapchik 2016
 * Public Profile https://www.linkedin.com/in/sergoman
 * @type {{limit: number, knowledge: Array, steps: Array, _flat_steps: Array, variants: Array, decisions: Array, preffix: string, fastLearn: boolean, init: Predictor.init, load: Predictor.load, save: Predictor.save, decide: Predictor.decide, getBestDecision: Predictor.getBestDecision, learn: Predictor.learn, forget: Predictor.forget, addStep: Predictor.addStep, _flatterizeSteps: Predictor._flatterizeSteps, _flatterizeOneStep: Predictor._flatterizeOneStep}}
 */
Predictor = {
    limit: 100,
    knowledge: [],
    steps: [],
    _flat_steps: [],
    variants: [],
    decisions: [],
    preffix: '',
    fastLearn: false,
    init: function(steps, variants, preffix) {
        this.steps = steps;
        this.variants = variants;
        this.preffix = preffix;
        this.load();
    },
    load: function() {
        if( ! this.knowledge.length) {

            try {
                this.knowledge = JSON.parse(localStorage.getItem(this.preffix+"_"+this.variants.length+'_'+this.steps.length));
            } catch (e) {
                this.knowledge = [];
            }

            if(this.knowledge === null) {
                this.knowledge = [];
            }

            if( ! this.knowledge.length) {
                for(var i = 0; i < this.variants.length; i++) {
                    this.knowledge[this.variants[i]] = [];
                    for(var x = 0; x < this.steps.length; x ++) {
                        this.knowledge[this.variants[i]][x] = [];
                        for(var xVar = 0; xVar < this.variants.length; xVar++) {
                            this.knowledge[this.variants[i]][x][this.variants[xVar]] = 0;
                        }
                    }
                }
                this.save();
            }
        }
    },
    save: function() {
        localStorage.setItem(this.preffix+"_"+this.variants.length+'_'+this.steps.length,JSON.stringify(this.knowledge));
    },
    decide: function(limit) {
        this._flatterizeSteps();
        this.decisions = [];

        for(var i = 0; i < this.variants.length; i++) {
            var sum = 0;
            for(var x = 0; x < this.steps.length; x++) {
                for(var xVar = 0; xVar < this.variants.length; xVar++) {
                    sum += Math.round(this.knowledge[this.variants[i]][x][this.variants[xVar]] *
                        this._flat_steps[x][this.variants[xVar]]);
                }
            }

            if(limit === true) {
                if(this.limit <= sum) {
                    this.decisions[sum] = {variant: this.variants[i],sum:sum};
                }
                continue;
            }

            this.decisions[sum] = {variant: this.variants[i],sum:sum};
        }
        return this.getBestDecision();
    },
    getBestDecision: function() {
        maxDecision = {sum:0,variant:''};
        this.decisions.forEach(function(dat, sum){
            if(maxDecision.sum < sum) {
                maxDecision = dat;
            }
        });
        return maxDecision.variant;
    },
    learn: function(decision, forgetFlag) {
        var best = 0;
        if(forgetFlag) {
            best = this.decide(true);
        }

        if(best && best != decision) {
            this.forget(best);
        }

        for(var x = 0; x < this.steps.length; x++) {
            for(var xVar = 0; xVar < this.variants.length;xVar++) {
                this.knowledge[decision][x][this.variants[xVar]] += (this._flat_steps[x][this.variants[xVar]] / 2);
            }
        }

        if(this.fastLearn) {
            best = this.decide(true);
        }

        if(this.fastLearn && best != decision) {
            return this.learn(decision,forgetFlag);
        }
        this.save();
    },
    forget: function(decision) {
        for(var x = 0; x < this.steps.length; x++) {
            for(var xVar = 0; xVar < this.variants.length;xVar++) {
                this.knowledge[decision][x][this.variants[xVar]] -= (this._flat_steps[x][this.variants[xVar]] / 4);
            }
        }
    },
    addStep: function(step) {
        if(this.steps.length == this.variants.length-1) {
            this.learn(step,true);
            log('Learned step: '+step);
            this.steps = [];
            return;
        }
        log(step);
        this.steps.push(step);


        if(this.steps.length == this.variants.length-1) {
            log('Next will: '+this.decide());
        }
        },
    _flatterizeSteps: function() {
        this._flat_steps = [];
        for(var i = 0; i < this.steps.length; i++) {
            this._flat_steps.push(this._flatterizeOneStep(this.steps[i]));
        }
    },
    _flatterizeOneStep: function(step) {
        var flat = [];
        for(var k = 0; k < this.variants.length; k++) {

            if(step == this.variants[k]) {
                flat[this.variants[k]] = 1
            } else {
                flat[this.variants[k]] = 0
            }
        }
        return flat;
    }

}

function log(data) {
    document.getElementById('log').innerHTML += data+'<br>';
}