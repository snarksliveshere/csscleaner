<?php
ini_set('max_execution_time', 900000);
// TODO надо перебить это на генератор, чтобы память не ел
// TODO можно еще вот это добавить px){
class CSSConfig
{
    protected $urlWithoutHTML = true;
    protected $customPath = __DIR__.'/assets/template/';
    protected $jsFolder = 'js';
    protected $cssFolder = 'css2';
    protected $mainPageLink = 'http://demo4.ru/';
    protected $resultCSS = 'result.css';
    protected $counter = 0;
    /**
     * удалять ли комментарии
     * @var bool
     */
    protected $cleanComments = false;
    /**
     * минимизируем в 1 строку
     * @var bool
     */
    protected $minifyAllInOneString = false;
    /**
     * 1 класс 1 строка
     * @var bool
     */
    protected $minifyOneClassOneString = false;
}
class CleanStyle
    extends CSSConfig
{
    /**
     * @var array
     */
    protected static $jsClassesPath= [];
    /**
     * @var array
     */
    protected static $cssClassesPath = [];
    /**
     * @var array
     * @property $allJSClasses
     */
    protected $allJSClasses = [];
    protected $allCSSClasses = [];
    protected $allClasses = [];
    /**
     * @param $dirname
     * @param $ext
     */
    protected static function getAllClassesPath($dirname, $ext)
    {
        $dir = opendir($dirname);
        while (($file = readdir($dir)) !== false)
        {
            // Если файл обрабатываем его содержимое
            if($file != "." && $file != "..")
            {
                // Если имеем дело с файлом - регистрируем его
                $fullPath = $dirname."/".$file;
                if(is_file($fullPath)
                    && strpos($fullPath,'.'.$ext)) {
                    if(substr($fullPath,-2) == 'js') {
                        self::$jsClassesPath[] =$fullPath;
                    } elseif (substr($fullPath,-3) == 'css') {
                        self::$cssClassesPath[] =$fullPath;
                    }
                }
                // Если перед нами директория, вызываем рекурсивно
                if(is_dir($fullPath)) {
                    self::getAllClassesPath($fullPath,$ext);
                }
            }
        }
        // Закрываем директорию
        closedir($dir);
    }
    /**
     * @param $path
     * @return array
     */
    public function getCSSClasses()
    {
        $dirname = $this->customPath.$this->cssFolder;
        self::getAllClassesPath($dirname, 'css');
        return self::$cssClassesPath;
    }
    /**
     * @param $path
     * @return array
     */
    public function getJSClasses()
    {
        $dirname = $this->customPath.$this->jsFolder;
        self::getAllClassesPath($dirname, 'js');
        return self::$jsClassesPath;
    }
}
/**
 * looking for css classes in files with RegExp
 * Class ClassesRegular
 * Мне надо обязательно решить проблему с точками
 */
