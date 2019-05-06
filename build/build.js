({
    baseUrl: "../public/js/node_modules",
    paths: {
		text: '../public/js/node_modules/organizator/Plugins/text',
		json: '../public/js/node_modules/organizator/Plugins/json',
		css: '../public/js/node_modules/organizator/Plugins/css',
		route: '../public/js/node_modules/organizator/Plugins/route',
		controller: '../public/js/node_modules/organizator/Plugins/controller',
		xliff: '../public/js/node_modules/organizator/Plugins/xliff'
	},
	stubModules: ['css'],
	name: "../public/js/app",
    out: "../public/js/app.min.js",
    optimize: "none",
    removeCombined: true,
    findNestedDependencies: true
})