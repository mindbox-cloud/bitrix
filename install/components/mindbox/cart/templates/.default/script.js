$(document).ready(function() {
    var $success = $('#mindbox-cart-success');
    var $error = $('#mindbox-cart-error');
    var $bonus = $('#mindbox-cart-bonus');
    var limitMessage = '';

    var request = BX.ajax.runComponentAction('mindbox:cart', 'getBalance', {
        mode:'class',
    });

    request.then(function (response) {
        var balance = response.data.balance;
        $bonus.data('limit', balance);
        limitMessage = response.data.message;
        $('#mindbox-available_bonuses').text(balance);
    });

   $('#mindbox-promocode-submit').on('click', function () {
       var $targetForLoadder = $('#mindbox-cart');
       loader.show($targetForLoadder);

       var code = $('#mindbox-cart-promo').val();
       var request = BX.ajax.runComponentAction('mindbox:cart', 'applyCode', {
           mode:'class',
           data: {
               code: code
           }
       });

       request.then(function (response) {
           loader.hide($targetForLoadder);
           var data = response.data;

           if(data.type === 'error') {
               $success.hide();
               $error.text(data.message);
               $error.show();
           }

           else if(data.type === 'success') {
               $error.hide();
               $success.text(data.message);
               $success.show();
               setTimeout(function () {
                   window.location.reload()
               }, 500);

           }
       })
   });

   $('#mindbox-pay-bonuses').on('click', function () {
       var bonuses = $bonus.val();

       if (bonuses === '') {
           bonuses = 0;
       }
       var limit = $bonus.data('limit');

       if(bonuses > limit) {
           $success.hide();
           $error.text(limitMessage);
           $error.show();

           return;
       }

       var $targetForLoadder = $('#mindbox-cart');
       loader.show($targetForLoadder);

       var request = BX.ajax.runComponentAction('mindbox:cart', 'applyBonuses', {
           mode:'class',
           data: {
               bonuses: bonuses
           }
       });

       request.then(function (response) {
           loader.hide($targetForLoadder);
           var data = response.data;
           if(data.type === 'success') {
               window.location.reload();
           }
       });
   });

    $('#mindbox-cart-clear-bonus').on('click', function () {
        $('#mindbox-pay-bonuses').trigger('click');
    });

    $('#mindbox-clear-code').on('click', function () {
        $('#mindbox-promocode-submit').trigger('click');
    })

});