<?php
/*
   版本编号：101105082215
   文件名称：sql.php
   更新日期：2011-05-08
   代码修改：feikeq
   代码功能：利用0.01精度校正库文件修正经纬度偏移。
*/
header("Content-Type:text/html; charset=utf-8");
define('__dat_db__' , 'offset.dat' );// DAT数据文件
define('datmax' , 9813675 );// 数据条数-结束记录

//SELECT * FROM `offset_data` where lon=7350 and lat=3930

// # xz.php?lat=39.914914&lon=116.460633 
$lon=$_GET['lon'];
$lat=$_GET['lat'];
$tmplon=intval($lon * 100);
$tmplat=intval($lat * 100);
//经度到像素X值
function lngToPixel($lng,$zoom) {
return ($lng+180)*(256<<$zoom)/360;
}
//像素X到经度
function pixelToLng($pixelX,$zoom){
return $pixelX*360/(256<<$zoom)-180;
}
//纬度到像素Y
function latToPixel($lat, $zoom) {
$siny = sin($lat * pi() / 180);
$y=log((1+$siny)/(1-$siny));
return (128<<$zoom)*(1-$y/(2*pi()));
}
//像素Y到纬度
function pixelToLat($pixelY, $zoom) {
$y = 2*pi()*(1-$pixelY /(128 << $zoom));
$z = pow(M_E, $y);
$siny = ($z -1)/($z +1);
return asin($siny) * 180/pi();
}


function xy_fk( $number ){
        $fp = fopen(__dat_db__,"rb"); //■1■.将 r 改为 rb
        $myxy=$number;#"112262582";
        $left = 0;//开始记录
        $right = datmax;//结束记录
        
        //开如用二分法来查找查数据
        while($left <= $right){
            $recordCount =(floor(($left+$right)/2))*8; //取半
            //echo "运算：left=".$left." right=".$right." midde=".$recordCount."<br />";
            @fseek ( $fp, $recordCount , SEEK_SET ); //设置游标
            $c = fread($fp,8); //读8字节
            $lon = unpack('s',substr($c,0,2));
            $lat = unpack('s',substr($c,2,2));
            $x = unpack('s',substr($c,4,2));
            $y = unpack('s',substr($c,6,2));
            $jwd=$lon[1].$lat[1];
            //echo "找到的经纬度:".$jwd;
            if ($jwd==$myxy){
               fclose($fp);
               return $x[1]."|".$y[1];
               break;
            }else if($jwd<$myxy){
               //echo " > ".$myxy."<br />";
               $left=($recordCount/8) +1;
            }else if($jwd>$myxy){
               //echo " < ".$myxy."<br />";
               $right=($recordCount/8) -1;
            }
                  
        }
        fclose($fp);
}

$offset =xy_fk($tmplon.$tmplat);
// echo $offset.'<br />';
$off=explode('|',$offset);
$lngPixel=lngToPixel($lon,18)+$off[0];
$latPixel=latToPixel($lat,18)+$off[1];

$offset_lat = pixelToLat($latPixel, 18);
$offset_lon = pixelToLng($lngPixel, 18);
$array=array('lat'=>$offset_lat,'lon'=>$offset_lon);
$str_encode = json_encode($array);
// echo pixelToLat($latPixel,18).",".pixelToLng($lngPixel,18);
// echo $offset_lat.",".$offset_lon;
echo $str_encode;
?>