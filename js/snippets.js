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
					var snippetName = $('#snippet-select').val().replace('#snippet-tab-',''),
						snippetToInsert = SnippetData.snippetsToInsert[snippetName];

					$(this).dialog("close");

					$.each(SnippetData.variables[snippetName],function(name,value){
						snippetToInsert = snippetToInsert.replace('{'+name+'}',escapeToJSON(escapeToJSON($('#'+snippetName+'_'+name).val())));
					});

					snippetToInsert = $.parseJSON(snippetToInsert);

					// HTML editor
					if (snippets_caller == 'html') {

						QTags.insertContent(snippetToInsert);

					} else {

						// Replace any raw tags with placeholder image
						if (/([\s\S]*?)(\[raw\]|<!--raw-->|<!--start_raw-->)([\s\S]*?)/gi.test(snippetToInsert)) {
							var placeholderSrc = rawhtml_placeholder_image, // This should be set if [raw] content exists
								placeholderClass = 'raw-html-placeholder',
								placeholderTitle = 'Switch to HTML mode to edit this code';

							// Replace any <!--raw--> content in the snippet
							snippetToInsert = snippetToInsert.replace(
								/(\[raw\]|<!--raw-->|<!--start_raw-->)([\s\S]*?)(\[\/raw\]|<!--\/raw-->|<!--end_raw-->)/gi,
								function(content){ // NB: content is stored in alt attribute
									return '<img alt="'+escape(content)+'" class="'+placeholderClass+' mceItemNoResize" title="'+placeholderTitle+'" src="'+placeholderSrc+'" />';
								}
							);
						}

						// Visual Editor
						snippets_canvas.execCommand('mceInsertContent', false, snippetToInsert);
					}
				}
			},
			width: 560,
		});
	});

	if ( typeof QTags != 'undefined' ) {
		QTags.addButton('simple_snippets_id','snippet',function(){
			snippets_caller = 'html';
			jQuery('#snippets-dialog').dialog('open');
		});
	}

	toggleUseContent();

	$('#_snippet_is_shortcode').bind('load change',function(){
		toggleUseContent();
	});

	$('#snippet_variable_adder').click(function(){
		var elementCount = $('#snippet-variables fieldset').length,
			oldElementID = elementCount - 1,
			newFieldset  = $('#snippet-variables fieldset:last').clone();

		// Clear values
		newFieldset.find('input').val('');

		// Update the attributes
		$.each(['variable_name','variable_default'],function(index,keyName){
			newFieldset.find('[name="_snippet_variables['+oldElementID+']['+keyName+']"]').attr({
				'id': '_snippet_variables['+elementCount+']['+keyName+']',
				'name': '_snippet_variables['+elementCount+']['+keyName+']'
			});
			newFieldset.find('[for="_snippet_variables['+oldElementID+']['+keyName+']"]').attr({
				'for': '_snippet_variables['+elementCount+']['+keyName+']'
			});
		});

		$('#snippet-variables fieldset:last').after(newFieldset);

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
