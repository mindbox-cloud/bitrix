$(document).ready(function() {
    var phone = localStorage.getItem('phone');
    var $error = $('#mindbox-auth-sms-error');
    var $success = $('#mindbox-auth-sms-success');

    var validationMap  = {
        '/customer/birthDate': $('#mindbox-fillup-birth-date-error'),
        '/customer/mobilePhone': $('#mindbox-fillup-phone-error'),
        '/customer/lastName': $('#mindbox-fillup-last-name-error'),
        '/customer/firstName': $('#mindbox-fillup-name-error'),
        '/customer/email': $('#mindbox-fillup-email-error'),
    };

    var inputCode = function(phone) {
        $('#mindbox-input-phone').hide();
        $('#mindbox-auth-sms--mobile-phone').text(phone);
        $('#mindbox-input-code').show();
    };
    
    var captchaReload = function() {
        var request = BX.ajax.runComponentAction('mindbox:auth.sms', 'captchaUpdate', {
            mode:'class',
        });

        request.then(function (response) {
            var data = response.data;
            $('#mindbox---captcha_sid').val(data.captcha_sid);
            $('#mindbox--captcha_img').attr("src", "/bitrix/tools/captcha.php?captcha_sid=" + data.captcha_sid);
        });
    };

    $('#mindbox-input-phone').on('submit', function (event) {
        var $targetForLoader = $('#mindbox-input-phone');

        loader.show($targetForLoader);
        event.preventDefault();

        phone = $('#mindbox-num').val();
        localStorage.setItem('phone', phone);

        var request = BX.ajax.runComponentAction('mindbox:auth.sms', 'sendCode', {
            mode:'class',
            data: {
                phone: phone
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoader);

            if(response.data.type === 'success') {
                $error.hide();
                inputCode(phone);
            }
            else if(response.data.type === 'error') {
                $error.text(response.data.message);
                $error.show();
            }
        });
    });

    $('#mindbox-submit-code--reset').on('click', function () {
        $error.hide();
        localStorage.setItem('phone', '');
        $('#mindbox-input-phone').show();

        $('#mindbox-input-code').hide();
    });

    $('#mindbox-submit-code--resend').on('click', function () {
        $error.hide();
        var request = BX.ajax.runComponentAction('mindbox:auth.sms', 'resend', {
            mode:'class',
            data: {
                phone: phone
            }
        });
    });

    $('#mindbox-input-code').on('submit', function (event) {
        var $targetForLoader = $('#mindbox-input-code');

        loader.show($targetForLoader);
        event.preventDefault();

        var code = $('#mindbox-code').val();

        var request = BX.ajax.runComponentAction('mindbox:auth.sms', 'checkCode', {
            mode:'class',
            data: {
                code: code,
                phone: phone
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoader);

            if (response.data.type === 'success') {
                localStorage.setItem('phone', '');
                window.location.reload()
            } else if (response.data.type === 'error') {
                $error.text(response.data.message);
                $error.show();
            } else if(response.data.type === 'fillup') {

                if ($('#mindbox-fillup-profile').length) {
                    let $genderId = (response.data.sex === 'male')? 1:2;

                    $('#mindbox-input-code').hide();
                    $('#mindbox-fillup-profile').show();

                    $('#mindbox-fillup-phone').val(response.data.mobilePhone);
                    $('#mindbox-fillup-email').val(response.data.email);
                    $('#mindbox-fillup-name').val(response.data.firstName);
                    $('#mindbox-fillup-last-name').val(response.data.lastName);
                    $('#mindbox-fillup-date').val(response.data.birthDate);
                    $('#mindbox-gender_' + $genderId).attr('checked', 'checked');
                } else {
                    localStorage.setItem('phone', '');
                    window.location.reload()
                }

            }
        });
    });

    $('#mindbox-fillup-profile').on('submit', function (e) {
        e.preventDefault();
        let $targetForLoader = $(this);
        loader.show($targetForLoader);

        let fields = {
            'NAME': $('#mindbox-fillup-name').val(),
            'EMAIL': $('#mindbox-fillup-email').val(),
            'LAST_NAME': $('#mindbox-fillup-last-name').val(),
            'PERSONAL_PHONE': $('#mindbox-fillup-phone').val(),
            'PERSONAL_BIRTHDAY': $('#mindbox-fillup-date').val(),
            'PASSWORD': $('#mindbox-fillup-password').val(),
            'CONFIRM_PASSWORD': $('#mindbox-fillup-password').val(),
            'PERSONAL_GENDER': $("input[name='mindbox-fillup-gender']:checked").val()
        };

        for (let fieldKey in fields) {
            if (fields[fieldKey] === null || fields[fieldKey] === undefined) {
                delete fields[fieldKey];
            }
        }

        if ($('#mindbox--captcha_sid').val() !== undefined && $('#mindbox--captcha_word').val() !== undefined) {
            fields['captcha_word'] = $('#mindbox--captcha_word').val();
            fields['captcha_sid'] = $('#mindbox--captcha_sid').val();
        } else {
            fields['captcha_word'] = '';
            fields['captcha_sid'] = 0;
        }

        var request = BX.ajax.runComponentAction('mindbox:auth.sms', 'fillup', {
            mode:'class',
            data: {
                fields: fields
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoader);
            var data = response.data;

            if (data.type === 'error') {
                $error.html(data.message);
                $error.show();
                
                captchaReload();
            } else if (data.type === 'validation errors') {
                for (var error of data.errors) {
                    var $field = validationMap[error.location];
                    $field.text(error.message);
                    $field.closest($('.form-group')).addClass('has-error');
                }
                
                captchaReload();
            } else if (data.type === 'success') {
               window.location.reload()
            }
        });
    })
});