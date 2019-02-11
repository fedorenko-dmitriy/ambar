﻿
SET NAMES 'utf8';


 
INSERT   INTO `users` (  `userlogin`, `userpass`, `createdon`, `active`, `email`, `acl`, `smartmenu`) VALUES(  'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 1, 'admin@admin.admin', '<detail><acltype>1</acltype><onlymy></onlymy><aclview></aclview><acledit></acledit><widgets></widgets></detail>', NULL);

 
INSERT  INTO `stores` (  `storename`, `description`) VALUES(  'Основной склад', '');

 
INSERT  INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:11:{s:8:"firmname";s:20:"Наша  фирма";s:8:"defstore";s:2:"19";s:5:"cdoll";s:1:"2";s:5:"ceuro";s:1:"5";s:4:"crub";s:3:"0.4";s:6:"price1";s:18:"Розничная";s:6:"price2";s:14:"Оптовая";s:6:"price3";s:0:"";s:6:"price4";s:0:"";s:6:"price5";s:0:"";s:6:"useval";b:0;}');
INSERT  INTO `options` (`optname`, `optvalue`) VALUES('shop', 'a:3:{s:7:"defcust";s:1:"2";s:8:"defstore";s:2:"19";s:12:"defpricetype";s:6:"price2";}');

INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(1, 4, 'Склады', 'StoreList', 'Товары', '', 0, 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товары', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(4, 4, 'Категории товаров', 'CategoryList', 'Товары', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(5, 4, 'Контрагенты', 'CustomerList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', 'Закупки', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', 'Продажи', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(8, 3, 'Журнал документов', 'DocList', '', '', 0, 1, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(9, 3, 'Товары на складе', 'StockList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(10, 1, 'Гарантийный талон', 'Warranty', '', '', 1, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(11, 1, 'Перемещение товара', 'MoveItem', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(12, 2, 'Движение по складу', 'ItemActivity', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(13, 2, 'ABC анализ', 'ABC', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(15, 1, 'Акт выполненных работ', 'ServiceAct', 'Продажи', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', 'Продажи', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(18, 3, 'Работы, наряды', 'TaskList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(19, 1, 'Наряд', 'Task', '', 'Наряд на выполнение работы, задачи', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(20, 2, 'Оплата по нарядам', 'EmpTask', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(21, 2, 'Закупки', 'Income', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(22, 2, 'Продажи', 'Outcome', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(23, 5, 'Бренды', 'Manufacturers', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(24, 5, 'Группы товаров', 'GroupList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(26, 5, 'Товары', 'ProductList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(27, 3, 'Счет фактура', 'OrderList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(28, 1, 'Заказ покупателя', 'Order', 'Продажи', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(30, 1, 'Оприходование  с  производства', 'ProdReceipt', 'Производство', 'Оприходование готовой продукции и полуфабрикатов  с  производства  на  склад.  ', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(31, 1, 'Списание на  производство', 'ProdIssue', 'Производство', 'Передача  на производство  материалов  и комплектующий.', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(32, 2, 'Отчет по производству', 'Prod', 'Производство', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(33, 4, 'Производственные участвки', 'ProdAreaList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(38, 1, 'Заявка  поставщику', 'OrderCust', 'Закупки', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(35, 3, 'Продажи', 'GIList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(36, 4, 'Оборудование', 'EqList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(37, 3, 'Закупки', 'GRList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`, `order`) VALUES(39, 3, 'Заявки поставщикам', 'OrderCustList', '', '', 0, 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `notes`, `disabled`, `smartmenu`) VALUES(40, 2, 'Прайс', 'Price', 'Склад', '', 0, 0);


INSERT INTO `currencies` (`currency_id`, `currency_name`, `currency_main_name`, `currency_coin_name`, `currency_symbol`, `iso_code`, `iso_number`) VALUES(1, 'Гривна', 'грн', 'коп', '₴', 'UAH', 980);
INSERT INTO `currencies` (`currency_id`, `currency_name`, `currency_main_name`, `currency_coin_name`, `currency_symbol`, `iso_code`, `iso_number`) VALUES(2, 'Доллар США', '', '', '$', 'USD', 840);
INSERT INTO `currencies` (`currency_id`, `currency_name`, `currency_main_name`, `currency_coin_name`, `currency_symbol`, `iso_code`, `iso_number`) VALUES(3, 'Евро', '', '', '€', 'EUR', 978);
INSERT INTO `currencies` (`currency_id`, `currency_name`, `currency_main_name`, `currency_coin_name`, `currency_symbol`, `iso_code`, `iso_number`) VALUES(4, 'Российский рубль', '', '', '₽', 'RUR', 643);
INSERT INTO `currencies` (`currency_id`, `currency_name`, `currency_main_name`, `currency_coin_name`, `currency_symbol`, `iso_code`, `iso_number`) VALUES(5, 'Английский фунт', '', '', '£', 'GBP', 826);
INSERT INTO `currencies` (`currency_id`, `currency_name`, `currency_main_name`, `currency_coin_name`, `currency_symbol`, `iso_code`, `iso_number`) VALUES(6, 'Польский злотый', '', '', '', 'PLN', 985);


INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(1, 'шт.', 'штука', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(2, 'уп.', 'упаковка', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(3, 'ящ.', 'ящик', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(4, 'кг.', 'киллограмм', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(5, 'т.', 'тонна', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(6, 'куб.м.', 'кубический метр', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(7, 'л.', 'литр', '');
INSERT INTO `messures` (`messure_id`, `messure_short_name`, `currency_main_name`, `messure_second_name`) VALUES(7, 'ч.', 'час', '');
