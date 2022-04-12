$(document).ready(function () {
    var limitMessage = '';

    let request = BX.ajax.runComponentAction('mindbox:cart', 'getBalance', {
        mode: 'class',
    });

    request.then(function (response) {
        let balance = response.data.balance;

        $('#mindbox-cart-bonus').data('limit', balance);

        limitMessage = response.data.message;

        $('#mindbox-available_bonuses').text(balance);
    });

    $('#mindbox-promocode-submit').on('click', function () {
        let $targetForLoadder = $('#mindbox-cart');

        loader.show($targetForLoadder);

        let promoInput = $('#mindbox-cart-promo');
        let code = promoInput.val();

        if (!$.trim(promoInput.val())) {
            return;
        }

        let request = BX.ajax.runComponentAction('mindbox:cart', 'applyCode', {
            mode: 'class',
            data: {
                code: code
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoadder);

            if (response.data.type === 'error') {
                $('#mindbox-cart-success').hide();

                $('#mindbox-cart-error').text(response.data.message).show();
            } else if (response.data.type === 'success') {
                $('#mindbox-cart-error').hide();

                $('#mindbox-cart-success').text(response.data.message).show();

                setTimeout(function () {
                    window.location.reload()
                }, 500);

            }
        })
    });

    $('#mindbox-pay-bonuses').on('click', function () {
        let bonuses = $('#mindbox-cart-bonus').val();
        let limit = $('#mindbox-cart-bonus').data('limit');

        if (bonuses === '') {
            bonuses = 0;
        }

        if (bonuses > limit) {
            $('#mindbox-cart-success').hide();

            $('#mindbox-cart-error').text(limitMessage).show();

            return;
        }

        let $targetForLoadder = $('#mindbox-cart');

        loader.show($targetForLoadder);

        let request = BX.ajax.runComponentAction('mindbox:cart', 'applyBonuses', {
            mode: 'class',
            data: {
                bonuses: bonuses
            }
        });

        request.then(function (response) {
            loader.hide($targetForLoadder);

            if (response.data.type === 'success') {
                window.location.reload();
            }
        });
    });

    $('#mindbox-cart-clear-bonus').on('click', function () {
        $('#mindbox-pay-bonuses').trigger('click');
    });

    $('#mindbox-clear-code').on('click', function () {
        $('#mindbox-cart-error').hide();
        $('#mindbox-promocode-submit').trigger('click');
    })
});
