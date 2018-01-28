<?php

class CSSConfig
{
    protected $friendlyUrl = true;
    protected $customPath = __DIR__.'/assets/template/';
    protected $jsFolder = 'js';
    protected $cssFolder = 'css2';
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

    public function getCSSFromFile($data)
    {

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


$styles = new ClassesRegular();
$cssPaths = $styles->getCSSClasses();
$jsPaths = $styles->getJSClasses();


$tst = $styles->getJSFromFile($jsPaths);

$new = $styles->cleanClasses($tst);
var_dump($new);


