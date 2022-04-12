$(document).ready(function() {
   $('#mindbox-card-input').on('submit', function (e) {
       e.preventDefault();
       let $targetForLoader = $(this);

       loader.show($targetForLoader);

       let card = $('#mindbox-card').val();

       let request = BX.ajax.runComponentAction('mindbox:discount.card', 'sendCard', {
           mode:'class',
           data: {
               card: card
           }
       });

       request.then(function (response) {
           loader.hide($targetForLoader);

            if (response.data.type === 'error') {
                $('#mindbox-card-error').text(response.data.message).show();
            }

            if (response.data.type === 'warning') {
                $('#mindbox-card-error').text(response.data.message).show();
            }

            if (response.data.type === 'success') {
                localStorage.setItem('phone', response.data.phone);
                $('#mindbox-card-input').hide();
                $('#mindbox-card-error').hide();
                $('#mindbox-code-confirm').show();
            }
       });
   });

   $('#mindbox-code-resend').on('click', function () {
       let phone = localStorage.getItem('phone');
       let request = BX.ajax.runComponentAction('mindbox:discount.card', 'resend', {
           mode:'class',
           data: {
               phone: phone
           }
       });
   });

   $('#mindbox-code-confirm').on('submit', function (e) {
       e.preventDefault();

       let $targetForLoader = $(this);

       loader.show($targetForLoader);

       let code = $('#mindbox-code').val();
       let phone = localStorage.getItem('phone');
       let request = BX.ajax.runComponentAction('mindbox:discount.card', 'sendCode', {
           mode:'class',
           data: {
               code: code,
               phone: phone
           }
       });

       request.then(function (response) {
           loader.hide($targetForLoader);

           if(response.data.type === 'error') {
               $('#mindbox-card-error').text(response.data.message).show();
           } else if (response.data.type ==='success') {
               $('#mindbox-card-error').hide();
               $('#mindbox-card-success').text(response.data.message).show();

               if (response.data.url) {
                   setTimeout(function () {
                       window.location.replace(response.data.url)
                   }, 500);
               }
           }
       })
   });

   $('#mindbox-cancel-button').on('click', function () {
       $('#mindbox-card-error').hide();
       $('#mindbox-code-confirm').hide();
       $('#mindbox-card-input').show();
   })
});