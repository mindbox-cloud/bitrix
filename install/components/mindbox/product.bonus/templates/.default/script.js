if (typeof window.loadMindboxProductBonusComponent === 'undefined') {
  window.loadMindboxProductBonusComponent = true;

  document.addEventListener('DOMContentLoaded', function(){
    let productPriceItems = document.querySelectorAll('.mindbox-product-bonus');

    productPriceItems.forEach(function (item) {
      item.addEventListener("changeProductBonus", function(event) {
        let target = event.target;
        let productParams = event.detail;
        let request = BX.ajax.runComponentAction('mindbox:product.bonus', 'changeProduct', {
          mode:'class',
          data: {
            productId: productParams.productId,
            price: productParams.price,
          }
        });

        request.then(function (response) {

          if (response.data.type === 'success') {
            if (response.data.return.hasOwnProperty('MINDBOX_BONUS')) {
              target.querySelector('.mindbox-product-bonus__value').innerText = response.data.return['MINDBOX_BONUS'];
            }
          }
        });
      });
    });
  });
}
