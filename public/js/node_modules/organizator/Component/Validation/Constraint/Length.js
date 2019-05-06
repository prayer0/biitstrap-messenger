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
        class Organizator_Validation_Constraint_Length extends Organizator_Validation_Constraint {
            constructor(options) {
                super();

                Object.assign(this, options);

                this.messages['TOO_SHORT_ERROR'] = 'This value is too short. It must have ' + this.min + ' characters or more.';
                this.messages['TOO_SHORT_SUCCESS'] = 'This value is valid.';
                this.messages['TOO_LONG_ERROR'] = 'This value is too long. It must have ' + this.max + ' characters or less.';
                this.messages['TOO_LONG_SUCCESS'] = 'This value is valid.';
            }

            static getName(){
                return 'length';
            }

            validate(value) {
                var resultBuilder = new Organizator_Validation_ConstraintValidationResultBuilder();

                resultBuilder.setValue(value);

                if(this.trim){
                    value = value.trim().replace(/\s+/, " ");
                }

                if(this.min !== undefined){
                    if(value.length >= this.min){
                        resultBuilder.addSuccess(this.messages['TOO_SHORT_SUCCESS']);
                    }else{
                        resultBuilder.addError(this.messages['TOO_SHORT_ERROR']);
                    }
                }

                if(this.max !== undefined){
                    if(value.length <= this.max){
                        resultBuilder.addSuccess(this.messages['TOO_LONG_SUCCESS']);
                    }else{
                        resultBuilder.addError(this.messages['TOO_LONG_ERROR']);
                    }
                }

                return resultBuilder.getResult();
            }
        }
        
        Organizator.Validator.addConstraint(Organizator_Validation_Constraint_Length);
        
        return Organizator_Validation_Constraint_Length;
    }
);
