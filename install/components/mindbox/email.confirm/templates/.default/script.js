$(function () {
    var $error = $('#mindbox-email-confirm-error');
    var $success =  $('#mindbox-email-confirm-success');

    var $targetForLoader = $('#mindbox-email-confirm--resend');

    $('#mindbox-email-confirm--resend').on('click', function () {
        var request = BX.ajax.runComponentAction('mindbox:email.confirm', 'resend', {
            mode:'class',
        });
	
    });
});
