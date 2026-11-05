<?php
declare(strict_types=1);
/** Validador V93 sin eval: solo expresiones aritméticas de un subconjunto aprobado. */
function mpiValidateFormula(string $formula):void {
 if($formula===''||strlen($formula)>180)throw new InvalidArgumentException('Fórmula vacía o demasiado extensa');
 $allowedVariables=['area','height','stud','original','base','needed','useful','waste','perimeter','length','width','slope','sheetLength','density','cols','rows','price'];
 $allowedFuncs=['ceil'=>1,'floor'=>1,'round'=>1,'abs'=>1,'sqrt'=>1,'min'=>2,'max'=>2];
 preg_match_all('/\s+|(?:\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\.[0-9]+)|[a-zA-Z][a-zA-Z0-9_]*|[()+*\/,\-]|./u',$formula,$m);
 $tokens=[];foreach($m[0] as $part){if(trim($part)==='')continue;
  if(!preg_match('/^(?:\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\.[0-9]+|[a-zA-Z][a-zA-Z0-9_]*|[()+*\/,\-])$/D',$part))throw new InvalidArgumentException('Carácter prohibido en fórmula');
  $tokens[]=$part;
 }
 if(count($tokens)>100)throw new InvalidArgumentException('Demasiados términos');
 $i=0;$depth=0;$parse=null;
 $atom=function()use(&$atom,&$parse,&$i,&$depth,$tokens,$allowedVariables,$allowedFuncs):void{
  if(++$depth>24)throw new InvalidArgumentException('Fórmula demasiado anidada');
  $t=$tokens[$i++]??'';
  if($t==='+'||$t==='-'){$atom();}
  elseif($t==='('){$parse(0);if(($tokens[$i++]??'')!==')')throw new InvalidArgumentException('Paréntesis incompleto');}
  elseif(preg_match('/^(?:\d|\.)/',$t)){if(!is_numeric($t)||!is_finite((float)$t))throw new InvalidArgumentException('Número inválido');}
  elseif(preg_match('/^[a-zA-Z]/',$t)){
   if(($tokens[$i]??'')==='('){if(!isset($allowedFuncs[$t]))throw new InvalidArgumentException('Función no admitida: '.$t);
    $i++;$argc=1;$parse(0);while(($tokens[$i]??'')===','){$i++;$argc++;$parse(0);}
    if(($tokens[$i++]??'')!==')'||$argc!==$allowedFuncs[$t])throw new InvalidArgumentException('Paréntesis o argumentos inválidos');
   }elseif(!in_array($t,$allowedVariables,true))throw new InvalidArgumentException('Variable no admitida: '.$t);
  }else throw new InvalidArgumentException('Término inválido en fórmula');
  $depth--;
 };
 $parse=function(int $min)use(&$parse,&$atom,&$i,$tokens):void{
  $atom();while($i<count($tokens)){$op=$tokens[$i];$prec=in_array($op,['+','-'],true)?1:(in_array($op,['*','/'],true)?2:-1);
   if($prec<$min)break;$i++;$parse($prec+1);}
 };
 $parse(0);if($i!==count($tokens))throw new InvalidArgumentException('Fórmula incompleta o mal formada');
}

/** Evaluación independiente en servidor, con el mismo conjunto cerrado de operadores del navegador. */
function mpiEvalFormula(string $formula,array $scope):float {
 mpiValidateFormula($formula);
 preg_match_all('/\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\.[0-9]+|[a-zA-Z][a-zA-Z0-9_]*|[()+*\/,\-]/',$formula,$m);
 $tokens=$m[0];$i=0;$depth=0;
 $atom=null;$parse=null;
 $atom=function()use(&$atom,&$parse,&$i,&$depth,$tokens,$scope):float {
  if(++$depth>24)throw new InvalidArgumentException('Anidación excesiva');
  $t=$tokens[$i++]??'';
  if($t==='+')$v=$atom();
  elseif($t==='-')$v=-$atom();
  elseif($t==='('){$v=$parse(0);$i++;}
  elseif(is_numeric($t))$v=(float)$t;
  elseif(($tokens[$i]??'')==='('){
   $i++;$args=[$parse(0)];while(($tokens[$i]??'')===','){$i++;$args[]=$parse(0);} $i++;
   $v=match($t){
    'ceil'=>ceil($args[0]),'floor'=>floor($args[0]),'round'=>round($args[0]),'abs'=>abs($args[0]),
    'sqrt'=>$args[0]<0?NAN:sqrt($args[0]),'min'=>min($args[0],$args[1]),'max'=>max($args[0],$args[1]),
    default=>throw new InvalidArgumentException('Función inválida')};
  }else $v=(float)($scope[$t]??1);
  $depth--;
  if(!is_finite((float)$v))throw new InvalidArgumentException('La fórmula produjo un número no válido');
  return (float)$v;
 };
 $parse=function(int $min)use(&$parse,&$atom,&$i,$tokens):float {
  $left=$atom();while($i<count($tokens)){
   $op=$tokens[$i];$prec=in_array($op,['+','-'],true)?1:(in_array($op,['*','/'],true)?2:-1);
   if($prec<$min)break;$i++;$right=$parse($prec+1);
   if($op==='/'&&$right==0)throw new InvalidArgumentException('División por cero');
   $left=match($op){'+'=>$left+$right,'-'=>$left-$right,'*'=>$left*$right,'/'=>$left/$right};
   if(!is_finite($left))throw new InvalidArgumentException('Resultado inválido');
  }return $left;
 };
 $result=$parse(0);
 if($result<0||$result>1000000||!is_finite($result))throw new InvalidArgumentException('Cantidad fuera de límites');
 return round($result,4);
}

