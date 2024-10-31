var nordotMadeChange = false;

jQuery(document).ready(function($) {

	$(function () {
		$('[data-toggle="tooltip"]').tooltip({ customClass: 'nordot-tooltip' })
    });
    
	$("#nordot-settings-save").click(function(event) {
        nordotMadeChange = false;
		event.preventDefault();
		$(this).addClass("disabled");
		$("i", this).attr("class", "spinner-border spinner-border-sm");
		$("form#nordot-settings").submit();
	});    

	$('#nordotSettingsModal').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var title = button.data('title');
		var img = button.data('img');
		var modal = $(this);
		modal.find('.modal-title').text(title);
		modal.find('.modal-body img').attr("src", img);
	  });

      $("form#nordot-settings :input").change(function() {
        nordotMadeChange = true;
      });

      $(window).bind('beforeunload', function(){
        if (nordotMadeChange) {
            return 'Are you sure you want to leave?';
        }
      });      
});