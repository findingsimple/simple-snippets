// Docu : http://www.tinymce.com/wiki.php/API3:tinymce.api.3.x
(function() {

	tinymce.PluginManager.requireLangPack('simple_snippets');

	tinymce.create('tinymce.plugins.simple_snippets', {
		init : function(ed, url) {

			// Register the snippet command
			ed.addCommand('mce_simple_snippets', function() {
				snippets_canvas = ed;
				snippets_caller = 'visual';
				jQuery( "#snippets-dialog" ).dialog( "open" );
			});

			// Register snippet button
			ed.addButton('simple_snippets', {
				title : 'simple_snippets.desc',
				cmd : 'mce_simple_snippets',
				image : url + '/simple-snippets-icon.png'
			});
		},
		getInfo : function() {
			return {
					longname  : 'Simple Snippets',
					author 	  : 'Brent Shepherd',
					authorurl : 'http://findingsimple.com/',
					version   : '1.0'
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('simple_snippets', tinymce.plugins.simple_snippets);
})();


