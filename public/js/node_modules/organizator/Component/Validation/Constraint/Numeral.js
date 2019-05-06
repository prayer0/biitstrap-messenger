define(
    [
        'organizator/Organizator',
        'organizator/Component/Validation/Constraint',
        'organizator/Component/Validation/ConstraintValidationResultBuilder',
        'numeral/min/numeral.min'
    ],
    function(
        Organizator,
        Organizator_Validation_Constraint,
        Organizator_Validation_ConstraintValidationResultBuilder,
        numeral
    ){
        class Organizator_Validation_Constraint_Numeral extends Organizator_Validation_Constraint {
            constructor(options) {
                super();

                if(typeof options.min !== 'undefined'){
                    this.min = numeral(options.min);

                    this.messages['ERROR_LESSER_THAN_MIN'] = 'This value must be greater than ' + this.min.value() + '.';
                    this.messages['SUCCESS_LESSER_THAN_MIN'] = 'This value is valid.';
                }

                if(typeof options.max !== 'undefined'){
                    this.max = numeral(options.max);
                }

                if(typeof options.gt !== 'undefined'){
                    this.gt = numeral(options.gt);

                    this.messages['ERROR_GT'] = 'This value must be greater than ' + this.gt.value() + '.';
                    this.messages['SUCCESS_GT'] = 'This value is valid.';
                }

                this.messages['ERROR_LESSER_THAN_MAX'] = 'This value is greater than the maximum limit.';
                this.messages['SUCCESS_LESSER_THAN_MAX'] = 'This value is valid.';
            }

            static getName(){
                return 'numeral';
            }

            validate(value) {
                var resultBuilder = new Organizator_Validation_ConstraintValidationResultBuilder();

                let elementValue = numeral(value);
                
                if(typeof this.min !== 'undefined'){
                    resultBuilder.setValue(value);
                    if(elementValue.value() >= this.min.value()){
                        resultBuilder.addSuccess(this.messages['SUCCESS_LESSER_THAN_MIN']);
                    }else{
                        resultBuilder.addError(this.messages['ERROR_LESSER_THAN_MIN']);
                    }
                }
                
                if(typeof this.max !== 'undefined'){
                    resultBuilder.setValue(value);

                    if(elementValue.value() <= this.max.value()){
                        resultBuilder.addSuccess(this.messages['SUCCESS_LESSER_THAN_MIN']);
                    }else{
                        resultBuilder.addError(this.messages['ERROR_LESSER_THAN_MIN']);
                    }
                }
                
                if(typeof this.gt !== 'undefined'){
                    resultBuilder.setValue(value);

                    if(elementValue.value() > this.gt.value()){
                        resultBuilder.addSuccess(this.messages['SUCCESS_GT']);
                    }else{
                        resultBuilder.addError(this.messages['ERROR_GT']);
                    }
                }

                return resultBuilder.getResult();
            }
        }
        
        Organizator.Validator.addConstraint(Organizator_Validation_Constraint_Numeral);
        
        return Organizator_Validation_Constraint_Numeral;
    }
);
