jQuery(document).ready(function($) {
	
	// Tweak the featured thumbnail metabox
	$("#postimagediv h3.hndle span").text("Badge Image");
	//$("#set-post-thumbnail").text("Set badge image");
	$("#remove-post-thumbnail").text("Remove badge image");
	
	// Hide the three conditionals to begin with.
	$( "#simplebadges_meta_box tr:eq(1), #simplebadges_meta_box tr:eq(2), #simplebadges_meta_box tr:eq(3)" ).hide();
	
	// Show the first conditional if automatic badges is checked.
	
		
	// // Maybe I don't need CSS for now
	// $("#simplebadges-meta-box tr:eq(1)").hide();
	// $("#simplebadges-meta-box tr:eq(3)").hide();
	// $("#simplebadges-meta-box tr:eq(4)").hide();
	// 
	
	// Adjust the conditional dropdowns
	// $("#simplebadges_badge_conditional_parttwo").appendTo("#simplebadges-meta-box tr:eq(2) td");
	// $("#simplebadges_badge_conditional_partthree").appendTo("#simplebadges-meta-box tr:eq(2) td");
	// 
	// // Display none the row unless the automatic option is chosen
	// if ( $("#simplebadges-meta-box tr:eq(0) input:eq(1)").is(":checked") ) {
	// 	$("#simplebadges-meta-box tr:eq(2)").css( "display", "table-row" );	
	// } else {
	// 	$("#simplebadges-meta-box tr:eq(2)").css( "display", "none" );
	// }
	// 
		// $("#simplebadges-meta-box tr:eq(0) input:eq(1), #simplebadges-meta-box tr:eq(0) td label:eq(1)").click(function() {
	// 	$("#simplebadges-meta-box tr:eq(2)").css( "display", "table-row" );	
	// });
	// 
	// $("#simplebadges-meta-box tr:eq(0) input:eq(0), #simplebadges-meta-box tr:eq(0) td label:eq(0)").click(function() {
	// 	$("#simplebadges-meta-box tr:eq(2)").css( "display", "none" );	
	// });

});