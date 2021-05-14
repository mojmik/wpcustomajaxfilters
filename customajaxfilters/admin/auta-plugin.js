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

	function addRemoveListener() {
		jQuery("form.removeCPT").submit(function() {	
			var thisForm=$(this);
			var formData={};
			thisForm.find( '[name]' ).each( function( i , v ){
				let input = $( this ); // resolves to current input element.
				let name = input.attr( 'name' );
				let value = input.val();
				formData[name] = value;
			});
	
			jQuery.ajax({
				   type: "POST",
				   url: ajaxurl,
				   data: {
					action: "editCPT",
					slug: formData["slug"],
					cafActionRemove: formData["cafActionRemove"]
				  },
				success: function( data ) {
					let jsonObj=JSON.parse(data);
					jQuery("#"+jsonObj.id).remove();
				 }
		   });
		   return false;
		 });
	}

	jQuery("form.createCPT").submit(function() {	
		var singular = $("input[name='singular']").val();		
		var plural = $("input[name='plural']").val();		
		var cafAction = $("input[name='cafAction']").val();	
		jQuery.ajax({
			   type: "POST",
			   url: ajaxurl,
			   data: {
				action: "createCPT",
				singular: singular,
				cafAction: cafAction,
				plural: plural,
			  },
			success: function( data ) {
				//window.location.reload();
				jQuery("#mAutaCustomPosts").append(data);
				addRemoveListener();
			 }
	   });
	   return false;
	 });

	 jQuery("form.editCPT").submit(function() {	
		var thisForm=$(this);
		var formData={};
		thisForm.find( '[name]' ).each( function( i , v ){
			let input = $( this ); // resolves to current input element.
			let name = input.attr( 'name' );
			let value = input.val();
			formData[name] = value;
		});

		jQuery.ajax({
			   type: "POST",
			   url: ajaxurl,
			   data: {
				action: "editCPT",
				singular: formData["singular"],
				cafAction: formData["cafAction"],
				slug: formData["slug"],
				plural: formData["plural"],
				cafActionEdit: formData["cafActionEdit"],
			  },
			success: function( data ) {
				window.location.reload();
			 }
	   });
	   return false;
	 });

	 addRemoveListener();

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
	});
});