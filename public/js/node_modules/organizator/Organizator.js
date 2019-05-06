define(
    [
        'organizator/config',
        'organizator/Component/Globals/Globals',
        'organizator/Component/Routing/Routing',
        'organizator/Component/Validation/Validator',
        'organizator/Component/Nunjucks/Nunjucks',
        'organizator/Component/Translator/Translator',
        'lokijs/build/lokijs.min',
        'organizator/Util/FormSerializer',
        'organizator/Polyfill/polyfill-extended.min',
        'organizator/Polyfill/parents',
        'organizator/Polyfill/date.format'
    ],
    function(
        config,
        Organizator_Globals,
        Organizator_Routing,
        Organizator_Validation_Validator,
        Organizator_Nunjucks,
        Organizator_Translator,
        Loki,
        Organizator_Util_FormSerializer
    ){
        class Organizator {
            constructor(configuration = {}){
                var self = this;

                this.configuration = Object.assign(config, configuration);
                this.configuration.localization.locale = document.querySelector('html').getAttribute('lang');

                this.Globals = new Organizator_Globals();

                let basePath = '';
                this.Routing = new Organizator_Routing({
                    base: basePath ? basePath : location.protocol + '//' + location.host,
                    mode: 'history'
                });
                
                this.Validator = new Organizator_Validation_Validator();
                
                this.Db = new Loki('organizator_database.json');
                this.PersistentDb = new Loki("organizator_peristent_database.db", { 
                  autoload: true,
                  // autoloadCallback : databaseInitialize,
                  autosave: false
                });
                
                this._Nunjucks = Organizator_Nunjucks;
                this.Nunjucks = new this._Nunjucks.Environment(new this._Nunjucks.WebLoader(''));

                this.Nunjucks.addGlobal('path', function(route, parameters, options) {
                    return self.Routing.Generator.generateUrl(route, parameters, options);
                });

                require(['organizator/Util/NumberFormat'], function(number_format){
                    self.Nunjucks.addFilter('number_format', function(number, decimals, decPoint, thousandsSep) {
                        return number_format(number, decimals, decPoint, thousandsSep);
                    });
                });

                require(['organizator/Util/ArrayIntersect'], function(array_intersect){
                    self.Nunjucks.addFilter('array_intersect', function() {
                        return array_intersect(arguments);
                    });
                });

                self.Nunjucks.addFilter('date', function(date, format) {
                    return new Date(date * 1000).format(format);
                });

                self.Nunjucks.addFilter('hex2dec', function(hex) {
                    return parseInt(hex, 16);
                });

                self.Nunjucks.addFilter('truncate', function(str) {
                    return str;
                });

                /*
                self.Nunjucks.addFilter('trans', function(id, params, domain) {
                    return bazinga.trans(id, params, domain);
                });*/

                this.FormSerializer = new Organizator_Util_FormSerializer();
                this.Translator = new Organizator_Translator({
                    defaultLocale: this.configuration.localization.defaultLocale,
                    availableLocales: this.configuration.localization.availableLocales,
                    locale: this.configuration.localization.locale
                });

                
                this.applications = {};
                this.globals = {};
            }
        }
        
        if(window.Organizator === undefined){
            window.Organizator = new Organizator();
        }

        return window.Organizator;
    }
);