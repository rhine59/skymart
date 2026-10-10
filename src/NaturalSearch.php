<?php
declare(strict_types=1);
final class NaturalSearch {
 public static function refine(array $current,string $instruction,array $categories): array {
  $text=mb_strtolower(trim($instruction));if($text==='')throw new InvalidArgumentException('Enter a search instruction.');
  if(mb_strlen($text)>250)throw new InvalidArgumentException('Search instruction too long.');
  if(preg_match('/^(new search|start again|reset)$/',$text))return ['terms'=>[]];
  $out=$current;
  if(preg_match('/(?:under|below|less than|maximum|up to)\s*£?\s*([0-9,]+)\s*(k|thousand)?/i',$text,$m)){$out['max_price']=(float)str_replace(',','',$m[1])*(empty($m[2])?1:1000);$text=str_replace($m[0],' ',$text);}
  if(preg_match('/(?:over|above|more than|minimum|at least)\s*£?\s*([0-9,]+)\s*(k|thousand)?/i',$text,$m)){$out['min_price']=(float)str_replace(',','',$m[1])*(empty($m[2])?1:1000);$text=str_replace($m[0],' ',$text);}
  foreach($categories as $cat){$name=mb_strtolower($cat['name']);if(str_contains($text,$name)){$out['category']=$cat['slug'];$text=str_replace($name,' ',$text);break;}}
  $text=preg_replace('/\b(find|show|me|only|ones|one|with|that|mention|mentions|containing|contain|including|include|for|please|aircraft|adverts|listings|search|within|results|and|the|a|all|priced|costing)\b/u',' ',$text);
  $text=trim(preg_replace('/\s+/u',' ',$text),' .,!');
  if($text!==''){$out['terms']??=[];$out['terms'][]=$text;$out['terms']=array_slice(array_values(array_unique($out['terms'])),0,8);}
  return $out;
 }
}
