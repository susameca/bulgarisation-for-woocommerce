=== Bulgarisation for WooCommerce ===
Contributors: autopolisbg
Tags: woocommerce, e-commerce, invoice, shipping, bulgaria
Requires at least: 5.3
Tested up to: 6.8
Donate link: https://revolut.me/tihomi9gj5
Requires PHP: 7.4
Stable tag: 3.4.10
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

= 3.3.4 = 
BOX NOW: Optimizations
Couriers: Optimizations
Invoices: Optimizations
Checkout: Optimizations
NRA: Update .xsd

= 3.3.3 = 
BOX NOW: Add errors when generating label
BOX NOW: Optimizations
NRA export: Optimizations
Speedy: Optimizations
Couriers: Optimizations

= 3.3.2 = 
Speedy: Optimizations
Econt: Optimizations
BOX NOW: Optimizations

= 3.3.1 =
Speedy: Fix admin generation

= 3.3.0 =
Couriers: Add label if not calculated
Couriers: Fix recalculations
Econt: Optimizations
Speedy: Optimizations

= 3.2.2 =
BOX NOW: Fix label

= 3.2.1 =
BOX NOW: Fix pricing fields

= 3.2.0 =
Add BOX NOW shipping method
Speedy: Label optimizations
Speedy: Other optimizations
Fix vies validation if number is not required
NRA: Optimizations
Invoices: Optimizations

= 3.1.3 =
Nekorekten.com: Add "Submit report" form in admin order page
Nekorekten.com: Check for reports on checkout and disable COD

= 3.1.2 =
Invoice: Fix double country code in vat number
Invoices: Fix if shipping doesn't have a price
Econt: Label Optimizations

= 3.1.1 =
Add attachments to Woo Subs customer email
Couriers: Refresh sender data on label creation
Speedy: Optimize profile pull process
Fix pro tab and validations

= 3.1.0 =
Speedy: Label optimizations
Couriers: Change price options to number field
Econt: Optimize packing list
NRA Export: Optimization

= 3.0.34 =
Econt: Fix invoice number

= 3.0.32 =
Speedy: Add ref1/2 field in admin order edit
Econt: Add invoice number field in admin order edit
Econt: Change default print label to 10x9
Couriers: Hide generate label if it's not woo_bg method
Fix warnings

= 3.0.31 =
Speedy: Optimizations
Documents: Optimizations
Update dompdf and all libraries

= 3.0.30 =
Econt: Optimizations
Couriers: Add action after generating labels
Invoices: Remove generating doc number on pro forma

= 3.0.29 =
Econt: Optimizations