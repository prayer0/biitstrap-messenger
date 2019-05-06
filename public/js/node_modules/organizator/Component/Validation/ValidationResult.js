/*
 * Organizator_Validation_ValidationResult
 */
define(function(){
    class Organizator_Validation_ValidationResult {
        constructor(options){
            this.isValid = options.isValid;
            this.results = options.results;
            this.errorCount = options.errorCount || 0;
        }
    }

    return Organizator_Validation_ValidationResult;
});