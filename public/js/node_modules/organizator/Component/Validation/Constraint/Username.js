/*
 * Validation component
 * 
 * @namespace Organizator/Component/Validation/Constraint
 */
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
        class Organizator_Validation_Constraint_Username extends Organizator_Validation_Constraint {
            constructor(strict) {
                super();

                this.regex = /^[a-zA-Z]([a-zA-Z0-9]|(([.\-_])[a-zA-Z0-9])(?!\3))*[a-zA-Z0-9]*$/;
                this.regex_startsWith = /^[a-zA-Z]/;
                this.regex_endsWith = /[a-zA-Z0-9]$/;

                this.messages['ERROR_NOT_VALID'] = 'Only letters, numbers and two (non-consecutive) special characters (._-) are allowed. It must start with a letter and end with a letter or number.';
                this.messages['SUCCESS_VALID'] = '.';
                this.messages['ERR_STARTSWITH'] = 'It must start with a (English) letter.';
                this.messages['ERR_ENDSWITH'] = 'It must end with a (English) letter or a number.';
                this.messages['ERR_MAXSEP'] = 'Only two (non-consecutive) special characters (._-) are allowed.';
            }

            static getName(){
                return 'username';
            }

            validate(value) {
                var resultBuilder = new Organizator_Validation_ConstraintValidationResultBuilder();

                resultBuilder.setValue(value);

                if(!this.regex.test(value)){
                    let specialCharCount = 0;
                    for(let char of ['.', '_', '-']){
                        specialCharCount += this.substr_count(value, char);
                    }

                    if(!this.regex_startsWith.test(value)){
                        resultBuilder.addError(this.messages['ERR_STARTSWITH']);
                    } else if(!this.regex_endsWith.test(value)){
                        resultBuilder.addError(this.messages['ERR_ENDSWITH']);
                    } else if(specialCharCount > 2) {
                        resultBuilder.addError(this.messages['ERR_MAXSEP']);
                    }else{
                        resultBuilder.addError(this.messages['ERROR_NOT_VALID']);
                    }

                }else{
                    resultBuilder.addSuccess(this.messages['SUCCESS_VALID']);
                }

                return resultBuilder.getResult();
            }

            substr_count (haystack, needle, offset, length) { 
              // eslint-disable-line camelcase
              //  discuss at: http://locutus.io/php/substr_count/
              // original by: Kevin van Zonneveld (http://kvz.io)
              // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
              // improved by: Brett Zamir (http://brett-zamir.me)
              // improved by: Thomas
              //   example 1: substr_count('Kevin van Zonneveld', 'e')
              //   returns 1: 3
              //   example 2: substr_count('Kevin van Zonneveld', 'K', 1)
              //   returns 2: 0
              //   example 3: substr_count('Kevin van Zonneveld', 'Z', 0, 10)
              //   returns 3: false

              var cnt = 0

              haystack += ''
              needle += ''
              if (isNaN(offset)) {
                offset = 0
              }
              if (isNaN(length)) {
                length = 0
              }
              if (needle.length === 0) {
                return false
              }
              offset--

              while ((offset = haystack.indexOf(needle, offset + 1)) !== -1) {
                if (length > 0 && (offset + needle.length) > length) {
                  return false
                }
                cnt++
              }

              return cnt
            }
        }
        
        Organizator.Validator.addConstraint(Organizator_Validation_Constraint_Username);
        
        return Organizator_Validation_Constraint_Username;
    }
);
