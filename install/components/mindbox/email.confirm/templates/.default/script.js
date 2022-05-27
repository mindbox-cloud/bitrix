$(function () {
    $('#mindbox-email-confirm--resend').on('click', function () {
        let request = BX.ajax.runComponentAction('mindbox:email.confirm', 'resend', {
            mode:'class',
        });
	
    });
});