class ClassesRegular
    extends CleanStyle
{
    protected $dataClasses = [];
    /**
     * @var string
     */
    /**
     * clean css from caret
     */
    protected $cleanFromCarriagePattern = '/[\r\n]{2,}/i';

    /**
     * minify alla in one string - 2 preg
     * @var string
     */
    protected $minifyAllInOneStringFirstPattern = "/([\r\n]{1,})|(\s*?(?={))/i";
    protected $minifyRemoveSpacesPattern = '/(?<=\:)\s*?/';
    /**
     * 1 string 1 class
     * @var string
     */
    protected $oneClassOneStringPattern = "/((?<!})((\\r\\n)|(\\n)|(\\r)))/";
    /**
     * clean comments
     * @var string
     */
    protected $cleanCommentsPattern = '!/\*.*?\*/!s';
    /**
     * css REGEXP
     * @var string
     */
    protected $cssPattern = '~class=\"(?P<class>[_a-z\sA-Z1-9-]{0,})\"~';
    /**
     * @var string
     */
    protected $classInCSSPattern= '~(?P<class>[^\}\{\\\/]*)\{(\n*.*\n)?[^\}\{]*\}~';
    protected $jsPattern1 = '\$\((\'|\")(?P<selector1>\..+?)(\'|\")\)';
    /**
     * @var string
     */
    protected $jsPattern2= '(addClass|removeClass|toggleClass)\((\'|\")(?P<selector2>.+?)(\'|\")';
    /**
     * @var string
     */
    protected $jsPattern3 = 'class=\"(?P<selector3>[_a-z\sA-Z1-9-]{0,})\"';
    /**
     * @var string
     */
    protected $jsPattern4 = '\$\((\s|\"|\')(.+)(\s|\"|\')\).on\((\s|\"|\')(.+?)(\'|\"),(\s|\'|\")(?P<selector4>.+?)(\s|\'|\")';
    /**
     * @param $data
     * @return array
     */
    public function getJSFromFile($data)
    {
        $jsCommonPattern = "/(?J)({$this->jsPattern1})|({$this->jsPattern2})|({$this->jsPattern3})|({$this->jsPattern4})/";
        foreach ($data as $datum) {
            $fileData = file_get_contents($datum);
            preg_match_all($jsCommonPattern, $fileData,$tempArr);
            foreach ($tempArr as $ki => $item) {
                if (stripos($ki,'selector') === 0 ) {
                    if ($ki == 'selector2' || $ki == 'selector3') {
                        $item = array_map(
                            function($n)
                            {
                                if (strstr($n,' ')) {
                                    $servArr = explode(' ', $n);
                                    $servArr = array_map(function ($a) { return '.'.$a;}, $servArr);
                                    $n = implode(' ', $servArr);
                                    return $n;
                                } else {
                                    return '.'.$n;
                                }
                            },
                            $item);
                    }
                    if (count($this->dataClasses)) {
                        $this->dataClasses =  array_merge($this->dataClasses,$item);
                    } else {
                        $this->dataClasses = $item;
                    }
                }
            }
        }
    }
    public function cleanClasses($data)
    {
        $tempArray = [];
        foreach ($data as $datum) {
            $val = trim($datum);
            $val = str_replace('"', '', $val);
            if (empty($datum) || $datum == '.') {
                continue;
            }
            if(strpos($val, ' ')) {
                $temps  = explode(' ',$val);
                foreach ($temps as $tempum) {
                    if(strlen($tempum)!=1) {
                        $tempArray[] = $tempum;
                    }
                }
            } else {
                if(strlen($val)!=1) {
                    $tempArray[] = $val;
                }
            }
        }
        $nextArr = [];
        foreach ($tempArray as $item) {
            if (!strstr($item, '.')) {
                continue;
            }
            $item = str_replace(',','', $item);
            if (strpos($item, '.') !== 0) {
                $a = explode('.', $item);
                unset($a[0]);
                foreach ($a as $atum) {
                    $nextArr[] = $atum;
                }
            } else {
                $item = substr($item, 1);
                $nextArr[] = $item;
            }
        }
        $nextArr = array_unique($nextArr);
        $this->allJSClasses = $nextArr;
    }
}

class LinksFromSite
    extends ClassesRegular
{
    protected $patternHref = '~href="(?<link>.+?)"~';
    protected $linksFromPages = [];

    public function getAllLinks()
    {
        $resourceLink = file_get_contents($this->mainPageLink);
        preg_match_all($this->patternHref, $resourceLink,$allLinks);
        foreach ($allLinks['link'] as $allLink) {
            if ($this->urlWithoutHTML) {
                // проверяем в том числе и ошибки в коде
                if ( stristr($allLink, 'http://')
                    || stristr($allLink, 'https://')
                    || stristr($allLink, '.')
                    || strlen($allLink) < 2
                    || stristr($allLink,'>')
                    || stristr($allLink,'<')
                    || stristr($allLink,'"')
                    || stristr($allLink,'\'')
                ) {
                    continue;
                }
                $this->linksFromPages[] = $allLink;
            } else {
                if ( substr($allLink,-4) == 'html' && !stristr($allLink, 'http://') && !stristr($allLink, 'https://') ) {
                    $this->linksFromPages[] = $allLink;
                }
            }
        }
        $this->linksFromPages[] = $this->mainPageLink;
    }
}

