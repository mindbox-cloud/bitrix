$(function () {
    $('#mindbox-phone-confirm--resend').on('click', function () {
        let request = BX.ajax.runComponentAction('mindbox:phone.confirm', 'resendCode', {
            mode: 'class',
        });
    });

    $('#mindbox-phone-confirm #submit-button').on('click', function (e) {
        e.preventDefault();

        loader.show($('#mindbox-phone-confirm'));

        let code = $('#mindbox-code').val();

        let request = BX.ajax.runComponentAction('mindbox:phone.confirm', 'checkCode', {
            mode: 'class',
            data: {
                code: code
            }
        });

        request.then(function (response) {
            loader.hide($('#mindbox-phone-confirm'));

            if (response.data.type === 'error') {
                $('#mindbox-phone-confirm-error').text(response.data.message).show();
            } else if (response.data.type === 'success') {
                $('#mindbox-phone-confirm-error').hide();
                $('#mindbox-phone-confirm-success').text(response.data.message).show();
            }
        })
    })
});