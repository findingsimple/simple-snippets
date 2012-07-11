jQuery(document).ready(function($){
	var $tabs = $("#snippets-tabs").tabs();
	console.log(SnippetData);
	$(function() {
		$( "#snippets-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			dialogClass: 'wp-dialog',
			buttons: {
				Cancel: function() {
					$(this).dialog("close");
				},
				Insert: function() {
					$(this).dialog("close");

					var snippetName = $("#snippets-tabs li:eq("+$tabs.tabs('option','selected')+") a").attr('href').replace('#snippet-tab-','');

					var snippetToInsert = SnippetData.contentToInsert[snippetName];

					console.log(SnippetData.variables[snippetName]);

					$.each(SnippetData.variables[snippetName],function(name,value){
						snippetToInsert = snippetToInsert.replace('{'+name+'}', $('#'+snippetName+'_'+name).val());
					});

					snippetToInsert = $.parseJSON(snippetToInsert);

					console.log(snippetToInsert);

					// HTML editor
					if (snippets_caller == 'html') {
						QTags.insertContent(snippetToInsert);
					} else { // Visual Editor
						snippets_canvas.execCommand('mceInsertContent', false, snippetToInsert);
					}
				}
			},
			width: 560,
		});
	});
});

// Global variables to keep track of the canvas instance and which editor opened the snippet dialog
var snippets_canvas;
var snippets_caller = '';
