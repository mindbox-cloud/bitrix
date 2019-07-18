$(document).ready(function() {
    var $message = $('#mindbox-message');
   $('#mindbox-subscribe-form').on('submit', function (e) {
       e.preventDefault();
       var $targetForLoader = $(this);
       loader.show($targetForLoader);

       var email = $('#mindbox-sub-email').val();

       var request = BX.ajax.runComponentAction('mindbox:subscription', 'subscribe', {
           mode:'class',
           data: {
               email: email
           }
       });


       request.then(function (response) {
           loader.hide($targetForLoader);
           var data =  response.data;
           if(data.type === 'success') {
               $('#mindbox-subscribe-form').hide();
               $message.text(data.message);
               $message.show();
           }

           else if(data.type === 'queue') {
               $message.text(data.message);
               $message.show();

           }
       });
   })
});
