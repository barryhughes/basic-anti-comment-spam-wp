if (jQuery !== undefined) jQuery(document).ready(function($) {
	var marker = $("input[name='acsmarker']");

	// Do not proceed if required fields are not present
	if (marker.length < 1) return;
	if (acs_references === undefined || acs_references.ajax_url === undefined) return;

	var field = $("input[name='acsmarker']").val();
	var field_parts = field.split("|");
	var time = "";
	var hash = "";
	var wait = 0;

	// Determine wait time and initial hash
	if (field_parts.length < 3) return; // Unworkable if the field doesn't contain expected data
	wait = ( parseInt(field_parts[0]) * 1000 ) + 125; // Add a 1/8 sec safety margin
	time = field_parts[1];
	hash = field_parts[2];

	// Add any update to the field
	function add_update(update) {
		marker.val(field + "|" + update);
	}

	// Talk to server and try to retrieve an update
	function get_field_update() {
		$.post(acs_references.ajax_url, {
			"action": "acs_update",
			"time": time,
			"hash": hash
		}, add_update, "text");
	}

	setTimeout(get_field_update, wait);
});
