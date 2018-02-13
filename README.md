css cleaner

remove unused classes from css

удаляет ненужные классы из css


/**
 * @var bool
 * есть ли .html у адреса
 * have .html in url ?
 */
protected $urlWithoutHTML = true;
/**
 * @var string
 * путь к папке, где лежат css файлы
 * way to the folder with css files
 */
protected $customPath = __DIR__.'/assets/template/';
/**
 * @var string
 * название папки, где лежат js скрипты
 * name of the folder with js scripts
 */
protected $jsFolder = 'js';
/**
 * @var string
 * название папки, где лежат css. Оптимально сделать копию папки, чтобы не чистить такие bootstrap.css & css, которые идут вместе с подключаемыми библиотеками
 * name of the folder with css files. The good way is copy css folder without bootstrap.css and css from outer js libs
 */
protected $cssFolder = 'css2';
/**
 * @var string
 * адрес главной страницы сайта
 * main page url
 */
protected $mainPageLink = 'http://demo4.ru/';
/**
 * @var string
 * название файла, которые получится на выходе
 * name of the output file
 */
protected $resultCSS = 'result.css';
/**
 * удалять ли комментарии
 * is comments need to delete? 
 * @var bool
 */
protected $cleanComments = false;
/**
 * минимизируем выходной css файл в 1 строку
 * minify output css in one string
 * @var bool
 */
protected $minifyAllInOneString = false;
/**
 * минимизируем выходной css по шаблону 1 класс 1 строка
 * minify output css by pattern 1 class = 1 string
 * @var bool
 */
protected $minifyOneClassOneString = false;