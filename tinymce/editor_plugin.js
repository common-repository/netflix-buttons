(function() {
	tinymce.create('tinymce.plugins.NetflixPlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceNetflix', function() {
				ed.windowManager.open({
						file : url + '/netflix.html',
						width : 300 + parseInt(ed.getLang('netflix.delta_width', 0)),
						height : 300 + parseInt(ed.getLang('netflix.delta_height', 0)),
						inline : 1
					}, {
						plugin_url : url
				});
			});
			 
			// Register buttons
			ed.addButton('netflix', {title : 'Netflix', cmd : 'mceNetflix', image: url + '/netflix.gif' });
		},
		 
		getInfo : function() {
			return {
				longname : 'Netflix Button',
				author : 'James Swindle',
				authorurl : 'http://jaswin.net',
				infourl : 'http://jaswin.net/code/netflix-buttons-wordpress-plugin/',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});
	 
	// Register plugin
	tinymce.PluginManager.add('netflix', tinymce.plugins.NetflixPlugin);
})();