/**
 * The "ace" plugin. 
 * This plugin is designed to  enhance the source view editor for CKEditor 4
 * and implement ACE Editor as a replacement. http://ace.c9.io
 * This plugin is based on the initial work on the CodeMirror plugin here: http://marijn.haverbeke.nl/codemirror/
 */

CKEDITOR.plugins.add( 'ace', {
	requires : [ 'sourcearea' ],

	// Initialize the plugin
	init : function( editor ) {
		
		console.log('initializing ACE plugin');
			
		// When the event "mode" is called in CKEditor it means someone clicked the "source" button.
		editor.on( 'mode', function() {
			
			//console.log('editor mode: ' + editor.mode);
			
			// if the user selected the "source" view
			if ( editor.mode == 'source' ) {
				
				// define some variables for the textarea that CKEditor is using, the parent element which is the container for CKEditor.
				var sourceAreaElement = $('textarea','.' + editor.id);
				var holderElement = sourceAreaElement.parent();
				
				// hide the textarea
				sourceAreaElement.hide();



				// START ACE
				
				var editorID = editor.id;
				var editorName = editor.name;
				
				$('.' + editorID).after('<div id="aceEditor_container_' + editorID + '" class="aceEditor_container" style=" background-color:white; position:absolute;">\
				<div id="aceEditor_' + editorID + '" style="width:100%; height:100%;"></div></div>'); //<textarea id="buffer"></textarea>
				
				// make the editor container fill the space.
				$('#aceEditor_container_' + editorID).css( holderElement.position() ).width( holderElement.width() ).height( holderElement.height() );
					
				// launch the editor and set the theme
				var aceEditor = ace.edit("aceEditor_" + editorID);
				aceEditor.setTheme("ace/theme/dreamweaver");
				aceEditor.getSession().setMode("ace/mode/html");
				aceEditor.setShowPrintMargin( 0 );
				aceEditor.getSession().setValue( editor.getData() );
				aceEditor.getSession().setUseWrapMode(false);
				
				// set the z-index for ACEEditor really high
				$('#aceEditor_container_' + editorID).css('z-index','9997');
				

				// CUSTOM FUNCTIONS
				
				// this function checks to see if we are returning to design view.  If so, purge all the ACE stuff
				function returnToDesignView(e) {
					
					//console.log('Before Command Exec: ' + e.data.name);
					
					if ( e.data.name == 'source') {
					
						// set the value of the editor
						//console.log('going back to CKEditor Design view');
						
						// Set the data of the CKEditor to the value of ACE Editor
						editor.setData( aceEditor.getSession().getValue(), function() {
							//console.log('change saved');
						}, false);
						
						// destroy the editor
						aceEditor.destroy();
						
						//remove the container
						$('#aceEditor_container_' + editorID).remove();
						
						// Remove the listeners

						editor.removeListener('beforeCommandExec', returnToDesignView );
						editor.removeListener('resize', resizeACE );
						editor.removeListener('afterCommandExec', maximizeACE );
						
						editor.fire( 'dataReady' );
						
					}

				}
				
				// this function will update the z-index of the ACE Editor based on whether we are maximized or not
				function maximizeACE(e) {
					
					//console.log('After Command Exec: ' + e.data.name);
					
					// if they are maximizing it
					if (e.data.name == 'maximize'){
						
						// if maximixed 
						if (e.data.command.state == 1 ) {
						
							$('#aceEditor_container_' + editorID).css('z-index','9997');
							
						} else {
							
							$('#aceEditor_container_' + editorID).css('z-index','auto');
							
						}
							
					}
					
				}

				// resizeACE 
				// this function will resize ACE editor to match the holderElement object's position
				function resizeACE() {
					
					//console.log('resizing ace');
											
					// make the editor container fill the space.
					$('#aceEditor_container_' + editorID).css( holderElement.position() ).width( holderElement.width() ).height( holderElement.height() );
					
					aceEditor.resize();
					
				}
					
				// updateCKEditor
				// this function updates the value of CKeditor
				function updateCKEditor(e){
					
					//console.log('change detected');
					
					var data = aceEditor.getSession().getValue();
					
					// Set the data of the CKEditor to the value of ACE Editor
					// NOTE: this function doesn't work in Chrome/Safari. Instead Force the data to the ckeditor source textarea
					//editor.setData(data, function(a) {

						//console.log('change saved ');

					//}, false);

					//$('.cke_source').html( data );
					$('.cke_source').val( data );

					return true;
					
				};
				
				
				// BIND EVENTS
				
				// when the ace editor changes, update CKEditor's source code
				aceEditor.on('input', function(e) {
					updateCKEditor(e);
				});

				// Commit source data back into 'source' mode.
				editor.on( 'beforeCommandExec', returnToDesignView ); 
				
				// When the editor fires the 'resize' event call the resize function.
				editor.on('resize', resizeACE);
				
				// run this after a command exeecutes in CKEditor
				editor.on('afterCommandExec', maximizeACE );
				
			}
			
		});
	
	
		// If we are sending them back to the WYSIWYG editor.
		editor.on( 'instanceReady', function(e) {
		  
			//console.log('instance ready');
			
			e.removeListener();
			
			if ( editor.mode == 'wysiwyg' ) {
				var thisData = editor.getData().indexOf('<?php');
				if (thisData !== -1) {
						editor.execCommand('source');
				};
			}
			
		});
		
		// in case we want to do anything when CKEditor fires the 'dataReady' event
		editor.on( 'dataReady', function(e) {
		  
			//console.log('data ready');
			
		});

	}

});
