DEPRECATED use https://github.com/yandex-money/yandex-money-cms-prestashop

Яндекс касса для prestashop v1.6
=======
yacassa

Более подробную информацию по данной системе можете почитать здесь: https://money.yandex.ru/start/#1

Этот модуль позволяет принимать платежи через яндекс кассу, с яндекс денег, вебмани, банковской карты, мобильных телефонов, терминала, мобильного терминала, сбербанка.
Сначала вам необходимо оставить заявку на https://money.yandex.ru/joinups
Потом !!! подключите SSL на вашем сервере !!!
Когда Вы получите техническую анкету, используйте данные в настройках модуля в самом верху.
После получения идентификаторов в настройках включите наладочный режим, и введите их.

И немного про необходимый сертификат. Все немного проще чем многие представляют себе:

Самый простой и бесплатный способ — это использовать, так называемый, самоподписной сертификат (self-signed), который можно сгенерировать прямо на веб-сервере. К слову во всех самых популярных панелях управления хостингом (Cpanel, ISPmanager, Directadmin) эта возможность доступна по умолчанию.
Плюс самоподписного сертификата — это его цена, точнее ее отсутствие, так как вы не платите ни копейки, за такой сертификат. А вот из минусов — это то, что на такой сертификат все браузеры будут выдавать ошибку, с предупреждением, что сайт не проверен. <br />
Нам это без разницы потому что по этому адресу будет заходить чисто яндекс и проверять контрольные суммы и все, пользователь туда заходить не будет. Активировать в самом престашопе его не надо, главное чтобы ссылка для яндекса работала с адрессом https.
