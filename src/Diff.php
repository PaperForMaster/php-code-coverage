<?php
/**
 * Created by PhpStorm.
 * User: 
 * Date: 2021/5/7
 * Time: 10:40 AM
 */

/**
 * 读git diff   v_10_3 master | grep -E 'diff --git|@@' > result.txt 处理之后的文件,将结果封装成数组 key=》文件名  value=》新增的行
 *
 */
namespace SebastianBergmann\CodeCoverage;
class Diff
{
    private $result = [];


    function main()
    {
        $fileContent = $this->getTxtcontent("RESULT_FILE");
        array_walk($fileContent, function (&$item) {
            if (strpos($item, 'diff') !== false) {
                $fileName = explode(' ', $item)[3];
                $fileName = str_replace("b/", "", $fileName);
                $this->result["PROJECT_ABS_PATH" . "/" . $fileName] = [];
            } else {
                /**
                 * 取result最后一个元素 放到value里面去
                 */
                //@@ -0,0 +1,19 @@
                $and = explode(' ', $item)[2];
                $beginLine = explode(',', $and)[0];
                $beginLine = str_replace("+", "", $beginLine);
                $count = explode(',', $and)[1];
                end($this->result);
                $key = key($this->result);
                $valueArray = $this->result[$key];
                for ($i = $beginLine; $i <= $beginLine + $count; $i++) {
                    $valueArray[$i] = 1;
                }
                /**
                 * 根据实际情况 是都需要替换成自己的项目目录
                 */
                if (strpos($key, '/project') !== false) {
                    $this->result[$key] = $valueArray;
                } else {
                    $this->result["PROJECT_ABS_PATH" . "/" . $key] = $valueArray;
                }
            }
        });

        print_r($this->result);
        return $this->result;
    }

    /*
     * 逐行读取TXT文件
     */
    function getTxtcontent($txtfile)
    {
        $file = @fopen($txtfile, 'r');
        $content = array();
        if (!$file) {
            return 'file open fail';
        } else {
            $i = 0;
            while (!feof($file)) {
                $content[$i] = mb_convert_encoding(fgets($file), "UTF-8", "GBK,ASCII,ANSI,UTF-8");
                $i++;
            }
            fclose($file);
            $content = array_filter($content); //数组去空
        }
//    print_r($content);
        return $content;
    }

}

$d = new Diff();
$d->main();