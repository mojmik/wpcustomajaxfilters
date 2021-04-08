function saveAndAdd() {
	var $butt=jQuery('button.editor-post-publish-button__button');
	var customPost=jQuery('#post_type').val();
	var url='./post-new.php?post_type='+customPost;
	
	jQuery('button.editor-post-publish-button__button').click();  
	setTimeout(function() {				
				window.location=url;
			}, 2000);	
	return false;  
}

jQuery(function($){

// on upload button click
$('body').on( 'click', '.icon-upl', function(e){
 
	e.preventDefault();

	var button = $(this),
	custom_uploader = wp.media({
		title: 'Insert image',
		library : {
			// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
			type : 'image'
		},
		button: {
			text: 'Use this image' // button label text
		},
		multiple: false
	}).on('select', function() { // it also has "open" and "close" events
		var attachment = custom_uploader.state().get('selection').first().toJSON();
		//button.html('<img style="width:150px" src="' + attachment.url + '">').next().val(attachment.id).next().show();			
		button.parent().children('.icon-rmv').show();
		button.parent().children('input[name="icon"]').val(attachment.id);	
		button.html('<img style="width:150px" src="' + attachment.url + '">');		
		button.blur();
	}).open();

	});

	// on remove button click
	$('body').on('click', '.icon-rmv', function(e){

		e.preventDefault();

		var button = $(this);		
		button.parent().children('input[name="icon"]').val('');		
		button.parent().children('.icon-upl').html('Upload image');	
		button.hide();	
		//button.prev().val(''); // emptying the hidden field
		//button.hide().prev().prev().html('Upload image');
	});
});