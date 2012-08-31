jQuery(document).ready(function($) {
	
	// Tweak the featured thumbnail metabox
	$("#postimagediv h3.hndle span").text("Badge Image");
	//$("#set-post-thumbnail").text("Set badge image");
	$("#remove-post-thumbnail").text("Remove badge image");
	
	
	// Set vars with the trigger row and the conditionals (that hide/show).
	var $trigger = "#simplebadges_meta_box tr:eq(0) input:eq(2)";
	var $conditionals = "#simplebadges_meta_box tr:eq(1), #simplebadges_meta_box tr:eq(2), #simplebadges_meta_box tr:eq(3)";
	
	// Show the conditionals if automatic badges is checked.
	if ( $( $trigger ).is( ":checked" ) ) {
		
		$( $conditionals ).css( "display", "table-row" );
		
	} else {
		
		// Hide the three conditionals to begin with.
		$( $conditionals ).hide();
		
	}
	
	// Pop options open if the checkbox is checked.
	$( $trigger ).click( function(){

		$( $conditionals ).toggle();
		
	} );
	
});