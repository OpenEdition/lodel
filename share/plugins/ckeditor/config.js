/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	config.toolbar_Advanced =
	[
	    ['Source'],
	    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','Anchor'],
	    ['Table','HorizontalRule','SpecialChar','PageBreak'],
	    '/',
	    ['Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks','-','About']
	];

	config.toolbar_Default =
	[
	    ['Source'],
	    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['Link','Unlink','Anchor'],
	    ['Table','HorizontalRule','SpecialChar','PageBreak'],
	    '/',
	    ['Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks','-','About']
	];
	
	config.toolbar_Basic =
	[
	    ['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', 'Outdent','Indent','-', 'Link', 'Unlink','-','About','-','Source']
	];
	
	config.toolbar_Simple = [
         ['Cut','Copy','Paste','PasteText','PasteFromWord','RemoveFormat'],
         ['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
         ['NumberedList','BulletedList','Outdent','Indent'],
         ['Link','Unlink'],
         ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
         ['FontFormat'],
         ['About','-','Source']
     ];
	config.resize_enabled = false;
};
