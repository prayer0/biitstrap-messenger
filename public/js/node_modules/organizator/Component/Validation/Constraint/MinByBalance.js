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
        class Organizator_Validation_Constraint_MinByBalance extends Organizator_Validation_Constraint {
            constructor(options) {
                super();

                this.messages['ERROR_NOT_SUFFICIENT'] = 'Insufficient funds.';
                this.messages['SUCCESS_SUFFICIENT'] = 'Balance is sufficient.';
            }

            static getName(){
                return 'minbybalance';
            }

            validate(value, element) {
                var resultBuilder = new Organizator_Validation_ConstraintValidationResultBuilder();

                if(Organizator.applications.AppUser.user.balance >= (value * 100000000)){
                    resultBuilder.addSuccess(this.messages['SUCCESS_SUFFICIENT']);
                }else{
                    resultBuilder.addError(this.messages['ERROR_NOT_SUFFICIENT']);
                }
                
                return resultBuilder.getResult();
            }
        }
        
        Organizator.Validator.addConstraint(Organizator_Validation_Constraint_MinByBalance);
        
        return Organizator_Validation_Constraint_MinByBalance;
    }
);
