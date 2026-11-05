/* Mega Prime Import V93: reglas declarativas de cantidades; sin eval, Function ni código arbitrario.
   Entrada: fórmulas aritméticas con variables verificadas y funciones permitidas.
   Las reglas personalizan cantidades comerciales, NO sustituyen el cálculo geométrico estructural. */
(function(root){
 'use strict';
 const systems=['tumbado_gypsum','cielo_estructura','pared_doble','pared_una_cara','fibrocemento','pegado_plancha','cielo_pvc','wpc_interior','wpc_exterior','marmoleado','roof',
 'general_teja','general_policarbonato','general_cielo','general_wall','general_alucobond','general_spc','general_barredera','general_cornisa','general_cresta'];
 const variables=['area','height','stud','original','base','needed','useful','waste','perimeter','length','width','slope','sheetLength','density','cols','rows','price'];
 const names=new Set(systems), vars=new Set(variables), funcs={ceil:1,floor:1,round:1,abs:1,sqrt:1,min:2,max:2};
 const maxResult=1000000;
 function tokens(expression){
  const s=String(expression??'');if(!s||s.length>180)throw Error('La fórmula debe tener de 1 a 180 caracteres.');
  const arr=[];let i=0;
  while(i<s.length){if(/\s/.test(s[i])){i++;continue;}
   const m=/^(?:\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|\.[0-9]+|[a-zA-Z][a-zA-Z0-9_]*|[()+\-*/,])/.exec(s.slice(i));
   if(!m)throw Error('Carácter no permitido en la fórmula.');
   arr.push(m[0]);i+=m[0].length;if(arr.length>100)throw Error('La fórmula tiene demasiados términos.');
  }
  return arr;
 }
 function evaluate(expr,scope={}){
  const ts=tokens(expr);let pos=0,depth=0;
  function atom(){if(++depth>24)throw Error('Fórmula demasiado anidada.');
   let t=ts[pos++],value;
   if(t==='+')value=atom();
   else if(t==='-')value=-atom();
   else if(t==='('){value=parse(0);if(ts[pos++]!==')')throw Error('Falta cerrar un paréntesis.');}
   else if(/^\d|^\./.test(t||'')){value=Number(t);}
   else if(/^[a-zA-Z]/.test(t||'')){
    if(ts[pos]==='('){if(!Object.prototype.hasOwnProperty.call(funcs,t))throw Error('Función no autorizada: '+t);
     pos++;const args=[parse(0)];while(ts[pos]===','){pos++;args.push(parse(0));}
     if(ts[pos++]!==')'||args.length!==funcs[t])throw Error('Cantidad de argumentos incorrecta en '+t);
     const op={ceil:Math.ceil,floor:Math.floor,round:Math.round,abs:Math.abs,sqrt:Math.sqrt,min:Math.min,max:Math.max}[t];value=op(...args);
    }else{if(!vars.has(t))throw Error('Variable desconocida: '+t);
     value=Object.prototype.hasOwnProperty.call(scope,t)?Number(scope[t]):1;
    }
   }else throw Error('Término inválido en fórmula.');
   depth--;if(!Number.isFinite(value))throw Error('La fórmula produjo un número no válido.');return value;
  }
  function parse(min){let left=atom();while(pos<ts.length){const op=ts[pos],prec=(op==='+'||op==='-')?1:(op==='*'||op==='/')?2:-1;
   if(prec<min)break;pos++;const right=parse(prec+1);
   left=op==='+'?left+right:op==='-'?left-right:op==='*'?left*right:left/right;
   if(!Number.isFinite(left))throw Error('La fórmula divide por cero o excede los límites.');
  }return left;}
  const result=parse(0);if(pos!==ts.length)throw Error('La fórmula contiene un término incompleto.');
  if(!Number.isFinite(result)||result<0||result>maxResult)throw Error('El resultado debe estar entre 0 y '+maxResult+'.');
  return Math.round(result*10000)/10000;
 }
 function validate(rules){if(!Array.isArray(rules)||rules.length>200)throw Error('Máximo 200 reglas de cálculo.');
  const seen=new Set();rules.forEach((r,i)=>{
   if(!r||typeof r!=='object'||Array.isArray(r))throw Error('Regla '+(i+1)+' inválida.');
   if(!names.has(r.system))throw Error('Sistema no admitido en regla '+(i+1)+'.');
   if(!/^[a-zA-Z0-9_-]{2,40}$/.test(String(r.code??'')))throw Error('Código de regla inválido.');
   const key=r.system+'|'+r.code;if(seen.has(key))throw Error('Reglas duplicadas: '+key);seen.add(key);
   if(String(r.name??'').length>140)throw Error('El nombre de regla supera 140 caracteres.');
   if(typeof r.enabled!=='boolean'||typeof r.approved!=='boolean')throw Error('Indica activación y aprobación técnica.');
   if(r.enabled&&!r.approved)throw Error('Aprueba la fórmula mediante pruebas antes de activarla.');
   if(r.system==='roof'&&r.code!=='screws')throw Error('En cubiertas solo se permite la regla screws.');
   if(r.system.startsWith('general_')&&!['main','fixings'].includes(r.code))throw Error('Las reglas generales deben usar main o fixings.');
   if(r.enabled&&!r.system.startsWith('general_')&&r.system!=='roof'&&!String(r.name||'').trim())throw Error('Indica el nombre del material de la lista.');
   evaluate(r.expression,{area:12,height:3,stud:.407,original:10,base:12,needed:13,useful:.96,waste:5,perimeter:14,length:5,width:3,slope:10,sheetLength:3,density:5,cols:4,rows:2,price:15});
   if(r.sampleArea!==undefined&&(!Number.isFinite(Number(r.sampleArea))||Number(r.sampleArea)<=0||Number(r.sampleArea)>1000000))throw Error('Área de prueba inválida.');
   if(r.sampleOriginal!==undefined&&(!Number.isFinite(Number(r.sampleOriginal))||Number(r.sampleOriginal)<0||Number(r.sampleOriginal)>1000000))throw Error('Base de prueba inválida.');
   if(r.enabled){
    if(r.sampleArea===undefined||r.sampleOriginal===undefined||r.sampleExpected===undefined||r.sampleExpected==='')throw Error('Registra un ejemplo completo con resultado esperado antes de activar.');
    const expected=Number(r.sampleExpected);
    if(!Number.isFinite(expected)||expected<0||expected>maxResult)throw Error('Resultado esperado inválido.');
    const context={area:Number(r.sampleArea),original:Number(r.sampleOriginal),height:3,stud:.407,base:12,needed:13,useful:.96,waste:5,perimeter:14,length:5,width:3,slope:10,sheetLength:3,density:5,cols:4,rows:2,price:15};
    const actual=evaluate(r.expression,context);
    if(Math.abs(actual-expected)>.00011)throw Error('La prueba no coincide para '+key+': esperado '+expected+', calculado '+actual);
   }
  });return true;
 }
 function rule(system,code){const c=root.MPI_CMS_ACTIVE||root.MPI_CMS_DEFAULTS||{};
  const rows=Array.isArray(c.calcRules)?c.calcRules:[];
  return rows.find(r=>r.system===system&&r.code===code&&r.enabled===true&&r.approved===true)||null;
 }
 function quantity(system,code,original,context={}){const r=rule(system,code);if(!r)return original;
  const value=evaluate(r.expression,{...context,original:Number(original)});
  return value;
 }
 function applyRows(system,rows,context={}){
  if(!names.has(system))throw Error('Sistema de cálculo desconocido.');
  const out=rows.map(r=>({...r})),codes=new Set(out.map(r=>r.code));
  for(const item of out){const ruleFor=rule(system,item.code);if(!ruleFor)continue;
   item.qty=quantity(system,item.code,item.qty,context);
   if(ruleFor.name)item.name=ruleFor.name;
  }
  for(const r of (root.MPI_CMS_ACTIVE?.calcRules||[])){
   if(r.system!==system||!r.enabled||!r.approved||codes.has(r.code))continue;
   if(!r.name)throw Error('Un material adicional necesita nombre comercial.');
   out.push({code:r.code,name:r.name,qty:evaluate(r.expression,{...context,original:0})});
  }
  return out;
 }
 root.MPI_CalcRules={systems,variables,evaluate,validate,quantity,applyRows};
})(typeof window!=='undefined'?window:globalThis);
