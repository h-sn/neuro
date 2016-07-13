/**
 * Predictor v 1.1.4
 * Sergey Ostapchik 2016
 * Public Profile https://www.linkedin.com/in/sergoman
 * @type {{limit: number, minLimit: number, knowledge: {variants: Array, data: Array}, steps: Array, _flat_steps: Array, variants: Array, decisions: Array, preffix: string, fastLearn: boolean, useAjax: boolean, useART: boolean, init: Predictor.init, _callAjax: Predictor._callAjax, load: Predictor.load, _generateKnowledge: Predictor._generateKnowledge, save: Predictor.save, decide: Predictor.decide, getBestDecision: Predictor.getBestDecision, addVariant: Predictor.addVariant, learn: Predictor.learn, forget: Predictor.forget, addStep: Predictor.addStep, _flatterizeSteps: Predictor._flatterizeSteps, _flatterizeOneStep: Predictor._flatterizeOneStep}}
 */

var Predictor = {
    limit: 200,
    minLimit: 50,
    knowledge: {variants:[],data:[]},
    steps: [],
    _flat_steps: [],
    variants: [],
    decisions: [],
    preffix: '',
    fastLearn: false,
    useAjax: false,
    useART: true,
    init: function(steps, variants, preffix) {
        this.steps = steps;
        this.variants = variants;
        this.preffix = preffix;
        var tmp = JSON.stringify(this);
        var tmpObj = JSON.parse(tmp);
        tmpObj.load = this.load;
        tmpObj._callAjax = this._callAjax;
        tmpObj.save = this.save;
        tmpObj._generateKnowledge = this._generateKnowledge;
        tmpObj.decide = this.decide;
        tmpObj.learn = this.learn;
        tmpObj.addVariant = this.addVariant;
        tmpObj.forget = this.forget;
        tmpObj.addStep = this.addStep;
        tmpObj._flatterizeOneStep = this._flatterizeOneStep;
        tmpObj._flatterizeSteps = this._flatterizeSteps;
        tmpObj.getBestDecision = this.getBestDecision;
        tmpObj.load();
        return tmpObj;
    },
    _callAjax: function(data,success,fail) {
        var xmlhttp;
        try {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (E) {
                xmlhttp = false;
            }
        }
        if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
            xmlhttp = new XMLHttpRequest();
        }
        xmlhttp.open("POST", 'knowledge.php',true);
        xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == XMLHttpRequest.DONE) {
                if(xmlhttp.status == 200){
                    try {
                        success(xmlhttp.responseText);
                    } catch(e) {
                        fail(xmlhttp.responseText);
                    }
                }else{
                    fail(xmlhttp.responseText);
                }
            }
        };
        xmlhttp.send(data);
    },
    load: function() {
        if(this.knowledge.variants.length != this.variants.length) {
            var k_name = this.preffix+"_"+this.variants.length+'_'+this.steps.length;
            if(this.useAjax) {
                var params = 'action=load&name='+k_name;
                tmpObj = this;
                this._callAjax(params,function(response){
                    tmpObj.knowledge = JSON.parse(response);
                },tmpObj._generateKnowledge);
                return;
            }
            try {
                this.knowledge = JSON.parse(localStorage.getItem(k_name));
            } catch (e) {
                this.knowledge = {variants:[],data:[]};
            }

            if(this.knowledge === null) {
                this.knowledge = {variants:[],data:[]};
            }
            this._generateKnowledge();
        }
    },
    _generateKnowledge: function() {
        if( ! this.knowledge.variants.length) {
            this.knowledge.variants = this.variants;
            for(var i = 0; i < this.variants.length; i++) {
                this.knowledge.data[i] = [];
                this.addVariant(this.variants[i]);
            }
            this.save();
        }
    },
    save: function() {
        var k_name = this.preffix+"_"+this.variants.length+'_'+this.steps.length;
        if(this.useAjax) {
            var params = 'action=save&name='+encodeURIComponent(k_name)+'&data='+encodeURIComponent(JSON.stringify(this.knowledge));
            tmpObj = this;
            this._callAjax(params,function(response){console.log(response)},function(response){alert(response)});
            return;
        }
        localStorage.setItem(this.preffix+"_"+this.variants.length+'_'+this.steps.length,JSON.stringify(this.knowledge));
    },
    decide: function(limit) {
        this._flatterizeSteps();
        this.decisions = [];

        for(var i = 0; i < this.variants.length; i++) {
            var sum = 0;
            for(var x = 0; x < this.steps.length; x++) {
                for(var xVar = 0; xVar < this.variants.length; xVar++) {
                    sum += Math.round(this.knowledge.data[i][x][xVar] *
                        this._flat_steps[x][xVar]);
                }
            }

            if(limit === true) {
                if(this.limit < sum) {
                    this.decisions[sum] = {variant: this.variants[i],sum:sum};
                }
                continue;
            }

            if(this.minLimit < sum) {
                this.decisions[sum] = {variant: this.variants[i],sum:sum};
            }
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
    addVariant: function(decision) {
        var dec_key = this.knowledge.variants.indexOf(decision);
        if(dec_key < 0) {
            dec_key = this.knowledge.variants.push(decision) - 1;
        }

        for(var x = 0; x < this.steps.length; x ++) {
            this.knowledge.data[dec_key][x] = [];
            for(var xVar = 0; xVar < this.variants.length; xVar++) {
                this.knowledge.data[dec_key][x][xVar] = Math.round(Math.random()*100)/500;
            }
        }
        return dec_key;
    },
    learn: function(decision, forgetFlag) {
        var best = 0;
        if(forgetFlag) {
            best = this.decide(true);
        }

        if(best && best != decision) {
            this.forget(best);
        }

        var dec_key = this.knowledge.variants.indexOf(decision);
        if(dec_key < 0) {
            dec_key = this.addVariant(decision);
            this._flatterizeSteps();
        }

        if(this.useART) {
            var tmpSteps = this._flat_steps;
            for (var q = 0; q < this.knowledge.variants.length; q++) {
                if(this.knowledge.variants[q] == decision) {
                    continue;
                }
                for(var s = 0; s < this.steps.length; s++) {
                    for (var i = 0; i < this.knowledge.variants.length; i++) {
                        if(this.knowledge.data[q][s][i] > 5) {
                            this._flat_steps[s][i] = Math.round(this._flat_steps[s][i]/1.1 * 100) / 100;
                        } else {
                            this._flat_steps[s][i] = Math.round(this._flat_steps[s][i]/0.8 * 10) / 10;
                        }
                    }
                }
            }
        }

        for(var x = 0; x < this.steps.length; x++) {
            for(var xVar = 0; xVar < this.variants.length;xVar++) {
                this.knowledge.data[dec_key][x][xVar] += (this._flat_steps[x][xVar] / 2);
            }
        }

        if(this.useART) {
            this._flat_steps = tmpSteps;
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
        var dec_key = this.knowledge.variants.indexOf(decision);
        if(dec_key < 0) {
            this.addVariant(decision);
            this._flatterizeSteps();
        }

        if(this.useART) {
            var tmpSteps = this._flat_steps;
            for (var q = 0; q < this.knowledge.variants.length; q++) {
                if(this.knowledge.variants[q] == decision) {
                    continue;
                }
                for(var s = 0; s < this.steps.length; s++) {
                    for (var i = 0; i < this.knowledge.variants.length; i++) {
                        if(this.knowledge.data[q][s][i] > 5) {
                            this._flat_steps[s][i] = Math.round(this._flat_steps[s][i]/1.1 * 100) / 100;
                        } else {
                            this._flat_steps[s][i] = Math.round(this._flat_steps[s][i]/0.8 * 10) / 10;
                        }
                    }
                }
            }
        }

        for(var x = 0; x < this.steps.length; x++) {
            for(var xVar = 0; xVar < this.variants.length;xVar++) {
                this.knowledge.data[dec_key][x][xVar] -= (this._flat_steps[x][xVar] / 10);
            }
        }

        if(this.useART) {
            this._flat_steps = tmpSteps;
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
                flat[k] = 1
            } else {
                flat[k] = 0
            }
        }
        return flat;
    }
};


function log(data) {
    document.getElementById('log').innerHTML += data+'<br>';
}