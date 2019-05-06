define(
    [
        'organizator/Organizator',
        'organizator/Component/Validation/Constraint',
        'organizator/Component/Validation/ConstraintValidationResultBuilder'
    ],
    function(
        Organizator,
        Organizator_Validation_Constraint,
        Organizator_Validation_ConstraintValidationResultBuilder
    ){
        class Organizator_Validation_Constraint_Quill extends Organizator_Validation_Constraint {
            constructor(options) {
                super();

                Object.assign(this, options);

                this.messages['ERROR_NOT_VALID'] = 'This value must not be empty.';
                this.messages['SUCCESS_VALID'] = 'This value %value% is a valid.';
                this.messages['MAX_ERROR'] = '';
                this.messages['MAX_SUCCESS'] = '';
            }

            static getName(){
                return 'quill';
            }

            validate(value, element) {
                var resultBuilder = new Organizator_Validation_ConstraintValidationResultBuilder();

                var quillElement = document.querySelector(this.element + ' > .ql-editor');
                var textLength = quillElement.textContent.trim().length;
                
                if(textLength <= 0){
                    resultBuilder.addError(this.messages['ERROR_NOT_VALID']);
                }else{
                    resultBuilder.addSuccess(this.messages['SUCCESS_VALID']);
                }

                if(textLength >= this.max){
                    resultBuilder.addError(this.messages['MAX_ERROR']);
                }else{
                    resultBuilder.addSuccess(this.messages['MAX_SUCCESS']);
                }

                return resultBuilder.getResult();
            }
        }
        
        Organizator.Validator.addConstraint(Organizator_Validation_Constraint_Quill);
        
        return Organizator_Validation_Constraint_Quill;
    }
);
