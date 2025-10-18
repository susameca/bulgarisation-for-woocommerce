=== Bulgarisation for WooCommerce ===
Contributors: autopolisbg
Tags: woocommerce, e-commerce, invoice, shipping, bulgaria
Requires at least: 5.3
Tested up to: 6.8
Donate link: https://revolut.me/tihomi9gj5
Requires PHP: 7.4
Stable tag: 3.4.19
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

= 3.4.19 =
Multi Currency: Add option to convert product prices from bgn to eur.
NRA: Optimizations
Invoice PDF: Add filter for shipping items
Invoice PDF: Discounts optimizations
Invoice PDF: Order optimization
Speedy: Optimize fiscal generation

= 3.4.18 = 
Econt: Get actual option for auto size
Econt: Fix partial delivery
NRA: Fix item rate if tax is 0
Checkout: Remove required for state field
Orders documents: add meta info for each document
BOXNOW: Add option to hide method over price

= 3.4.17 = 
BOXNOW: Fix label generation
Speedy: Optimization
Invoice: PDF Optimization

= 3.4.16 = 
Checkout: Reorder states alphabetically and return required for state field.
Econt, Speedy: Add dimensions fields and automatically calculation option
BOXNOW: Optimization

= 3.4.15 = 
Couriers labels: Prevent duplication
Couriers: Change declared value as Courier option
Speedy, Econt: Add filters for label before save.

= 3.4.14 = 
Econt: Fix critical issue with fixed prices

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