function mpiValidateCalcRules(mixed $rules):void {
 if(!is_array($rules)||count($rules)>200)throw new InvalidArgumentException('Máximo 200 reglas');
 $systems=['tumbado_gypsum','cielo_estructura','pared_doble','pared_una_cara','fibrocemento','pegado_plancha','cielo_pvc','wpc_interior','wpc_exterior','marmoleado','roof','general_teja','general_policarbonato','general_cielo','general_wall','general_alucobond','general_spc','general_barredera','general_cornisa','general_cresta'];
 $seen=[];
 foreach($rules as $rule){
  if(!is_array($rule)||!in_array($rule['system']??'', $systems,true)||!preg_match('/^[a-zA-Z0-9_-]{2,40}$/D',(string)($rule['code']??'')))throw new InvalidArgumentException('Sistema o código de regla inválido');
  $key=$rule['system'].'|'.$rule['code'];if(isset($seen[$key]))throw new InvalidArgumentException('Hay reglas duplicadas');$seen[$key]=true;
  if(strlen((string)($rule['name']??''))>140||!is_bool($rule['enabled']??null)||!is_bool($rule['approved']??null))throw new InvalidArgumentException('Nombre o estado de regla incorrecto');
  if($rule['enabled']&&!$rule['approved'])throw new InvalidArgumentException('No se publica una fórmula sin aprobación técnica');
  if($rule['system']==='roof'&&$rule['code']!=='screws')throw new InvalidArgumentException('Solo se permite screws en la cubierta avanzada');
  if(str_starts_with($rule['system'],'general_')&&!in_array($rule['code'],['main','fixings'],true))throw new InvalidArgumentException('Código no permitido en cálculo general');
  if($rule['enabled']&&!str_starts_with($rule['system'],'general_')&&$rule['system']!=='roof'&&trim((string)($rule['name']??''))==='')throw new InvalidArgumentException('Falta nombre de material adicional');
  mpiValidateFormula((string)($rule['expression']??''));
  foreach(['sampleArea','sampleOriginal','sampleExpected'] as $f)if(isset($rule[$f])&&($rule[$f]!==''&&(!is_numeric($rule[$f])||!is_finite((float)$rule[$f])||(float)$rule[$f]<0||(float)$rule[$f]>1000000)))throw new InvalidArgumentException('Valor de prueba inválido');
  if($rule['enabled']){
   foreach(['sampleArea','sampleOriginal','sampleExpected'] as $f)if(!isset($rule[$f])||$rule[$f]==='')throw new InvalidArgumentException('Falta ejemplo de prueba aprobado');
   if((float)$rule['sampleArea']<=0)throw new InvalidArgumentException('Área de prueba incorrecta');
   $scope=['area'=>(float)$rule['sampleArea'],'original'=>(float)$rule['sampleOriginal'],'height'=>3,'stud'=>.407,'base'=>12,'needed'=>13,'useful'=>.96,'waste'=>5,'perimeter'=>14,'length'=>5,'width'=>3,'slope'=>10,'sheetLength'=>3,'density'=>5,'cols'=>4,'rows'=>2,'price'=>15];
   $result=mpiEvalFormula((string)$rule['expression'],$scope);
   if(abs($result-(float)$rule['sampleExpected'])>0.00011)throw new InvalidArgumentException('El ejemplo no coincide con el resultado esperado: '.$key);
  }
 }
}
