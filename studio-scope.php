<?php
declare(strict_types=1);
// Service area: https://cavite.gov.ph/directory/
function caviteCities(): array {
 return ['Alfonso','Amadeo','Bacoor','Carmona','Cavite City','Dasmariñas','General Emilio Aguinaldo','General Mariano Alvarez','General Trias','Imus','Indang','Kawit','Magallanes','Maragondon','Mendez','Naic','Noveleta','Rosario','Silang','Tagaytay','Tanza','Ternate','Trece Martires'];
}
function caviteCity(string $city): ?string {
 $key=mb_strtolower(trim($city));
 $key=preg_replace('/,\s*cavite$/u','',$key);
 $key=preg_replace('/^city of\s+|\s+city$/u','',$key);
 $key=str_replace(['ñ','gen.'],['n','general'],$key);
 if($key==='mendez-nunez')$key='mendez';
 foreach(caviteCities() as $name) {
  $candidate=str_replace('ñ','n',mb_strtolower($name));
  $candidate=preg_replace('/\s+city$/u','',$candidate);
  if($key===$candidate)return $name;
 }
 return null;
}

// The same boundary is used by the admin map and server-side validation.
function caviteBoundary(): array {
 static $geometry;
 if ($geometry===null) $geometry=json_decode(file_get_contents(__DIR__.'/data/cavite-boundary.geojson'),true,512,JSON_THROW_ON_ERROR)['geometry'];
 return $geometry;
}
function studioPointInRing(float $x,float $y,array $ring): bool {
 $inside=false;$n=count($ring);
 for($i=0,$j=$n-1;$i<$n;$j=$i++) {
  [$a,$b]=$ring[$i];[$c,$d]=$ring[$j];
  $cross=($x-$a)*($d-$b)-($y-$b)*($c-$a);
  if(abs($cross)<1e-12 && $x>=min($a,$c) && $x<=max($a,$c) && $y>=min($b,$d) && $y<=max($b,$d))return true;
  if(($b>$y)!==($d>$y) && $x<($c-$a)*($y-$b)/($d-$b)+$a)$inside=!$inside;
 }
 return $inside;
}
function caviteCoordinates(float $latitude,float $longitude): bool {
 if(!is_finite($latitude)||!is_finite($longitude))return false;
 $g=caviteBoundary();$polygons=$g['type']==='Polygon'?[$g['coordinates']]:$g['coordinates'];
 foreach($polygons as $rings) {
  if(!studioPointInRing($longitude,$latitude,$rings[0]))continue;
  $hole=false;foreach(array_slice($rings,1) as $ring)if(studioPointInRing($longitude,$latitude,$ring)){$hole=true;break;}
  if(!$hole)return true;
 }
 return false;
}
