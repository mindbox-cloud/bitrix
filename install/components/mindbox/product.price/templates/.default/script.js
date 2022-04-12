if (typeof window.loadMindboxProductPriceComponent === 'undefined') {
  window.loadMindboxProductPriceComponent = true;

  document.addEventListener('DOMContentLoaded', function(){
    let productPriceItems = document.querySelectorAll('.mindbox-product-price');

    productPriceItems.forEach(function (item) {
      item.addEventListener("changeProductPrice", function(event) {
        let target = event.target;
        let productParams = event.detail;
        let request = BX.ajax.runComponentAction('mindbox:product.price', 'changeProduct', {
          mode:'class',
          data: {
            productId: productParams.productId,
            price: productParams.price,

          }
        });

        request.then(function (response) {

          if (response.data.type === 'success') {
            if (response.data.return.hasOwnProperty('MINDBOX_PRICE')) {
              target.querySelector('.mindbox-product-price__price').innerText = response.data.return['MINDBOX_PRICE'];
            }

            if (!response.data.return.hasOwnProperty('MINDBOX_OLD_PRICE')) {
              target.querySelector('.mindbox-product-price__discount').innerText = '';
            } else {
              target.querySelector('.mindbox-product-price__discount').innerText = response.data.return['MINDBOX_OLD_PRICE'];
            }
          }
        });
      });
    });
  });
}
