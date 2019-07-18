$(function () {
    var $bonusMore  = $('#mindbox-bonus-more');

   $('#mindbox-bonus-history--load-more').on('click', function () {
       var $targetForLoader = $bonusMore;
       loader.show($targetForLoader);

       var page = parseInt($bonusMore.data('page'))  + 1;

       var request = BX.ajax.runComponentAction('mindbox:bonus.history', 'page', {
           mode:'class',
           data: {
               page: page
           }
       });

       request.then(function(response) {
           loader.hide($targetForLoader);
           var data = response.data;

           if(data.type === 'error') {
               $bonusMore.hide();
           }

           else if(data.type === 'success') {
               $bonusMore.data('page', data.page);
               var $html = data.html;

               $('#mindbox-bonus-history').append($html);

               if(data.more === false) {
                   $bonusMore.hide();
               }
           }
       });
   });

});