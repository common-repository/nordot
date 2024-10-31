// Only execute the nordot modal JS file if the nordot widget JS file is not present, otherwise they conflict.
jQuery(document).ready(function($) {
	
	var noNordotWidget = true;
	$('script').each(function(i, obj) {
		var jsSrc = $(this).attr('src');
		if (jsSrc) {
			if (jsSrc.includes('kiji.is/widgets')) {
				noNordotWidget = false;
				return false;
			}
		}
	});
	
	if (noNordotWidget) {
		$.getScript('https://this.kiji.is/modal/modal-1.0.0.min.js', function()
				{
				    // script is now loaded and executed.
				});		
	}
		
});