<?php
ini_set('max_execution_time', 900000);
// надо перебить это на генератор, чтобы память не ел
class CSSConfig
{
    protected $urlWithoutHTML = true;
    protected $customPath = __DIR__.'/assets/template/';
    protected $jsFolder = 'js';
    protected $cssFolder = 'css2';
    protected $mainPageLink = 'http://demo4.ru/';
}
class CleanStyle
    extends CSSConfig
{
    protected static $jsClasses = [];
    protected static $cssClasses = [];
    /**
     * @param $dirname
     * @param $ext
     */
    protected static function getAllClasses($dirname, $ext)
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
                        self::$jsClasses[] =$fullPath;
                    } elseif (substr($fullPath,-3) == 'css') {
                        self::$cssClasses[] =$fullPath;
                    }
                }
                // Если перед нами директория, вызываем рекурсивно
                if(is_dir($fullPath)) {
                    self::getAllClasses($fullPath,$ext);
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
        self::getAllClasses($dirname, 'css');
        return self::$cssClasses;
    }
    /**
     * @param $path
     * @return array
     */
    public function getJSClasses()
    {
        $dirname = $this->customPath.$this->jsFolder;
        self::getAllClasses($dirname, 'js');
        return self::$jsClasses;
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
     * css REGEXP
     * @var string
     */
    protected $cssPattern = '~class=\"(?P<class>[_a-z\sA-Z1-9-]{0,})\"~';
    /**
     * @var string
     */
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
        return $this->dataClasses;
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
            if(strpos($val, ' '))
            {
                $temps  = explode(' ',$val);
                foreach ($temps as $tempum)
                {
                    if(strlen($tempum)!=1)
                    {
//                        if(stripos($tempum,'.') === 0 )
//                        {
//                            $tempum = substr($tempum,1);
//                            array_push($tempArray, $tempum);
//                        }
//                        else
//                        {
//                            array_push($tempArray, $tempum);
//                        }
                        $tempArray[] = $tempum;
                    }
                }
            } else {
                if(strlen($val)!=1)
                {
//                    array_push($tempArray, $val);
//                    if(stripos($val,'.') === 0  )
//                    {
//                        $val = substr($val,1);
//                        array_push($tempArray, $val);
//                    }
//                    else
//                    {
//                        array_push($tempArray, $val);
//                    }
                    $tempArray[] = $val;
                }
            }
        }
        //return $tempArray;
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
        return $nextArr;
    }
}

class LinksFromSite
    extends ClassesRegular
{
    protected $patternHref = '~href="(?<link>.+?)"~';

    /**
     *
     */
    public function getAllLinks()
    {
        $resourceLink = file_get_contents($this->mainPageLink);
        preg_match_all($this->patternHref, $resourceLink,$allLinks);
        $values = [];
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
                $values[] = $allLink;
            } else {
                if ( substr($allLink,-4) == 'html' && !stristr($allLink, 'http://') && !stristr($allLink, 'https://') ) {
                    $values[] = $allLink;
                }
            }
        }
        $values[] = $this->mainPageLink;
        return $values;
    }
}

class getCSSClassesFromPages
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
        return $this->storageClasses;
    }
}

$styles = new getCSSClassesFromPages();
//$cssPaths = $styles->getCSSClasses();
//$jsPaths = $styles->getJSClasses();
//$tst = $styles->getJSFromFile($jsPaths);
//$new = $styles->cleanClasses($tst);

$fl = $styles->getAllLinks();
$classes = $styles->getClassesFromPages($fl);
//var_dump($classes);



