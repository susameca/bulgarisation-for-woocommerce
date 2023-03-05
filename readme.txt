=== Bulgarisation for WooCommerce ===
Contributors: autopolisbg
Tags: woocommerce, e-commerce, invoice, shipping, bulgaria, bulgarisation, nra
Requires at least: 5.3
Tested up to: 6.1.1
Donate link: https://revolut.me/tihomi9gj5
Requires PHP: 7.4
Stable tag: 2.4.7
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Всичко необходимо за вашият онлайн магазин на WooCommerce да работи в България и според Българските разпоредби. Включва облекчен режим за Наредба - H-18 и методи за доставка с Еконт, CVC и Спиди.

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
*   Добавени методи за доставка със [CVC](https://cvc.bg/).
*   Добавени методи за доставка със [Спиди](https://speedy.bg/).

Плъгинът използва следните помощни библиотеки:

*   За генериране на одиторския файл - https://github.com/escapeboy/nra-audit-generator
*   За генериране на документите - https://github.com/artkonekt/pdf-invoice ( модифициран ).
*   За генериране на QR код в документите - https://github.com/chillerlan/php-qrcode
*   За валидиране на ДДС номер - https://github.com/ddeboer/vatin

=== Забележки ===

*   За да работи коректно плъгина моля попълнете всички задължителни полета, включително настройките за всеки платежен метод.
*   При нужда свържете се с вашият счетоводител, ако не сте сигурни за някое от полетата.
*   За да използвате отзивите от nekorekten.com трябва да включите опцията в главните настройки на плъгина и да добавите API ключ в новопоявилия се таб.
*   За да използвате методи на доставка с Еконт/CVC/Спиди, трябва да включите опцията в главните настройки на плъгина. След запазване и презареждане на страницата ще се появи нов таб с настройките за Еконт/CVC/Спиди. След това добавете желаните методи за доставка ( за адрес и офис трябва да имате 2 метода за доставка ) в зоните за доставка - WooCommerce >> Настройки >> Доставка >> Зони за доставка.

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

= 2.4.7 =
Speedy: Optimize address widget
Shipping: Optimize office widgets

= 2.4.6 =
Shipping: Optimize widgets init
Shipping: Optimize package totals
Shipping: Optimize office widgets
Shipping: Optimize address widgets

= 2.4.5 =
Speedy: Fix max contents length
Speedy: Optimize address generation
Speedy/Econt/CVC: Increase debounce on address fields
Speedy/Econt/CVC: Office optimizations
Speedy/Econt/CVC: Overall optimizations
Speedy/Econt: Fix office locators on mobile

= 2.4.4 =
Shipping methods: Improve calculations

= 2.4.3 =
Speedy: Fix cron job
Speedy: Add option for PPP
CVC: Change PPP option
CVC/Speedy: Add filters for shipping rates
Settings: Add true/false field type
Invoice: Improves

= 2.4.2 =
Econt/Speedy/CVC: Optimize widgets rendering
Econt/Speedy/CVC: Fix calculation if country is disabled
Econt/Speedy: Fix shipping to APS
Econt/Speedy: Fix calculation with services ( with payment different from COD )
Speedy: Optimize shipping to small towns with no streets in API

= 2.4.1 =
Speedy: Fix returning all cities by region

= 2.4.0 =
Add Speedy shipping method
Econt: Optimize address search

= 2.3.4 =
Invoice: Fix generation

= 2.3.3 =
Econt: Fix receiver city
Econt: Fix save office after choose from the office locator
Econt: Optimize shipping to office method
Admin: Fix clearing correct cache
CVC: Optimize to address delivery
CVC: Update send to address

= 2.3.2 =
Econt: Fix send from office

= 2.3.1 =
Econt: Add support for multiple profiles
Econt: Add field for declared value 
CVC: Add field for declared value 
CVC: Change offices functionality
CVC: Fix send from hub
CVC: Add status information
CVC: Remove office locator button for cvc

= 2.3.0 =
Econt: Add declared value functionality
Add CVC shipping method

= 2.2.15 =
NRA Export: Improve functionality

= 2.2.14 =
NRA Export: Improve functionality

= 2.2.13 =
Econt: Optimize label filters

= 2.2.12 =
Add support for HPOS
Econt: Add credentials validation message
Econt: Change dev password
Econt: Fix shipping title on alternative layout

= 2.2.11 =
NRA Export: Improve functionality
Improve options

= 2.2.10 =
Invoice: Improve functionality
NRA Export: Improve functionality
Econt: Fix regions

= 2.2.9 =
Invoice: Improve functionality

= 2.2.8 =
Improve extending
Move generated files by the plugin in separate folder
Minor fixes
Invoice: Add filters for setTo fields
Invoice: Add filters for document titles

= 2.2.7 =
Econt: Add option to generate label after checkout
Econt: Fix CD if there is discounts
Invoice: Remove vat number from documents
Invoice: Add filter for items names
NRA Export: Fix VAT for items with different VAT
NRA Export: Add filters for item price and VAT
Fix issue with media-views.min.js
Add documents export for Microinvest Delta
Fix settings tab callback

= 2.2.6 =
Econt: Add new option to change shipping options layout in checkout
Econt: Add option to change review and test in order page
Econt: Fix label phone and names
Invoice: Add option to disable document generations
Invoice: Add new payment type for NRA
Fix error on eu vat at checkout
Optimize tabs functionality

= 2.2.5 =
Econt: Fix shipping methods not loading initially
Econt: Fix error reporting on checkout
Econt: Change matching cities and addresses
Econt: Add transliteration from latin to cyrillic

= 2.2.4 =
Invoice: Change PDF printer functionality to use .ttf font
Econt: Update checkout when change payment method
Econt: Fix calculation on checkout when sender address is in different town from the selected office of sending

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
