<?php
require_once dirname(__FILE__).'/vendor/autoload.php';  # 在composer生成的vender同级目录
use SebastianBergmann\CodeCoverage\CodeCoverage;
$coverage = new CodeCoverage;
 

$coverage->filter()->addDirectoryToWhitelist('/opt/web/phpc/application/hbg_php_houseapp/phpapps/*');  
# 白名单<br>$coverage->filter()->removeDirectoryFromWhitelist('/var/www/html/userinfo.php'); 
# 从白名单中移除文件夹<br>$coverage->filter()->removeFileFromWhitelist('/var/www/html/userinfo.php'); # 从白名单中移除文件


$coverage->start('<Site coverage>');#开始统计
register_shutdown_function('__coverage_stop',$coverage);#注册关闭方法
  
function __coverage_stop(CodeCoverage $coverage){
  $coverage->stop();#停止统计
  $cov = '<?php return unserialize(' . var_export(serialize($coverage), true) . ');';#获取覆盖结果，注意使用了反序列化  //echo $cov;
  file_put_contents(dirname(__FILE__).'/cov/site.' . date('U') .'.'.uniqid(). '.cov', $cov);#将结果写入到文件中
}
