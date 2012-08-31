jQuery(document).ready(function($) {
	
	
	// Tweak the featured thumbnail metabox
	$("#postimagediv h3.hndle span").text("Badge Image");
	//$("#set-post-thumbnail").text("Set badge image");
	$("#remove-post-thumbnail").text("Remove badge image");
	
	
	// Show the conditionals if automatic badges is checked.
	if ( $( "#simplebadges_meta_box tr:eq(0) input:eq(2)" ).is( ":checked" ) ) {
		
		$( "#simplebadges_meta_box tr:eq(1), #simplebadges_meta_box tr:eq(2), #simplebadges_meta_box tr:eq(3)" ).css( "display", "table-row" );
		
	} else {
		
		// Hide the three conditionals to begin with.
		$( "#simplebadges_meta_box tr:eq(1), #simplebadges_meta_box tr:eq(2), #simplebadges_meta_box tr:eq(3)" ).hide();
		
	}
	
	
	// Pop options open if the checkbox is checked.
	$( "#simplebadges_meta_box tr:eq(0) input:eq(2)" ).click( function(){

		$( "#simplebadges_meta_box tr:eq(1), #simplebadges_meta_box tr:eq(2), #simplebadges_meta_box tr:eq(3)" ).toggle();
		
	} );
	

});