$(document).ready(function() {
    var $error = $('#mindbox-card-error');
    var $success = $('#mindbox-card-success');

   $('#mindbox-card-input').on('submit', function (e) {
       e.preventDefault();
       var $targetForLoader = $(this);
       loader.show($targetForLoader);

       var card = $('#mindbox-card').val();

       var request = BX.ajax.runComponentAction('mindbox:discount.card', 'sendCard', {
           mode:'class',
           data: {
               card: card
           }
       });


       request.then(function (response) {
           loader.hide($targetForLoader);
            var data = response.data;

            if(data.type === 'error') {
                $error.text(data.message);
                $error.show();
            }
            if(data.type === 'warning') {
                $error.text(data.message);
                $error.show();
            }

            if(data.type === 'success') {
                localStorage.setItem('phone', data.phone);
                $('#mindbox-card-input').hide();
                $error.hide();
                $('#mindbox-code-confirm').show();
            }
       });
   });

   $('#mindbox-code-resend').on('click', function () {
       var phone = localStorage.getItem('phone');
       var request = BX.ajax.runComponentAction('mindbox:discount.card', 'resend', {
           mode:'class',
           data: {
               phone: phone
           }
       });
   });

   $('#mindbox-code-confirm').on('submit', function (e) {
       e.preventDefault();
       var $targetForLoader = $(this);
       loader.show($targetForLoader);

       var code = $('#mindbox-code').val();
       var phone = localStorage.getItem('phone');
       var request = BX.ajax.runComponentAction('mindbox:discount.card', 'sendCode', {
           mode:'class',
           data: {
               code: code,
               phone: phone
           }
       });

       request.then(function (response) {
           loader.hide($targetForLoader);
           var data = response.data;

           if(data.type === 'error') {
               $error.text(data.message);
               $error.show();
           }

           else if(data.type ==='success') {
               $error.hide();
               $success.text(data.message);
               $success.show();

               var url = data.url;
               if(url) {
                   setTimeout(function () {
                       window.location.replace(url)
                   }, 500);
               }
           }
       })
   });

   $('#mindbox-cancel-button').on('click', function () {
       $error.hide();
       $('#mindbox-code-confirm').hide();
       $('#mindbox-card-input').show();
   })
});