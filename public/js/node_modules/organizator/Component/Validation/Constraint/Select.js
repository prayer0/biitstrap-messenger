define(
    [
        'organizator/Organizator',
        'organizator/Component/Validation/Constraint',
        'organizator/Component/Validation/ConstraintValidationResultBuilder'
    ],
    function(
        Organizator,
        Organizator_Validation_Constraint,
        Organizator_Validation_ConstraintValidationResultBuilder,
    ){
        class Organizator_Validation_Constraint_Select extends Organizator_Validation_Constraint {
            constructor(options) {
                super();

                Object.assign(this, options);

                this.messages['MIN_ERROR'] = 'At least ' + this.min + ' option must be selected.';
                this.messages['MIN_SUCCESS'] = '';
                this.messages['MAX_ERROR'] = 'Maximum ' + this.max + ' options can be selected';
                this.messages['MAX_SUCCESS'] = '';
            }

            static getName(){
                return 'select';
            }

            validate(value, element) {
                var resultBuilder = new Organizator_Validation_ConstraintValidationResultBuilder();

                resultBuilder.setValue(value);

                if(this.min !== undefined){
                    if(element.querySelectorAll('option:checked').length < this.min){
                        resultBuilder.addError(this.messages['MIN_ERROR']);
                    }else{
                        resultBuilder.addSuccess(this.messages['MIN_SUCCESS']);
                    }
                }

                if(this.max !== undefined){
                    if(element.querySelectorAll('option:checked').length > this.max){
                        resultBuilder.addError(this.messages['MAX_ERROR']);
                    }else{
                        resultBuilder.addSuccess(this.messages['MAX_SUCCESS']);
                    }
                }

                return resultBuilder.getResult();
            }
        }
        
        Organizator.Validator.addConstraint(Organizator_Validation_Constraint_Select);
        
        return Organizator_Validation_Constraint_Select;
    }
);
