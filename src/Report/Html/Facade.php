<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage\Report\Html;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\RuntimeException;
use SebastianBergmann\CodeCoverage\Diff;

/**
 * Generates an HTML report from a code coverage object.
 */
class Facade
{
    /**
     * @var string
     */
    private $templatePath;

    /**
     * @var string
     */
    private $generator;

    /**
     * @var int
     */
    private $lowUpperBound;

    /**
     * @var int
     */
    private $highLowerBound;
    private $originData;
    private $diffData;

    /**
     * Constructor.
     *
     * @param int $lowUpperBound
     * @param int $highLowerBound
     * @param string $generator
     */
    public function __construct($lowUpperBound = 50, $highLowerBound = 90, $generator = '')
    {
        $this->generator = $generator;
        $this->highLowerBound = $highLowerBound;
        $this->lowUpperBound = $lowUpperBound;
        $this->templatePath = __DIR__ . '/Renderer/Template/';
    }


    public function zengliangParse()
    {
        $result = [];
        /**
         * 类在diff中的,都展示到结果中
         */
        print_r("1");
        var_dump($this->diffData);
        print_r("======originData======");
        var_dump($this->originData);
        print_r("============");

        foreach ($this->diffData as $diffKey => $diffValue) {
            print_r("======diffData======");
            print_r($diffKey);
            // foreach($this->originData as $k=>$v){
            //	var_dump($k);
//	}
            print_r(array_key_exists($diffKey, $this->originData));
            print_r(gettype($diffKey) . "++++++++++");
            //$diffKey = $diffKey.replace('\n', '').replace('\r', '');
            $diffKey = str_replace(PHP_EOL, '', $diffKey);
            print_r($diffKey . "---new diffKey----");
            print_r("-------test result--");
            if (array_key_exists($diffKey, $this->originData)) {
                //有变更且有覆盖信息  覆盖信息不变；有变更 但是没有覆盖信息 红；没有变更 但是有覆盖信息  一律白不加到结果中去；
                print_r("------------2-----------");
                $diffValueArray = $diffValue;
                $originValueArray = $this->originData[$diffKey];

                $resultValues = [];
                foreach ($diffValueArray as $key => $value) {
                    $diffLine = $key;
                    print_r("3");
                    if (array_key_exists($diffLine, $originValueArray)) {
                        $resultValues[$diffKey][$diffLine] = $originValueArray[$diffLine];
                        print_r("4");
                        unset($originValueArray[$key]);
                    } else {
                        print_r("5");
                        $resultValues[$diffKey][$diffLine] = [];
                    }
                    unset($diffValueArray[$key]);
                }
                $result[$diffKey] = $resultValues[$diffKey];
            } else {
                print_r($diffKey . "not exists in originData");
            }
        };
        print_r("-------------result-------------");
        var_dump($result);
        return $result;
    }


    /**
     * @param CodeCoverage $coverage
     * @param string $target
     */
    public function process(CodeCoverage $coverage, $target)
    {
//        file_put_contents(dirname(__FILE__) . '/data.txt', serialize($coverage->getData()));
        $d = new Diff();
        $diffData = $d->main();
        $this->originData = $coverage->getData();
        $this->diffData = $diffData;
        $zengliangData = $this->zengliangParse();
        $coverage->setData($zengliangData);

        //修改白名单 只展示增量报告:
        $whiteFileLists = [];
        foreach ($zengliangData as $key => $value) {
            $whiteFileLists[$key]=1;
        }
        $coverage->filter()->setWhitelistedFiles([]);
        print_r("-----after init----");
        print_r($coverage->filter()->getWhitelistedFiles());

        $coverage->filter()->setWhitelistedFiles($whiteFileLists);
        print_r("-----after zengliang----");
        print_r($coverage->filter()->getWhitelistedFiles());

        $target = $this->getDirectory($target);
        $report = $coverage->getReport();
        unset($coverage);

        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = \time();
        }

        $date = \date('D M j G:i:s T Y', $_SERVER['REQUEST_TIME']);

        $dashboard = new Dashboard(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound
        );

        $directory = new Directory(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound
        );

        $file = new File(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound
        );

        $directory->render($report, $target . 'index.html');
        $dashboard->render($report, $target . 'dashboard.html');

        foreach ($report as $node) {
            $id = $node->getId();

            if ($node instanceof DirectoryNode) {
                if (!\file_exists($target . $id)) {
                    \mkdir($target . $id, 0777, true);
                }

                $directory->render($node, $target . $id . '/index.html');
                $dashboard->render($node, $target . $id . '/dashboard.html');
            } else {
                $dir = \dirname($target . $id);

                if (!\file_exists($dir)) {
                    \mkdir($dir, 0777, true);
                }

                $file->render($node, $target . $id . '.html');
            }
        }
        $this->copyFiles($target);
    }

    /**
     * @param string $target
     */
    private function copyFiles($target)
    {
        $dir = $this->getDirectory($target . '.css');

        \file_put_contents(
            $dir . 'bootstrap.min.css',
            \str_replace(
                'url(../fonts/',
                'url(../.fonts/',
                \file_get_contents($this->templatePath . 'css/bootstrap.min.css')
            )

        );

        \copy($this->templatePath . 'css/nv.d3.min.css', $dir . 'nv.d3.min.css');
        \copy($this->templatePath . 'css/style.css', $dir . 'style.css');

        $dir = $this->getDirectory($target . '.fonts');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.eot', $dir . 'glyphicons-halflings-regular.eot');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.svg', $dir . 'glyphicons-halflings-regular.svg');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.ttf', $dir . 'glyphicons-halflings-regular.ttf');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.woff', $dir . 'glyphicons-halflings-regular.woff');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.woff2', $dir . 'glyphicons-halflings-regular.woff2');

        $dir = $this->getDirectory($target . '.js');
        \copy($this->templatePath . 'js/bootstrap.min.js', $dir . 'bootstrap.min.js');
        \copy($this->templatePath . 'js/d3.min.js', $dir . 'd3.min.js');
        \copy($this->templatePath . 'js/holder.min.js', $dir . 'holder.min.js');
        \copy($this->templatePath . 'js/html5shiv.min.js', $dir . 'html5shiv.min.js');
        \copy($this->templatePath . 'js/jquery.min.js', $dir . 'jquery.min.js');
        \copy($this->templatePath . 'js/nv.d3.min.js', $dir . 'nv.d3.min.js');
        \copy($this->templatePath . 'js/respond.min.js', $dir . 'respond.min.js');
        \copy($this->templatePath . 'js/file.js', $dir . 'file.js');
    }

    /**
     * @param string $directory
     *
     * @return string
     *
     * @throws RuntimeException
     */
    private function getDirectory($directory)
    {
        if (\substr($directory, -1, 1) != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        if (\is_dir($directory)) {
            return $directory;
        }

        if (@\mkdir($directory, 0777, true)) {
            return $directory;
        }

        throw new RuntimeException(
            \sprintf(
                'Directory "%s" does not exist.',
                $directory
            )
        );
    }
}

