$(function () {
    var $error = $('#mindbox-phone-confirm-error');
    var $success =  $('#mindbox-phone-confirm-success');

    var $targetForLoader = $('#mindbox-phone-confirm');

    $('#mindbox-phone-confirm--resend').on('click', function () {
        var request = BX.ajax.runComponentAction('mindbox:phone.confirm', 'resendCode', {
            mode:'class',
        });
    });

    $('#mindbox-phone-confirm #submit-button').on('click', function (e) {
        e.preventDefault();
        loader.show($targetForLoader);
        var code = $('#mindbox-code').val();

        var request = BX.ajax.runComponentAction('mindbox:phone.confirm', 'checkCode', {
            mode:'class',
            data: {
                code: code
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoader);
            var data = response.data;
            if(data.type === 'error') {
                $error.text(data.message);
                $error.show();
            }
            else if(data.type === 'success') {
                var url = data.url;

                $error.hide();
                $success.text(data.message);
                $success.show();
            }
        })
    })
});