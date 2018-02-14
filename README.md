пока в стадии разработки
not ready
css cleaner

remove unused classes from css

удаляет ненужные классы из css

Что касается переопределения классов. Допустим, есть 2 css файла 
template.css & custom.css, где custom.css расширяет template.css и подключен за ним.
в них есть одинаковые классы
template.css >> .header{font-size:20px;} & custom.css >> header{font-size:22px;}

На сайте будет работать класс header{font-size:22px;} , но в итоговом файле, который будет сформировн после отработки этого скрипта .header{font-size:22px} не переопределит .header{font-size:20px;},
так как файлы затягиваются по алфавиту, а custom раньше template.
Это вопрос семантики, скрипт не знает, какой файл подключен раньше, вытаскивать позицию из head не имееет большого смысла, т.к. там может быть уже минимизированная сборка.
Следовательно, если делать копию папки, в которой лежат нужные css файлы и указывать путь до нее, то оптимальносделать так, чтобы по алфавиту переопределяющие css файлы стояли позже тех, которых они переопределяют, т.е. измнить имя custom.css на zcustom.css или что-то вроде. Это не повлият на работу скрипта, а итоговый файл будет иметь правильную структуру.




Опции находятся в class CSSConfig
Options in class CSSConfig

 * есть ли .html у адреса
 * have .html in url ?

protected $urlWithoutHTML = true;

 * путь к папке, где лежат css файлы
 * way to the folder with css files

protected $customPath = __DIR__.'/assets/template/';

 * название папки, где лежат js скрипты
 * name of the folder with js scripts

protected $jsFolder = 'js';

 * название папки, где лежат css. Оптимально сделать копию папки, чтобы не чистить такие bootstrap.css & css, которые идут вместе с подключаемыми библиотеками
 * name of the folder with css files. The good way is copy css folder without bootstrap.css and css from outer js libs

protected $cssFolder = 'css2';

 * адрес главной страницы сайта
 * main page url

protected $mainPageLink = 'http://demo4.ru/';

 * название файла, которые получится на выходе
 * name of the output file

protected $resultCSS = 'result.css';


 * удалять ли комментарии
 * is comments need to delete? 

protected $cleanComments = false;


 * минимизируем выходной css файл в 1 строку
 * minify output css in one string

protected $minifyAllInOneString = false;


 * минимизируем выходной css по шаблону 1 класс 1 строка
 * minify output css by pattern 1 class = 1 string

protected $minifyOneClassOneString = false;