class CSSClassesFromPages
    extends LinksFromSite
{
    protected $storageClasses = [];
    public function getClassesFromPages($links)
    {
        foreach ($links as $vi) {
            if ($vi !== $this->mainPageLink) {
                $tempResource = file_get_contents($this->mainPageLink.$vi);
            } else {
                $tempResource = file_get_contents($this->mainPageLink);
            }
            preg_match_all($this->cssPattern, $tempResource,$tempArray);
            foreach ($tempArray['class'] as $val) {
                $val = trim($val);
                // разбиваю классы, если в html указано 2 или 3 класса  - у меня есть такие вещи
                if (strlen($val) <=1) {
                    continue;
                }
                if(strpos($val, ' ')) {
                    $temps  = explode(' ',$val);
                    foreach ($temps as $vall) {
                        if (strlen($val) <=1) {
                            continue;
                        }
                        $this->storageClasses[] = $vall;
                    }
                }
                else {
                    $this->storageClasses[] = $val;
                }
            }
        }
        $this->storageClasses = array_unique($this->storageClasses);
        $this->allCSSClasses = $this->storageClasses;
    }

    public function getAllClasses()
    {
        $this->allClasses = array_merge($this->allCSSClasses,$this->allJSClasses);
        $this->allClasses = array_unique($this->allClasses);
        $this->allClasses = array_values($this->allClasses);
    }
}

class CSSWalk
    extends CSSClassesFromPages
{
    protected $lngCounter = 0;
    public function getCSS($result_class)
    {
        foreach (self::$cssClassesPath as $ki_css => $item) {
            $tempCss = 'temp_'.$ki_css.'.css';
            $cssFile = file_get_contents($item);
            preg_match_all($this->classInCSSPattern, $cssFile, $cssArray, PREG_OFFSET_CAPTURE);
            $usedClasses = [];
            $fullClasses = [];
            foreach ($result_class as $html) {
                $singleClassPattern = '/\.+\b'.$html.'\b/u';
                foreach ($cssArray['class'] as $val) {
                    if(!strpos($val[0], '#')) {
                        if(!in_array($val[0], $fullClasses)) {
                            $fullClasses[]= trim($val[0]);
                        }
                    }
                    if(preg_match($singleClassPattern, $val[0])) {
                        $usedClasses[] = trim($val[0]);
                    }
                }
            }
            $notUsedClasses = array_diff($fullClasses,$usedClasses,array(''));
            foreach ($fullClasses as $ki => $val_arr) {
                if(in_array($val_arr,$notUsedClasses) && strstr($val_arr, '.')) {
                    $searchClassPattern = '/\Q'.$val_arr.'\E(?=[\s,\z]{0,}{)/u';
                    if (file_exists($tempCss)) {
                        $tempCSSFileContent = file_get_contents($tempCss);
                    } else {
                        $tempCSSFileContent = file_get_contents($item);
                    }
                    preg_match($searchClassPattern, $tempCSSFileContent, $findedClasses, PREG_OFFSET_CAPTURE);
                    if(isset($findedClasses[0]))
                    {
                        $num = $findedClasses[0][1];
                        $last = strpos($tempCSSFileContent,'}',$num);
                        $lng = $last - $num;
                        $tempCSSFileContent = substr_replace($tempCSSFileContent,'',$num,$lng+1);
                        file_put_contents($tempCss, $tempCSSFileContent);
                    }

                }
            }
            $this->counter++;
        }
    }
    public function mergeCSS()
    {
        for($i=0;$i<$this->counter;$i++) {
            $file = 'temp_'.$i.'.css';
            $tempFile = file_get_contents($file);
            if ($this->cleanComments) {
                $tempFile = preg_replace($this->cleanCommentsPattern, '', $tempFile);
            }
            if ($this->minifyAllInOneString && (false === $this->minifyOneClassOneString)) {
                $tempFile = preg_replace($this->minifyAllInOneStringFirstPattern, '', $tempFile);
                $tempFile = preg_replace($this->minifyRemoveSpacesPattern, '', $tempFile);
            }
            if ($this->minifyOneClassOneString && (false === $this->minifyAllInOneString)) {
                $tempFile = preg_replace($this->oneClassOneStringPattern, '', $tempFile);
                $tempFile = preg_replace($this->minifyRemoveSpacesPattern, '', $tempFile);
            }
            $tempFile = preg_replace($this->cleanFromCarriagePattern, "\r\n", $tempFile);
            file_put_contents($this->resultCSS,$tempFile,FILE_APPEND);
            unlink($file);
        }
    }
}
class CSSStart
    extends CSSWalk
{
    public function __construct()
    {
        $this->getCSSClasses();
        $this->getJSClasses();
        $this->getJSFromFile(self::$jsClassesPath);
        $this->cleanClasses($this->dataClasses);
        $this->getAllLinks();
        $this->getClassesFromPages($this->linksFromPages);
        $this->getAllClasses();
        $this->getCSS($this->allClasses);
        $this->mergeCSS();

    }
}
$styles = new CSSStart();




