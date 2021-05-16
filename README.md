# Модуль Mindbox для Bitrix Framework
Внимание! Полная работоспособность модуля гарантирована на штатном функционале 1С-Битрикс старше 17.5.10.

Для корректной работы модуля рекомендуем использовать ядро D7 при изменении объектов корзины и заказа.

### [Описание установки модуля](https://developers.mindbox.ru/docs/module-bitrix)


### В процессе установки:

* Будут навешаны обработчики на следующие события

| Событие                | Обработчик                     |
| :--------------------: | :----------------------------: |
| [OnAfterUserAuthorize](https://dev.1c-bitrix.ru/api_help/main/events/onafteruserauthorize.php) | OnAfterUserAuthorizeHandler |  |
| [OnBeforeUserRegister](https://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserregister.php) | OnBeforeUserRegisterHandler |
| [OnAfterUserRegister](https://dev.1c-bitrix.ru/api_help/main/events/onafteruserregister.php) | OnAfterUserRegisterHandler |
| [OnBeforeUserUpdate](https://dev.1c-bitrix.ru/api_help/main/events/onbeforeuserupdate.php) | OnBeforeUserUpdateHandler |
| [OnAfterUserUpdate](https://dev.1c-bitrix.ru/api_help/main/events/onafteruserupdate.php) | OnAfterUserUpdateHandler |
| [OnSaleBasketBeforeSaved](https://dev.1c-bitrix.ru/api_d7/bitrix/sale/events/basket_saved.php) | OnSaleBasketBeforeSavedHadler |
| [OnSaleOrderBeforeSaved](https://dev.1c-bitrix.ru/api_d7/bitrix/sale/events/order_saved.php) | OnSaleOrderBeforeSavedHandler |
| [OnSaleOrderSaved](https://dev.1c-bitrix.ru/api_d7/bitrix/sale/events/order_saved.php) | OnSaleOrderSavedHandler |

Исходный код всех обработчиков можно посмотреть в этом файле: ```/bitrix/modules/mindbox/lib/Event.php```

* Созданы агенты

| Агент                | Описание                     |
| :--------------------: | :----------------------------: |
| Агент выгрузки каталога | Данный агент служит для выгрузки товаров и их торговоых предложений в xml формате. Обращается к функции ```\Mindbox\YmlFeedMindbox::start();``` |
| Агент очереди | Данный агент служит для отправки запросов к Mindbox, добавленных в очередь запросов. Обращается к функции ```\Mindbox\QueueTable::start();``` |

__Примечание:__ 
1. Для проектов с большим каталогом рекомендуется перевести выгрузку каталога на крон.
2. Назавание сайта и компании в выгрузке берется из поля "Название веб-сайта" в настройках сайта.

Пример скрипта
```php
    <?php
    $_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../"); // путь к вашему DOCUMENT_ROOT
    $_SERVER["SERVER_NAME"] = "mysite.com"; // url вашего сайта без указания протокола
    
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
    
    use Bitrix\Main\Loader;
    use Bitrix\Main\LoaderException;
    use Mindbox\YmlFeedMindbox;
    
    try {
        if (!Loader::includeModule('mindbox.marketing')) {
            die();
        }
    } catch (LoaderException $e) {
        die();
    }
    YmlFeedMindbox::start();
```

Исходный код классов, с которыми работают агенты, можно посмотреть в следующих файлах: ```/bitrix/modules/mindbox/lib/YmlFeedMindbox.php```, ```/bitrix/modules/mindbox/lib/QueueTable.php```

* Созданы пользовательские поля:

| Поле                | Описание                     |
| :--------------------: | :----------------------------: |
| UF_MINDBOX_ID | Строковое поле предназначено для хранения id пользователя в mindbox. |
| UF_PHONE_CONFIRMED | Поле чекбокс предназначено для хранения информации о подтверждение пользователем телефона в mindbox. Данным полем можно воспользоваться для вывода подтверждения телефона в Вашем компоненте персональных данных. |
| UF_EMAIL_CONFIRMED | Поле чекбокс предназначено для хранения информации о подтверждение пользователем email в mindbox. Данным полем можно воспользоваться для вывода подтверждения email в Вашем компоненте персональных данных. |



### Интеграция модуля в стандартном режиме
1. Интегрируем компоненты ```catalog.tracking``` модуля mindbox. Компоненты и их шаблоны находятся в директории - ```/bitrix/components/mindbox```

### Интеграция модуля в режиме лояльности
1. Создаем хайлоадблок "Mindbox" c полями UF_BASKET_ID, UF_DISCOUNTED_PRICE  (https://i.imgur.com/DNwxnmE.png). 
2. Создаем правило работы с корзиной с названием "Mindbox". У правила снимаем галку "Прекратить дальнейшее применение правил". На вкладке "Действия и условия" добавляем действие "Применить скидку из HighLoad блока Mindbox". 
3. Интегрируем компоненты ```catalog.tracking``` модуля mindbox. При необходимости интегрируем остальные компоненты модуля. Компоненты и их шаблоны находятся в директории - ```/bitrix/components/mindbox```
4. При необходимости проводим кастомизацию шаблонов и стилей компонентов для сохранения корпоративного стиля Вашего сайта. Файлы стилей - ```/bitrix/css/mindbox```, js скрипты - ```/bitrix/js/mindbox```, Изображения - ```/bitrix/images/mindbox```
5. В стандартных шаблонах компонентов модуля подключается jQuery 1.8.3. Если на сайте используется более новая версия jQuery, рекомендуется копировать стандартные шаблоны компонентов и убрать из них подключение jQuery. ```CJSCore::Init(array('jquery'));```


### Описание компонентов модуля

1\. auth.sms - Компонент авторизации пользователя по телефону

| Параметр компонента | Описание | Значение по умолчанию |
| :-------: | :-------: | :---------------------:|
| PERSONAL_PAGE_URL   | URL личного кабинета, на которую будет совершен редирект после успешной авторизации. | / |

2\. bonus.history - Компонент выводит историю бонусных баллов mindbox.

| Параметр компонента | Описание | Значение по умолчанию |
| :-------: | :-------: | :---------------------:|
| PAGE_SIZE | Количество элементов на странице. | 5 |

3\. cart - Компонент корзины(промокоды и бонусы). Позволяет применять бонусы и промокоды к товарам в корзине.

| Параметр компонента | Описание | Значение по умолчанию |
| :-------: | :-------: | :---------------------:|
| USE_BONUSES | Использование бонусов. Если этот параметр равен N, то покупатель сможет использовать только промокоды. | Y |

4\. catalog.tracking - Данный компонент реализует обертку над [Mindbox JavaScript SDK.](https://developers.mindbox.ru/docs/%D1%82%D1%80%D0%B5%D0%BA%D0%B5%D1%80/)  
После подключения данного компонента можно отправлять данные о просмотре товаров и категорий каталога в mindbox.

Для этого используются 2 функции:  

**mindboxViewCategory**  
Пример вызова в шаблоне компонента bitrix:catalog
```php
<?php $APPLICATION->IncludeComponent('mindbox:catalog.tracking', '', []);?>
<script>
    mindboxViewCategory('<?=!empty($arCurSection['XML_ID']) ? $arCurSection['XML_ID'] : $arCurSection['ID']?>');
</script>
```
**mindboxViewProduct**  
Пример вызова в шаблоне компонента bitrix:catalog.element
```php
<?php
   $APPLICATION->IncludeComponent('mindbox:catalog.tracking', '', []);
?>
<script>
    mindboxViewProduct('<?=!empty($arResult['OFFERS'][0]['XML_ID']) ? $arResult['OFFERS'][0]['XML_ID'] : $arResult['OFFERS'][0]['ID']?>');
</script>
```

5\. discount.card - Компонент привязки дисконтной карты.

| Параметр компонента | Описание | Значение по умолчанию |
| :-------: | :-------: | :---------------------:|
| PERSONAL_PAGE_URL | URL личного кабинета, на которую будет совершен редирект после успешного привязывания карты. | / |

6\. email.confirm - Компонент подтверждения email в mindbox.  
**ВАЖНО:** Компонент необходимо расположить на странице редактирования персональных данных пользователя.

7\. order.history - Компонент выводит историю заказов mindbox.

| Параметр компонента | Описание | Значение по умолчанию |
| :-------: | :-------: | :---------------------:|
| PAGE_SIZE | Количество элементов на странице. | 5 |

8\. phone.confirm - Компонент подтверждения телефона в mindbox.

9\. sub.edit - Компонент управления подписками зарегистрированных и авторизованных пользователей.

10\. subscription - Компонент подписки на email рассылку, как для зарегистрированных, так и для анонимных пользователей.
