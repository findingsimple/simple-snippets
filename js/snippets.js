jQuery(document).ready(function($){
	var $snippetTabs = $("#snippets-tabs").tabs();

	$('#snippet-select').bind('change',function(){
		$snippetTabs.tabs('select',$(this).val());
	});

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
					var snippetTag = $('#snippet-select').val().replace('#snippet-tab-',''),
						snippetToInsert = SnippetData.snippetsToInsert[snippetTag];

					$(this).dialog("close");
					$.each(SnippetData.variables[snippetTag],function(name,value){
						snippetToInsert = snippetToInsert.replace('{'+name+'}',escapeToJSON(escapeToJSON($('#'+snippetTag+'_'+name).val())));
					});

					snippetToInsert = $.parseJSON(snippetToInsert);

					// HTML editor
					if (snippets_caller == 'html') {
						QTags.insertContent(snippetToInsert);
					} else {
						// Visual Editor
						snippets_canvas.execCommand('mceInsertContent', false, snippetToInsert);
					}
				}
			},
			width: 560,
		});
	});

	QTags.addButton('simple_snippets_id','snippet',function(){
		snippets_caller = 'html';
		jQuery('#snippets-dialog').dialog('open');
	});

	toggleUseContent();

	$('#_snippet_is_shortcode').bind('load change',function(){
		toggleUseContent();
	});

	$('#snippet_variable_adder').click(function(){
		var lastVarIndex = parseInt($('#snippet_variable_last_index').val()),
			nextVarIndex = lastVarIndex + 1,
			newFieldset  = $('#snippet-variables fieldset:last').clone(),
			lastHeaderText = $('#snippet-variables fieldset:last h5 code').text(),
			lastIndexRegex = new RegExp(lastVarIndex,'g');

		// Clear values
		newFieldset.find('input').val('');

		// Update the title
		newFieldset.find('h5 code').text(lastHeaderText.replace(lastIndexRegex,nextVarIndex));

		// Update the attributes
		$.each(['variable_description','variable_default'],function(index,keyName){
			newFieldset.find('[name="_snippet_variables['+lastVarIndex+']['+keyName+']"]').attr({
				'id': '_snippet_variables['+nextVarIndex+']['+keyName+']',
				'name': '_snippet_variables['+nextVarIndex+']['+keyName+']'
			});
			newFieldset.find('[for="_snippet_variables['+lastVarIndex+']['+keyName+']"]').attr({
				'for': '_snippet_variables['+nextVarIndex+']['+keyName+']'
			});
		});

		newFieldset.insertAfter($('#snippet-variables fieldset:last')).hide().slideDown('fast');

		$('#snippet_variable_last_index').val(nextVarIndex);
	});

	$('#snippet-variables .remove-button').live('click',function(){
		var variableFieldset = $(this).parents('fieldset.snippet-variable');

		// Hide the variable
		variableFieldset.slideUp('fast');

		// Clear values
		variableFieldset.find('input').val('');

	});

	function toggleUseContent(){
		if($('#_snippet_is_shortcode').attr('checked'))
			$('.use-snippet-content').slideDown();
		else
			$('.use-snippet-content').slideUp();
	}

	function escapeToJSON(string) {

	    if (string != null && string != "") {
			string = string.replace(/\\/g,'\\\\');
			string = string.replace(/\"/g,'\\"');
			string = string.replace(/\0/g,'\\0');
			string = string.replace(/\n/g, "\\n");
	    }

		return string;
	}

});

// Global variables to keep track of the canvas instance and which editor opened the snippet dialog
var snippets_canvas;
var snippets_caller = '';
