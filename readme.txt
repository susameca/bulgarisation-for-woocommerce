=== Bulgarisation for WooCommerce ===
Contributors: autopolisbg
Tags: woocommerce, e-commerce, invoice, shipping, bulgaria
Requires at least: 5.3
Tested up to: 6.6
Donate link: https://revolut.me/tihomi9gj5
Requires PHP: 7.4
Stable tag: 3.0.30
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
*   Добавени методи за доставка със [CVC](https://cvc.bg/).
*   Добавени методи за доставка със [Спиди](https://speedy.bg/).

Плъгинът използва следните помощни библиотеки:

*   За генериране на одиторския файл - https://github.com/escapeboy/nra-audit-generator ( модифициран )
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

= 3.0.30 =
Econt: Optimizations
Couriers: Add action after generating labels
Invoices: Remove generating doc number on pro forma

= 3.0.29 =
Econt: Optimizations

= 3.0.28 =
Speedy: Optimizations
Couriers: Add filter for offices
NRA: Optimize export

= 3.0.27 =
Speedy: Optimizations
Speedy: Add cash receipt for COD
Econt: Optimizations
Documents: Add vat group
NRA Export: Add additional filters
Update admin order columns

= 3.0.26 =
NRA: Optimizations
Couriers: Phone escape optimization

= 3.0.25 =
Econt: Optimize quarters
Fix microinvest export for Credit notice
Documents: Add separate numeration for invoices
NRA: Optimizations

= 3.0.24 =
Econt: Optimizations
Couriers: Add tracking number to customer_completed_order email

= 3.0.23 =
Econt: Fix to address method

= 3.0.22 =
Econt: Remove instructions
Econt: Optimize to address
Couriers: Optimize to office
Couriers: Don't update price if payment method is not COD
Invoices: Optimize proforma logic

= 3.0.21 =
Econt: Optimize return payment
Econt: Add packing list or invoice number option
Speedy: Add company name to label
Couriers: Optimize validations

= 3.0.20 =
Speedy:Add more print variants
Couriers: Add column for generating label

= 3.0.19 =
Couriers: Optimizations
Couriers: Add field for label description in admin panel
Add new plugin headers
Global optimizations

= 3.0.18 =
Speedy: Add description for credentials
Couriers: Add action before calculation
Small optimizations

= 3.0.17 =
Econt: Office optimizations
Econt: General optimizations
Couriers: Errors optimizations
Fix some escapings

= 3.0.16 =
General optimizations
Econt: Optimizations

= 3.0.15 =
Add missing nonces

= 3.0.14 =
Econt: Optimizations
Speedy: Optimizations
Add freeshipping coupon to work with WooBG shipping methods
Invoices: Optimizations
Invoices bulk generations fixes

= 3.0.13 =
Nra: Optimizations
Econt, Speedy: Office locator optimizations
Change documents total to total amount
Change checkout required fields validation priority
Add bulk documents regeneration

= 3.0.12 =
NRA Export: Optimizations 
Invoice: Optimizations 
Added pro forma generation
Econt: Optimizations

= 3.0.11 =
Speedy: Optimizations
Delta: Optimizations

= 3.0.10 =
Speedy: Optimizations
Invoices: Optimizations
Add tab with information for PRO version
Shipping methods: Optimizations

= 3.0.9 =
Speedy:Optimizations
CVC:Optimizations
Shipping methods: optimizations

= 3.0.8 =
Invoices: Add option for total in text after order table
Invoices: Optimization
Econt, Speedy: Optimizations
Checkout: Divi fix
Nekorekten: Fix check after order is made
Minor optimizations

= 3.0.7 =
Speedy, Econt: Optimizations
NRA Export: Optimizations

= 3.0.6 =
Speedy, Econt: Optimizations

= 3.0.5 =
Speedy, Econt: Admin optimizations
Invoices: Optimizations

= 3.0.4 =
CVC: Fix address
Speedy, Econt, CVC: Optimize COD
Optimize NRA export
Optimize cities transliteration

= 3.0.3 =
Econt: Update office locator
Speedy, Econt, CVC: Optimize label generation
Documents: Improve functionality
Optimize product additional fields
Optimize fields in checkout

= 3.0.2 =
Documents: Improve functionality

= 3.0.1 =
Speedy: Optimize label
Speedy/Econt: Option for removing APT from shipping methods
Documents: Optimizations

= 3.0.0 =
Settings: Change main settings functionality
Refactoring: Documents, Export
Documents: New template with ability for custom integration
Documents: Separate options for N18 and invoices

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