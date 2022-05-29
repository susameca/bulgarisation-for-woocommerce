=== Bulgarisation for WooCommerce ===
Contributors: autopolisbg
Tags: e-commerce, nra, nap, nekorekten.com, bulgaria, bulgarisation, invoice, woocommerce, econt
Requires at least: 5.3
Tested up to: 6
Donate link: https://revolut.me/tihomi9gj5
Requires PHP: 7.4
Stable tag: 2.2.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Всичко необходимо за вашият онлайн магазин на WooCommerce да работи в България и според Българските разпоредби. Включва облекчен режим за Наредба - H-18 и методи за доставка с Еконт.

== Description ==

Този плъгин добавя възможност за:

*   Генериране на одиторски XML файл съобразно Наредба - H-18. 
*   Генериране на документ за поръчката. 
*   Генериране на фактури за поръчката. 
*   Генериране на кредитно известие при върната поръчка.
*   Добавя полета за фактуриране към фирма.
*   Проверка на ДДС номер с европейската система VIES.
*   Проверка за отзиви от nekorekten.com
*   Добавена възможност за добавяне на вече създадени поръчки към одиторският файл.
*   Добавени методи за доставка с Еконт.

Плъгинът използва следните помощни библиотеки:

*   За генериране на одиторския файл - https://github.com/escapeboy/nra-audit-generator
*   За генериране на документите - https://github.com/artkonekt/pdf-invoice ( модифициран ).
*   За генериране на QR код в документите - https://github.com/chillerlan/php-qrcode
*   За валидиране на ДДС номер - https://github.com/ddeboer/vatin

=== Забележки ===

*   За да работи коректно плъгина моля попълнете всички задължителни полета, включително настройките за всеки платежен метод.
*   При нужда свържете се с вашият счетоводител, ако не сте сигурни за някое от полетата.
*   За да използвате отзивите от nekorekten.com трябва да включите опцията в главните настройки на плъгина и да добавите API ключ в новопоявилия се таб.
*   За да използвате методи на доставка с Еконт, трябва да включите опцията в главните настройки на плъгина. След запазване и презареждане на страницата ще се появи нов таб с настройките за Еконт. След това добавете желаните методи за доставка ( за адрес и офис трябва да имате 2 метода за доставка ) в зоните за доставка - WooCommerce >> Настройки >> Доставка >> Зони за доставка.

[Facebook група](https://www.facebook.com/groups/bulgarisationforwoocommerce/)

== Frequently Asked Questions ==

= Може ли да се допълва плъгина =

Да, плъгина е разработен с мисълта за лесно добавяне на допълнителни функционалности.

= Може ли да се превежда на други езици =

Да, текстовете на плъгина са написани на английски и съобразено с начина на превеждане на текстове в WordPress.

== Upgrade Notice ==

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

= 2.2.3 =
Econt: Fix calculation and generation for София-Град
Econt: Fix cd amount when taxes are enabled
Invoice: Force order documents to be attached to mails
Change revolute link

= 2.2.2 =
Invoice: Fix default vat
invoice: Include order fees
Econt: Fix calculation on iOS
Econt: Add default weight to label
Econt: Add option to force variation data in label print

= 2.2.1 =
Add option to choose when to generate documents
Econt: Add shipment status 
Econt: Fix checkout when `other` is missing

= 2.2.0 =
Fix generation of refunded documents
Add Econt shipping methods

= 2.1.3 =
Fix documents generation for subscriptions.
Fix shipping taxes in pdf files.
Add ability to generate documents for old orders ( orders that were created before the plugin is installed ).
Improved export process to escape from errors in the file.
Add support for sequential/custom order number plugins.

= 2.1.2 =
Improved export process to escape from errors.
Improved export of refunded orders. Single item refunds now are in the export file. All refunded orders are with parent order ID.
Add filters for order ID in export xml file. `woo_bg/admin/export/refunded_order_id` and `woo_bg/admin/export/order_id`.
Add order invoice files in the customer orders at `My Account` page.

= 2.1.1 =
Added images to nekorekte.com reports

= 2.1.0 =
Added option to disable checkout fields
Added option for requirement of the VAT field
Added option for nekorekte.com column at orders list

= 2.0.0 =
Added nekorekten.com reports
Exlude orders from XML if the payment method was not found or disabled.

= 1.1.6 =
CRITICAL UPDATE: Fix products in invoice pdf

= 1.1.5 =
Fix missing orders in the .xml from last day of the month.
Add automatically attached invoice pdf.

= 1.1.4 =
CRITICAL UPDATE: Fix generation of xml when shippings are included in the documents

= 1.1.3 =
CRITICAL UPDATE: Fix checkout on unchecked company invoice - VAT number validation.

= 1.1.2 =
CRITICAL UPDATE: Fix checkout on unchecked company invoice.

= 1.1.1 =
Remove some descriptions.

= 1.1.0 =
Add checkout fields for company invoices.
Add VIES validation for VAT number.
Fix invoices with shippings.

= 1.0.0 =
Initial version.
