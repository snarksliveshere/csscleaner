<?php
ini_set('max_execution_time', 900000);
// TODO надо перебить это на генератор, чтобы память не ел
// TODO разобраться с комментариями
// убрать return
// поставить в статик разбиение по пробелу
class CSSConfig
{
    protected $urlWithoutHTML = true;
    protected $customPath = __DIR__.'/assets/template/';
    protected $jsFolder = 'js';
    protected $cssFolder = 'css2';
    protected $mainPageLink = 'http://demo4.ru/';
    protected $resultCSS = 'result.css';
    protected $counter = 0;
    protected $needComment = true;
    protected $minifyAllInOneString = false;
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
        $this->allJSClasses = $nextArr;
        return $this->allJSClasses;
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
            $end_css = 'temp_'.$ki_css.'.css';
            $csss = file_get_contents($item);
            preg_match_all($this->classInCSSPattern, $csss, $csss_arr, PREG_OFFSET_CAPTURE);
            $use_class = [];
            $class_arrs = [];
            foreach ($result_class as $html)
            {
                $st_pat = '~\b'.$html.'\b~';
                foreach ($csss_arr['class'] as $val)
                {
                    if(!strpos($val[0], '#'))
                    {
                        if(!in_array($val[0], $class_arrs))
                        {
                            $class_arrs[]= trim($val[0]);
//                            $class_arrs[]= $val[0];
                        }

                    }
                    if(preg_match($st_pat, $val[0]))
                    {
                        $use_class[] = trim($val[0]);
//                        $use_class[] = $val[0];
                    }
                }
            }
            $css_val_arr = array_diff($class_arrs,$use_class,array(''));


            /**
             * сейчас будем вырезать
             */
            // TODO там обертывается в регулярку и аттрибуты будут неправильно интерпретироваться []
            // .cat_in .ten у меня преобразовалось в .cat_in, который уже вырезался до этого
            // TODO если попались в 1 файле 2 класса из числа тех, которые надо удалять
            foreach ($class_arrs as $ki => $val_arr)
            {
                if(in_array($val_arr,$css_val_arr) && strstr($val_arr, '.'))
                {
                    $pat = '/\Q'.$val_arr.'\E(?=[\s,\z]{0,}{)/u';
                    if (file_exists($end_css)) {
                        $fil = file_get_contents($end_css);
                    } else {
                        $fil = file_get_contents($item);
                    }
                    preg_match_all($pat, $fil, $new_csss_arr, PREG_OFFSET_CAPTURE);
                    if(!isset($new_csss_arr[0][1]))
                    {
                        $num = $new_csss_arr[0][0][1];
                        $last = strpos($fil,'}',$num);
                        $lng = $last - $num;
                        $fil = substr_replace($fil,'',$num,$lng+1);
                        file_put_contents($end_css, $fil);
                    }
                    else {
                        $newStr = '';
                        foreach ($new_csss_arr[0] as $item_all) {
                            if($this->lngCounter == 0) {
                                $num = $item_all[1];
                                $last = strpos($fil,'}',$num);
                                $this->lngCounter = $last - $num;
                                $this->lngCounter++;
                                $newStr = substr_replace($fil,'',$num,$this->lngCounter);
                            } else {
                                // нужно считать, т.к. могут быть одинаковые классы с неодинаковым содержамым
                                $num = $item_all[1] - $this->lngCounter;
                                $last = strpos($newStr,'}',$num);
                                $this->lngCounter = $last - $num;
                                $this->lngCounter++;
                                $newStr = substr_replace($newStr,'',$num,$this->lngCounter);
                            }
                        }
                        $this->lngCounter = 0;
                        file_put_contents($end_css, $newStr);
                    }

                }
            }
            $this->counter++;
        }

    }

    public function mergeCSS()
    {
        for($i=0;$i<$this->counter;$i++)
        {
            $file = 'temp_'.$i.'.css';
            $get_file = file_get_contents($file);
            file_put_contents($this->resultCSS,$get_file,FILE_APPEND);
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
        $jsClasses = $this->getJSFromFile(self::$jsClassesPath);
        $this->cleanClasses($jsClasses);
        $allLinks = $this->getAllLinks();
        $cssClasses = $this->getClassesFromPages($allLinks);
        $res = $this->getAllClasses();
//        foreach ($res as $re) {
//            $re = $re."\n";
//            file_put_contents('array_orig.txt',$re,FILE_APPEND);
//        }

        //     $result = file('array_orig.txt',FILE_IGNORE_NEW_LINES);
        //var_dump($result);
//
        // надо проверить только по меню
        $result = ['menu'];

        $this->getCSS($result);
//        $this->mergeCSS();

    }
}


$styles = new CSSStart();

//die;
//$cssPaths = $styles->getCSSClasses();
//$jsPaths = $styles->getJSClasses();
//$tst = $styles->getJSFromFile($jsPaths);
//$new = $styles->cleanClasses($tst);
//$fl = $styles->getAllLinks();
//$classes = $styles->getClassesFromPages($fl);
//
//$res = $styles->getAllClasses();
//foreach ($res as $re) {
//    $re = $re."\n";
//    file_put_contents('array.txt',$re,FILE_APPEND);
//}
//$result = file('array.txt');
//
//$res_c = $styles->getCSS($result);
//$styles->mergeCSS();




