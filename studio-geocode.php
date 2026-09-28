<?php
declare(strict_types=1);
if(!function_exists('requireUser')){http_response_code(404);exit;}
$address=bounded($data,'address',255,true);
$city=field($data,'city');
if($city!=='' && ($city=caviteCity($city))===null)respond(['error'=>'Choose a city in Cavite.'],422);
if(mb_strlen($address)<5)respond(['error'=>'Enter a more complete street or landmark address.'],422);
$query=$address.($city!==''?', '.$city:'').', Cavite, Philippines';
$key=hash('sha256',$query);$cache=$_SESSION['studio_geocode_cache']??[];
if(isset($cache[$key]) && $cache[$key]['expires']>time())respond(['locations'=>$cache[$key]['locations']]);
if(microtime(true)-($_SESSION['studio_geocode_at']??0)<1)respond(['error'=>'Please wait a moment before searching again.'],429);
$_SESSION['studio_geocode_at']=microtime(true);
$url='https://photon.komoot.io/api/?'.http_build_query(['q'=>$query,'limit'=>8,'lang'=>'en','bbox'=>'120.55,14.05,121.08,14.53']);
$ch=curl_init($url);
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>'LashMatch/1.0 (Cavite studio locator)',CURLOPT_HTTPHEADER=>['Accept: application/json']]);
$raw=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$failure=curl_error($ch);curl_close($ch);
if($raw===false||$code!==200){error_log('Studio address lookup: '.$code.' '.$failure);respond(['error'=>'Address search is unavailable. Try again or pick the studio location on the Cavite map.'],503);}
$result=json_decode($raw,true);
if(!is_array($result['features']??null))respond(['error'=>'Address search returned an invalid response. Please try again.'],503);
$locations=[];
foreach($result['features'] as $feature){
 $p=$feature['properties']??[];$xy=$feature['geometry']['coordinates']??[];
 if(($feature['geometry']['type']??'')!=='Point'||count($xy)!==2||!is_numeric($xy[0])||!is_numeric($xy[1])||!caviteCoordinates((float)$xy[1],(float)$xy[0]))continue;
 // Do not auto-pin a province/city centre when a street address was not found.
 if(in_array($p['type']??'',['city','county','state','country'],true))continue;
 $foundCity=null;foreach(['city','district','county','name'] as $field){$foundCity=caviteCity((string)($p[$field]??''));if($foundCity!==null)break;}
 if($foundCity===null||($city!==''&&$foundCity!==$city))continue;
 $label=implode(', ',array_unique(array_filter([$p['name']??'',trim(($p['housenumber']??'').' '.($p['street']??'')),$p['locality']??'',$foundCity,'Cavite'])));
 $locations[]=['latitude'=>(float)$xy[1],'longitude'=>(float)$xy[0],'city'=>$foundCity,'label'=>$label];
}
if(count($cache)>=20)array_shift($cache);
$cache[$key]=['expires'=>time()+3600,'locations'=>$locations];$_SESSION['studio_geocode_cache']=$cache;
respond(['locations'=>$locations]);
