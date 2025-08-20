=== Bulgarisation for WooCommerce ===
Contributors: autopolisbg
Tags: woocommerce, e-commerce, invoice, shipping, bulgaria
Requires at least: 5.3
Tested up to: 6.8
Donate link: https://revolut.me/tihomi9gj5
Requires PHP: 7.4
Stable tag: 3.4.13
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Всичко необходимо за вашият онлайн магазин за България. Включва облекчен режим за Наредба - H-18 и методи за доставка с Еконт, CVC и Спиди.

== Description ==

Този плъгин добавя възможност за:

*   Генериране на одиторски XML файл съобразно Наредба - H-18. 
*   Генериране на документ за поръчката. 
*   Генериране на фактури за поръчката. 
*   Генериране на кредитно известие при върната поръчка.
*   Генериране на експорт файл с фактури и кредитни известия за Microinvest Delta
*   Добавя полета за фактуриране към фирма.
*   Проверка на ДДС номер с европейската система VIES.
*   Проверка за отзиви от nekorekten.com
*   Добавена възможност за добавяне на вече създадени поръчки към одиторският файл.
*   Добавени методи за доставка с [Еконт](https://www.econt.com/).
*   Добавени методи за доставка със [BOX NOW](https://boxnow.bg/e-shops).
*   Добавени методи за доставка със [Спиди](https://speedy.bg/).
*   Добавени методи за доставка със [CVC](https://cvc.bg/).
*   Добавена възможност за показване на цените в две валути BGN/EUR.

Плъгинът използва следните помощни библиотеки:

*   За генериране на одиторския файл - https://github.com/escapeboy/nra-audit-generator ( модифициран )
*   За генериране на QR код в документите - https://github.com/chillerlan/php-qrcode
*   За валидиране на ДДС номер - https://github.com/ddeboer/vatin

=== Забележки ===

*   За да работи коректно плъгина моля попълнете всички задължителни полета, включително настройките за всеки платежен метод.
*   При нужда свържете се с вашият счетоводител, ако не сте сигурни за някое от полетата.
*   За да използвате отзивите от nekorekten.com трябва да включите опцията в главните настройки на плъгина и да добавите API ключ в новопоявилия се таб.
*   За да използвате методи на доставка с Еконт/BOX NOW/CVC/Спиди, трябва да включите опцията в главните настройки на плъгина. След запазване и презареждане на страницата ще се появи нов таб с настройките за Еконт/BOX NOW/CVC/Спиди. След това добавете желаните методи за доставка ( за адрес и офис трябва да имате 2 метода за доставка ) в зоните за доставка - WooCommerce >> Настройки >> Доставка >> Зони за доставка.

[Facebook група](https://www.facebook.com/groups/bulgarisationforwoocommerce/)

== Frequently Asked Questions ==

= Може ли да се допълва плъгина =

Да, плъгина е разработен с мисълта за лесно добавяне на допълнителни функционалности.

= Може ли да се превежда на други езици =

Да, текстовете на плъгина са написани на английски и съобразено с начина на превеждане на текстове в WordPress.

== Screenshots ==

1. Страница за експорт
2. Страница за настройки
3. Бутони за PDF документи
4. Колона със статус от nekorekten.com
5. Отзиви в страницата на поръчката от nekorekten.com 
6. Еконт Настройки
7. Метод за доставка с Еконт
8. Избор на метод за доставка

== Changelog ==

= 3.4.13 = 
Speedy: Optimize clients objects
Couriers: Optimize additional labels
Invoice: Optimize VAT field

= 3.4.12 = 
Speedy: Add ability to change "send from" options in order settings
Econt: Add ability to change "send from" options in order settings
Multi_Currency: Fix double second price in documents
Speedy: Delete fiscal items if option is disabled
PDF: Optimize view file

= 3.4.11 = 
Speedy: Label Optimizations
Dual price: Ajax Optimizations
Dual price: CURCY compatibility fix

= 3.4.10 = 
Global urgent update

= 3.4.9 = 
Couriers: Fix attributes in description

= 3.4.8 = 
Dual price: Optimizations
Couriers: Change the way attributes are added in description
Invoices: Add filter for single order item in documents

= 3.4.7 = 
Dual price: Optimizations
Econt: Fix send from office, optimizations
NRA Export: Optimization
Admin:Update tabs to match new WC styles

= 3.4.6 = 
Dual price: Optimizations

= 3.4.5 = 
Dual price optimizations
Add information in tabs
BOX NOW: Add dismiss button to message

= 3.4.4 = 
Documents: Fix documents logo/qr to be able to add/remove/edit them
Invoice: Optimize document number generation
Invoice: Optimize invoice date
Invoice: Add invoice due date option
Econt: Optimizations
Speedy: Optimizations

= 3.4.3 = 
Econt: Optimizations

= 3.4.2 = 
Econt, Speedy : Optimize admin office/address fields
Speedy: Optimize GR shipping to address
Change QR generation to print img with base64
Remove small tags from Multi_Currency
Invoices: Optimize dual currency on total, remove old files on regenerations
Add description for multi currency support

= 3.4.0 = 
Add BGN/EUR dual price
Speedy: Optimizations
BOX NOW: Options for box size
Econt: Add option for partial delivery
Econt, Speedy: Optimizations