require(
	[
		'organizator/Organizator'
	],
	function(
		Organizator
	){
		require(
			[
			    'route!organizator/Resources/routes',
			    'controller!organizator/Resources/controllers'
			],
			function(
				routes,
				controllers
			){
				require(
					[
						'organizator/Apps/MyApp/MyApp',
						'organizator/Apps/InviteForm/InviteForm',
						'organizator/Apps/MessageForm/MessageForm',
						'organizator/Apps/MessageServer/MessageServer'
					],
					function(
						MyApp,
						InviteForm,
						MessageForm,
						MessageServer
					){
						new MyApp();
						new InviteForm();
						new MessageForm();
						new MessageServer();
					}
				);
			}
		);
	}
);