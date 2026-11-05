<?php if (!defined('MPI_AUTHORIZED')) { http_response_code(403); exit; } ?>
<!DOCTYPE html>
<html lang="es">
<head>
<script id="mpiAlmacenPorCuenta">
/* Aísla los borradores de cada usuario de la calculadora en un mismo navegador.
   No borra copias previas de V82: se migran mediante exportación/restauración JSON. */
const mpiUserStore=(()=>{
  let real;
  try{
    real=window.localStorage;
    const test='__mpi_test_storage';real.setItem(test,'1');real.removeItem(test);
  }catch(e){
    window.MPI_ALMACEN_TEMPORAL=true;
    const memoria=Object.create(null);
    real={getItem:k=>Object.hasOwn(memoria,k)?memoria[k]:null,setItem:(k,v)=>{memoria[k]=String(v);},removeItem:k=>{delete memoria[k];},key:i=>Object.keys(memoria)[i]??null,get length(){return Object.keys(memoria).length}};
    document.addEventListener('DOMContentLoaded',()=>{
      const mensaje=document.createElement('p');mensaje.style.cssText='padding:12px;color:#fff2bf;background:#523d1d;border:1px solid #e3c778;';
      mensaje.textContent='El navegador bloquea el almacenamiento permanente. Guarda una copia en MariaDB o descarga un JSON antes de cerrar.';
      document.body.prepend(mensaje);
    },{once:true});
  }
  const pre='mpi_v83_<?= (int)$u['id'] ?>_';
  const shared='__mpi_fin_sesion';
  const storageKey=k=>String(k)===shared?shared:pre+String(k);
  const list=()=>{const out=[];for(let i=0;i<real.length;i++){const k=real.key(i);if(k&&k.startsWith(pre))out.push(k.slice(pre.length));}return out;};
  return {getItem:k=>real.getItem(storageKey(k)),setItem:(k,v)=>real.setItem(storageKey(k),String(v)),removeItem:k=>real.removeItem(storageKey(k)),clear:()=>{for(const key of list())real.removeItem(pre+key);},key:i=>list()[i]??null,get length(){return list().length;}};
})();
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#111111">
    <title>Mega Prime Import | Sistema de Costos de Importación</title>
    <!-- Recursos incluidos para alojamiento propio -->
    <link rel="stylesheet" href="assets/tailwind.css">
    <!-- SheetJS para procesamiento y descarga de hojas Excel -->
    <script src="assets/xlsx.full.min.js"></script>
    <!-- Chart.js para gráficos ejecutivos -->
    <script src="assets/chart.umd.min.js"></script>
    <!-- jsPDF para exportar reportes y manual en formato PDF -->
    <script src="assets/jspdf.umd.min.js"></script>



    <style>
        * { -webkit-tap-highlight-color: transparent; }
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #c7c7c7; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #D4AF37; }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button { opacity: 1; }
        .gold-glow:focus { box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.28); }
        .sticky-col { position: sticky; left: 0; z-index: 10; }


        /* === TEMA PRO OSCURO: capa visual, conserva la lógica original === */
        :root { color-scheme: dark; --pro-bg:#090d16; --pro-panel:#111827; --pro-panel2:#172033; --pro-border:#293449; --pro-text:#e5e7eb; --pro-muted:#9ca3af; --pro-gold:#e7c45a; }
        html, body { background:#090d16 !important; color:var(--pro-text) !important; }
        body { background-image:radial-gradient(ellipse at top, #172033 0%, #090d16 58%) !important; }
        body .bg-white, body .bg-gray-50, body .bg-gray-100, body .bg-gray-200 { background-color:#111827 !important; }
        body .bg-blue-50, body .bg-blue-50\/50, body .bg-emerald-50, body .bg-emerald-50\/50, body .bg-amber-50, body .bg-amber-50\/50, body .bg-red-50 { background-color:#141e2e !important; }
        body .text-gray-900, body .text-gray-800, body .text-gray-700, body .text-gray-600, body .text-gray-500, body .text-blue-950, body .text-emerald-950, body .text-amber-950 { color:#d1d5db !important; }
        body .text-gray-400 { color:#9ca3af !important; }
        body .border-gray-100, body .border-gray-200, body .border-gray-300, body .border-blue-100, body .border-blue-200, body .border-emerald-200, body .border-amber-200 { border-color:#293449 !important; }
        body input:not([type=checkbox]):not([type=radio]), body select, body textarea { background:#0b1220 !important; color:#e5e7eb !important; border-color:#344156 !important; }
        body input::placeholder, body textarea::placeholder { color:#718096 !important; }
        body table { color:#e5e7eb; }
        body th { background:#172033 !important; color:#e7c45a !important; }
        body td { border-color:#293449 !important; }
        body .hover\:bg-gray-100:hover, body .hover\:bg-gray-200:hover, body .hover\:bg-gray-300:hover { background-color:#263247 !important; }
        body .shadow-xl, body .shadow-md, body .shadow-sm { box-shadow:0 14px 40px rgba(0,0,0,.22) !important; }
        #toastInner { background:#111827 !important; }
        .pro-module { background:linear-gradient(145deg,#111827,#0f172a); border:1px solid #293449; border-radius:18px; padding:18px; box-shadow:0 12px 30px rgba(0,0,0,.18); }
        .pro-field { width:100%; border:1px solid #344156; background:#0b1220; color:#e5e7eb; border-radius:10px; padding:10px 12px; font-size:13px; min-height:42px; }
        .pro-label { display:block; color:#aeb8c8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; margin:0 0 6px; }
        .pro-btn { border:1px solid #3a465b; background:#1b2638; color:#e5e7eb; border-radius:10px; padding:9px 12px; font-size:12px; font-weight:800; cursor:pointer; transition:.15s; }
        .pro-btn:hover { border-color:#e7c45a; transform:translateY(-1px); }
        .pro-btn-primary { background:#e7c45a; color:#111827; border-color:#e7c45a; }
        .pro-kpi { background:#0b1220; border:1px solid #293449; border-radius:12px; padding:12px; }
        .pro-kpi .k { color:#9ca3af; font-size:10px; font-weight:800; text-transform:uppercase; }
        .pro-kpi .v { color:#f9fafb; font-size:20px; font-weight:900; margin-top:4px; }
        .pro-table-wrap { overflow:auto; border:1px solid #293449; border-radius:12px; }
        .pro-table { width:100%; border-collapse:collapse; font-size:12px; min-width:500px; }
        .pro-table th,.pro-table td { padding:10px; text-align:left; border-bottom:1px solid #293449; }
        .pro-pill { display:inline-flex; align-items:center; gap:6px; border:1px solid #334155; background:#172033; color:#cbd5e1; border-radius:999px; padding:4px 9px; font-size:10px; font-weight:800; }
        .pro-section-title { color:#f8fafc; font-size:15px; font-weight:900; letter-spacing:.02em; }
        .pro-muted { color:#9ca3af; font-size:12px; }
        @media print { body, html { background:white !important; color:#111 !important; } #panelHerramientasPRO { display:none !important; } }


        /* UX PREMIUM: navegación rápida, accesibilidad y lectura */
        .ux-nav{position:sticky;top:0;z-index:35;background:rgba(17,17,17,.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(212,175,55,.35);padding:.65rem;display:flex;gap:.5rem;overflow-x:auto;scrollbar-width:thin}
        .ux-nav a,.ux-nav button{white-space:nowrap;display:inline-flex;align-items:center;gap:.4rem;border:1px solid #3f3f46;border-radius:.7rem;padding:.6rem .85rem;color:#e5e7eb;font-size:.78rem;font-weight:700;text-decoration:none;transition:all .15s;background:#202020}
        .ux-nav a:hover,.ux-nav button:hover,.ux-nav a:focus-visible,.ux-nav button:focus-visible{border-color:#D4AF37;color:#D4AF37;outline:2px solid transparent}
        .ux-nav .ux-primary{background:#D4AF37;color:#111;border-color:#D4AF37}
        .ux-section-target{scroll-margin-top:82px}
        .ux-help{font-size:.78rem;color:#64748b;line-height:1.5}
        .ux-inventory-row-low{background:rgba(239,68,68,.08)}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*{transition:none!important;animation:none!important}}
    

        /* Mejoras adicionales de accesibilidad, impresión y adaptación móvil */
        :focus-visible { outline: 3px solid #FF8A24 !important; outline-offset: 2px; }
        button:disabled, input:disabled, select:disabled { opacity:.58; cursor:not-allowed; }
        .pro-btn, .download-resource-btn { min-height:40px; }
        .pro-muted { line-height:1.55; }
        @media(max-width:640px){ .pro-module{padding:13px} .pro-kpi .v{font-size:17px} .pro-table-wrap{max-width:100%;overscroll-behavior-x:contain} }
        @media print {
          body,html { background:#fff !important; color:#111 !important; }
          body * { color:#111 !important; text-shadow:none !important; }
          .ux-nav, #panelHerramientasPRO, button, input[type=file], .no-print { display:none !important; }
          .pro-module, .pro-kpi, section, .rounded-xl { background:#fff !important; box-shadow:none !important; border-color:#bbb !important; break-inside:avoid; }
          table { color:#111 !important; } th,td { background:#fff !important; color:#111 !important; border-color:#aaa !important; }
          canvas { max-width:100% !important; }
        }

        /* Botones de descarga: alto contraste sobre el tema oscuro */
        .download-resource-btn {
            background: #172033 !important;
            color: #F5C451 !important;
            border-color: #F5C451 !important;
            font-weight: 800 !important;
        }
        .download-resource-btn svg { color: #FF8A24 !important; stroke: #FF8A24 !important; }
        .download-resource-btn span { color: #F5C451 !important; }
        .download-resource-btn:hover,
        .download-resource-btn:focus-visible {
            background: #202d42 !important;
            color: #FF8A24 !important;
            border-color: #FF8A24 !important;
            outline: 2px solid rgba(255,138,36,.35);
            outline-offset: 2px;
        }
        .download-resource-btn:hover span,
        .download-resource-btn:focus-visible span { color: #FF8A24 !important; }
        .download-resource-btn:hover svg,
        .download-resource-btn:focus-visible svg { color: #F5C451 !important; stroke: #F5C451 !important; }

        /* === PALETA PREMIUM: texto blanco, dorado y naranja en toda la interfaz === */
        :root {
            --pro-gold: #F5C451;
            --pro-gold-bright: #FFD76A;
            --pro-orange: #FF8A24;
            --pro-white: #F8FAFC;
        }
        html, body { color: var(--pro-white) !important; }
        /* Texto general: blanco para lectura y contraste */
        body, body p, body li, body td, body small,
        body .text-black, body .text-slate-900, body .text-slate-800,
        body .text-neutral-900, body .text-neutral-800 { color: var(--pro-white) !important; }
        /* Encabezados y títulos: dorado */
        body h1, body h2, body h3, body h4, body h5, body h6,
        body .text-gray-900, body .text-gray-800, body .text-gray-700,
        body .text-blue-950, body .text-emerald-950, body .text-amber-950,
        body .pro-label, body th, body legend {
            color: var(--pro-gold-bright) !important;
        }
        /* Elementos secundarios y metadatos: blanco suave */
        body .text-gray-600, body .text-gray-500, body .text-gray-400,
        body .text-slate-600, body .text-slate-500, body .text-slate-400,
        body .text-neutral-600, body .text-neutral-500 {
            color: #E2E8F0 !important;
        }
        /* Acciones, importes destacados y acentos: naranja */
        body a, body .text-orange-500, body .text-orange-600,
        body .text-amber-500, body .text-amber-600,
        body .text-yellow-500, body .text-yellow-600,
        body .pro-btn-primary, body button[data-accent="orange"] {
            color: var(--pro-orange) !important;
        }
        body .pro-btn-primary { background: var(--pro-gold) !important; color: #111827 !important; border-color: var(--pro-gold) !important; }
        body input:not([type=checkbox]):not([type=radio]), body select, body textarea,
        body option { color: var(--pro-white) !important; }
        body input::placeholder, body textarea::placeholder { color: #AAB4C5 !important; }
        body table td { color: var(--pro-white) !important; }
        body table th { color: var(--pro-gold-bright) !important; }
        body button, body .pro-btn { text-shadow: none; }
        body .pro-module h1, body .pro-module h2, body .pro-module h3,
        body .pro-module h4 { color: var(--pro-gold-bright) !important; }
        /* Mantener indicadores semánticos de éxito/error legibles */
        body .text-green-500, body .text-green-600, body .text-emerald-500,
        body .text-emerald-600 { color: #4ADE80 !important; }
        body .text-red-500, body .text-red-600 { color: #F87171 !important; }
        body .text-sky-500, body .text-blue-500, body .text-cyan-500 { color: #7DD3FC !important; }

    

        /* === AJUSTE SOLICITADO: CAMPOS COMPLEMENTARIOS + DASHBOARD === */
        :root { --ui-gold:#FFD76A; --ui-white:#F8FAFC; --ui-blue:#38BDF8; --ui-neon:#22D3EE; --ui-orange:#FF8A24; --ui-dark:#0B1220; }
        /* Contenedores de los campos indicados: panel oscuro y contorno dorado */
        div:has(> label[for="fleteInternacionalGlobal"]),
        div:has(> label[for="tasaIVA"]),
        div:has(> label[for="tasaIVAVenta"]),
        div:has(> label[for="otrosRubrosGravadosDeclaracion"]),
        div:has(> label[for="metodoProrrateo"]),
        div:has(> label[for="impuestoICE"]) {
            background:linear-gradient(145deg,#111827,#0b1220) !important;
            border:1px solid #D4AF37 !important;
            box-shadow:0 0 0 1px rgba(212,175,55,.10),0 8px 22px rgba(0,0,0,.18);
        }
        #fleteInternacionalGlobal, #tasaIVA, #tasaIVAVenta,
        #otrosRubrosGravadosDeclaracion, #metodoProrrateo, #impuestoICE {
            background:#080E19 !important;
            color:var(--ui-gold) !important;
            border:1px solid #D4AF37 !important;
            caret-color:var(--ui-neon);
        }
        #fleteInternacionalGlobal:focus, #tasaIVA:focus, #tasaIVAVenta:focus,
        #otrosRubrosGravadosDeclaracion:focus, #metodoProrrateo:focus, #impuestoICE:focus {
            outline:2px solid var(--ui-neon) !important; outline-offset:1px;
            box-shadow:0 0 0 3px rgba(34,211,238,.18) !important;
        }
        label[for="fleteInternacionalGlobal"], label[for="tasaIVA"],
        label[for="tasaIVAVenta"], label[for="otrosRubrosGravadosDeclaracion"],
        label[for="metodoProrrateo"] { color:var(--ui-gold) !important; }
        label[for="impuestoICE"] { color:var(--ui-orange) !important; }
        #tasaIVA + p, #tasaIVA ~ label, #tasaIVAVenta + p,
        #otrosRubrosGravadosDeclaracion + p {
            color:var(--ui-neon) !important; text-shadow:0 0 8px rgba(34,211,238,.12);
        }
        #tasaIVA ~ label { color:var(--ui-white) !important; }
        #tasaIVA ~ label span { color:var(--ui-blue) !important; }
        #fleteInternacionalGlobal ~ *, #impuestoICE ~ * { color:var(--ui-white); }
        #metodoProrrateo option { background:#0B1220; color:var(--ui-white); }
        /* Insignias de los campos: combinan blanco, azul eléctrico y naranja */
        label[for="fleteInternacionalGlobal"] span:last-child { color:var(--ui-blue) !important; background:#10263A !important; }
        label[for="tasaIVA"] span:last-child { color:var(--ui-white) !important; background:#23415A !important; }
        label[for="impuestoICE"] span:last-child { color:var(--ui-orange) !important; background:#3A2114 !important; }
        /* Dashboard: fondos oscuros, contornos visibles y texto de alto contraste */
        #dashboardImportacion { color:var(--ui-white) !important; border-color:#D4AF37 !important; }
        #dashboardImportacion h2, #dashboardImportacion h3,
        #dashboardImportacion .text-\[\#111111\], #dashboardImportacion .text-gray-900,
        #dashboardImportacion .text-gray-800, #dashboardImportacion .text-gray-700,
        #dashboardImportacion .text-gray-600, #dashboardImportacion .text-gray-500 {
            color:var(--ui-white) !important;
        }
        #dashboardImportacion .bg-white, #dashboardImportacion .bg-gray-50,
        #dashboardImportacion .bg-gray-100, #dashboardImportacion .bg-amber-50,
        #dashboardImportacion .bg-blue-50 {
            background:linear-gradient(145deg,#111827,#0b1220) !important;
        }
        #dashboardImportacion [class*="border-"] { border-color:#334155 !important; }
        #dashboardImportacion .rounded-xl, #dashboardImportacion .rounded-lg {
            box-shadow:0 8px 24px rgba(0,0,0,.18);
        }
        #dashboardImportacion [id^="kpi"] { color:var(--ui-gold) !important; }
        #dashboardImportacion canvas { filter:contrast(1.04) saturate(1.12); }
        #dashboardImportacion th { color:var(--ui-gold) !important; background:#172033 !important; }
        #dashboardImportacion td { color:var(--ui-white) !important; }
        #dashboardImportacion .text-\[\#D4AF37\] { color:var(--ui-gold) !important; }
        #dashboardImportacion .text-\[\#FF6B00\] { color:var(--ui-orange) !important; }
        @media(max-width:640px){
            div:has(> label[for="fleteInternacionalGlobal"]),
            div:has(> label[for="tasaIVA"]), div:has(> label[for="tasaIVAVenta"]),
            div:has(> label[for="otrosRubrosGravadosDeclaracion"]),
            div:has(> label[for="metodoProrrateo"]), div:has(> label[for="impuestoICE"]) { padding:11px !important; }
        }

    
        /* Colores neón solicitados SOLO para los tres canales comerciales */
        #cardCanalMayoristaNeon {
            border: 2px solid #00E5FF !important;
            box-shadow: 0 0 0 1px rgba(0,229,255,.25), 0 0 14px rgba(0,229,255,.18) !important;
            background: #0b1420 !important;
        }
        #cardCanalMayoristaNeon > div:first-child > span:first-child {
            color: #00E5FF !important;
            text-shadow: 0 0 7px rgba(0,229,255,.35);
        }
        #cardDistribuidorNeon {
            border: 2px solid #39FF88 !important;
            box-shadow: 0 0 0 1px rgba(57,255,136,.24), 0 0 14px rgba(57,255,136,.17) !important;
            background: #0b1714 !important;
        }
        #cardDistribuidorNeon > div:first-child > span:first-child {
            color: #FFD700 !important;
            text-shadow: 0 0 7px rgba(255,215,0,.65), 0 0 14px rgba(255,215,0,.35);
        }
        #cardPVPNeon {
            border: 2px solid #00E5FF !important;
            box-shadow: 0 0 0 1px rgba(0,229,255,.22), 0 0 14px rgba(57,255,136,.18) !important;
            background: #101725 !important;
        }
        #cardPVPNeon > div:first-child > span:first-child {
            color: #39FF88 !important;
            text-shadow: 0 0 7px rgba(57,255,136,.32);
        }

        /* Contraste premium solicitado: solo parámetros globales seleccionados */
        .premium-param-flete,
        .premium-param-iva,
        .premium-param-iva-venta,
        .premium-param-otros-rubros,
        .premium-param-prorrateo,
        .premium-param-ice {
            border: 2px solid #D4AF37 !important; /* borde externo dorado */
            border-radius: 12px;
        }
        .premium-param-flete label[for="fleteInternacionalGlobal"],
        .premium-param-flete label[for="fleteInternacionalGlobal"] > span:first-child { color:#FFD700 !important; }
        .premium-param-iva label[for="tasaIVA"],
        .premium-param-iva label[for="tasaIVA"] > span:first-child { color:#00E5FF !important; }
        .premium-param-iva-venta label[for="tasaIVAVenta"] { color:#FF8A24 !important; }
        .premium-param-otros-rubros label[for="otrosRubrosGravadosDeclaracion"] { color:#39FF88 !important; }
        .premium-param-prorrateo label[for="metodoProrrateo"],
        .premium-param-prorrateo label[for="metodoProrrateo"] > span:first-child { color:#00E5FF !important; }
        .premium-param-ice label[for="impuestoICE"],
        .premium-param-ice label[for="impuestoICE"] > span:first-child { color:#FF8A24 !important; }
        .premium-param-flete input#fleteInternacionalGlobal,
        .premium-param-iva input#tasaIVA,
        .premium-param-iva-venta input#tasaIVAVenta,
        .premium-param-otros-rubros input#otrosRubrosGravadosDeclaracion,
        .premium-param-prorrateo select#metodoProrrateo,
        .premium-param-ice input#impuestoICE {
            border: 1px solid #FFFFFF !important; /* borde interno blanco */
            color: inherit;
        }
        .premium-param-flete input#fleteInternacionalGlobal { color:#FFD700 !important; }
        .premium-param-iva input#tasaIVA { color:#00E5FF !important; }
        .premium-param-iva-venta input#tasaIVAVenta { color:#FF8A24 !important; }
        .premium-param-otros-rubros input#otrosRubrosGravadosDeclaracion { color:#39FF88 !important; }
        .premium-param-prorrateo select#metodoProrrateo { color:#00E5FF !important; }
        .premium-param-ice input#impuestoICE { color:#FF8A24 !important; }
        .premium-param-iva p,
        .premium-param-iva label.flex,
        .premium-param-iva-venta p,
        .premium-param-otros-rubros p { color:#F8FAFC !important; }
        .premium-param-iva p,
        .premium-param-iva-venta p,
        .premium-param-otros-rubros p { text-shadow:0 0 5px rgba(0,229,255,.24); }
        .premium-param-flete input:focus,
        .premium-param-iva input:focus,
        .premium-param-iva-venta input:focus,
        .premium-param-otros-rubros input:focus,
        .premium-param-prorrateo select:focus,
        .premium-param-ice input:focus { border-color:#FFFFFF !important; outline-color:#FFD700 !important; }

        /* Botón Dashboard transparente; conserva borde y texto neón */
        .ux-nav a.ux-primary[href="#dashboardImportacion"] {
            background: transparent !important;
            background-color: transparent !important;
            box-shadow: none !important;
            border: 1px solid var(--pro-gold, #D4AF37) !important;
            color: var(--pro-orange, #FF8A24) !important;
        }
        .ux-nav a.ux-primary[href="#dashboardImportacion"]:hover,
        .ux-nav a.ux-primary[href="#dashboardImportacion"]:focus-visible {
            background: rgba(255,255,255,.06) !important;
            outline: 2px solid var(--ui-neon, #22D3EE);
            outline-offset: 2px;
        }
    </style>
<style>
#validacionCalculoBanner[data-estado="vacio"] {background:#10283d!important;border-color:#54b8ec!important;color:#e1f4ff!important}
#validacionAcciones button {min-height:44px;padding:8px 14px;border:1px solid currentColor;border-radius:8px;color:inherit;background:transparent;font-weight:700}
input[aria-invalid="true"] {outline:2px solid #ff6969!important;outline-offset:2px}
</style></head>
<body class="bg-gray-100 min-h-screen pt-3 pb-28 md:py-8 px-2.5 sm:px-5 lg:px-8 font-sans text-gray-800">
<header id="mpiCabeceraSistema" class="mpi-cabecera-sistema">
<div class="mpi-cabecera-interior"><div class="mpi-cabecera-texto"><div class="mpi-marca-sistema">MEGA PRIME IMPORT</div><h1>Sistema de Costos de Importación</h1><p>Planifica tu importación · Controla tus costos · Evalúa tu rentabilidad</p></div><img class="mpi-logo-sistema" src="assets/mpi-logo.png" alt="Logo de Mega Prime Importaciones" width="112" height="112"></div>
</header>
<style>
.mpi-cabecera-sistema{background:linear-gradient(110deg,#0d1727,#14233a);border-bottom:1px solid #b99a3f;padding:18px 24px;color:#fff}
.mpi-cabecera-interior{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:112px minmax(0,1fr) 112px;align-items:center;gap:20px}.mpi-cabecera-texto{grid-column:2;min-width:0;text-align:center}.mpi-logo-sistema{grid-column:3;display:block;width:112px;height:112px;object-fit:contain;flex:0 0 auto;border-radius:10px;border:1px solid #796733}
.mpi-marca-sistema{font:800 clamp(14px,1.4vw,19px)/1.4 system-ui,sans-serif;letter-spacing:.14em;color:#edcd69}
body .mpi-cabecera-sistema h1{font:800 clamp(28px,3.5vw,48px)/1.16 system-ui,sans-serif;margin:5px 0 7px;color:#fff!important;overflow-wrap:anywhere}
.mpi-cabecera-sistema p{font:400 clamp(13px,1.15vw,16px)/1.5 system-ui,sans-serif;margin:0;color:#cbd7e5}
@media(max-width:600px){.mpi-cabecera-interior{grid-template-columns:52px minmax(0,1fr) 52px;gap:8px}.mpi-logo-sistema{width:52px;height:52px}.mpi-cabecera-sistema{padding:15px 16px}.mpi-marca-sistema{font-size:12px;letter-spacing:.06em}body .mpi-cabecera-sistema h1{font-size:clamp(24px,5.6vw,34px)}}
</style>
<div id="accesoPrivado" style="background:#101722;color:#edcd69;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;border-bottom:1px solid #b99a3f;font:14px system-ui">
<span>Área privada · Calculadora de liquidación</span>
<form action="admin.php" method="post" style="margin:0"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button name="action" value="logout" style="background:#192536;color:white;border:1px solid #69778b;border-radius:8px;padding:8px 12px;cursor:pointer">Cerrar sesión</button></form>
</div><style>@media print{#accesoPrivado{display:none!important}}</style>
<nav class="mpi-business-nav"><?php if(mpi_sso_enabled()):?><a href="<?=mpi_e(rtrim(mpi_sso_config()['auth_base_url'],'/').'/index.php')?>" target="_blank" rel="noopener noreferrer">Centro de operaciones</a><a href="<?=mpi_e('https://megaprimeimport.com')?>" target="_blank" rel="noopener noreferrer">Tienda pública</a><?php else:?><a href="panel.php">Gestionar tienda y usuarios</a><a href="index.php" target="_blank" rel="noopener">Ver tienda pública</a><?php endif;?></nav>
<section id="mpiBarraGuardado" aria-label="Guardar importación"><div><strong>Guardado de importación</strong><p id="mpiEstadoGuardado" role="status">Sin guardado confirmado en esta sesión.</p><small>Almacenamiento local · La copia JSON respalda también proveedores e historial.</small></div><button type="button" onclick="guardarEmbarqueLocalStorage()">Guardar importación</button><button type="button" onclick="alternarCopiasPremium(true)">Respaldos</button></section>
<style>#mpiBarraGuardado{max-width:1280px;margin:14px auto;display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#101722;border:1px solid #b99a3f;border-radius:12px;color:#fff;font:14px/1.5 system-ui}#mpiBarraGuardado>div{flex:1 1 300px}#mpiBarraGuardado strong{color:#edcd69}#mpiEstadoGuardado{margin:4px 0;color:#ffcf7d;overflow-wrap:anywhere}#mpiEstadoGuardado[data-estado=guardado]{color:#91e9b5}#mpiBarraGuardado small{color:#b9c6d7}#mpiBarraGuardado button{padding:10px 14px;min-height:44px;border:1px solid #b99a3f;border-radius:8px;background:#e4bf53;color:#101722;font-weight:700;cursor:pointer}#mpiBarraGuardado button:focus-visible{outline:3px solid white;outline-offset:3px}@media(max-width:600px){#mpiBarraGuardado button{flex:1}}@media print{#mpiBarraGuardado{display:none!important}}</style>


<div id="avisoAlmacenamiento" role="note"><strong>Datos guardados en este navegador.</strong> No se sincronizan con otros equipos. Antes de cambiar de dispositivo o dirección web, descarga una copia completa y restáurala en el destino. Los usuarios registrados son referencias, no cuentas con acceso.</div>
    <div id="validacionCalculoBanner" class="hidden max-w-7xl mx-auto mb-3 rounded-xl border-2 border-red-400 bg-red-950 text-red-100 p-3 shadow-lg" role="alert" aria-live="assertive">
        <p class="text-xs font-semibold mb-1">Control preventivo: detecta inconsistencias de entrada y cálculo; no reemplaza la validación de tributos, subpartidas ni normativa vigente.</p>
      <div id="validacionCalculoTitulo" class="font-extrabold text-sm mb-1">Revisión requerida antes de calcular</div>
      <ul id="validacionCalculoLista" class="list-disc pl-5 text-xs space-y-1"></ul>
      <div id="validacionAcciones" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px">
        <button type="button" onclick="abrirModalNuevoItem()">＋ Agregar producto</button>
        <button type="button" onclick="document.getElementById('inputExcel').click()">Importar Excel</button>
      </div>
    </div>

    <!-- NOTIFICACIÓN FLOTANTE / TOAST -->
    <div id="toastNotification" class="fixed top-3 left-3 right-3 sm:left-auto sm:right-5 sm:top-5 z-50 hidden transform transition-all duration-300 sm:max-w-md">
        <div id="toastInner" class="bg-[#111111] border-l-4 border-[#D4AF37] text-white px-4 py-3.5 rounded-xl shadow-2xl flex items-start gap-3">
            <svg id="toastIcon" class="w-6 h-6 text-[#D4AF37] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1 text-xs sm:text-sm">
                <p id="toastTitle" class="font-bold text-[#D4AF37] uppercase tracking-wider text-xs">Notificación del Sistema</p>
                <p id="toastMessage" class="text-gray-200 mt-0.5 leading-relaxed">Operación ejecutada con éxito.</p>
            </div>
            <button onclick="cerrarToast()" class="text-gray-400 hover:text-white p-1 transition cursor-pointer" aria-label="Cerrar notificación">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden max-w-7xl mx-auto font-sans">
        
        <!-- ENCABEZADO -->
        <div class="bg-[#111111] border-b-4 border-[#D4AF37] px-4 sm:px-6 py-4 sm:py-5 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3.5">
            <div class="flex items-start sm:items-center space-x-3">
                <div class="p-2.5 bg-[#D4AF37]/10 border border-[#D4AF37]/40 rounded-xl shrink-0">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#D4AF37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-white text-base sm:text-xl font-bold tracking-wide uppercase leading-tight">Sistema de Costos de Importación y Precios</h1>
                    <p class="text-gray-400 text-xs mt-0.5">SENAE / SRI: Merma Real, Asiento Contable, Comparador LCL/FCL, ROI &amp; Punto de Equilibrio · Control de consistencia de costos</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto justify-between lg:justify-end pt-2 lg:pt-0 border-t border-gray-800 lg:border-t-0">
                <div class="flex items-center gap-1.5 bg-gray-900 border border-gray-700 px-2.5 py-1.5 rounded-lg text-[11px] text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>FODINFA: <strong class="text-white">0.5%</strong> | ISD: <strong id="badgeHeaderISD" class="text-[#D4AF37]">Auto</strong> | Seg: <strong id="badgeHeaderSeguro" class="text-blue-400">Auto 1%</strong> | IVA: <strong id="badgeIVA" class="text-white">15%</strong></span>
                </div>
                <span class="text-xs font-bold bg-[#D4AF37] text-[#111111] px-3.5 py-1.5 rounded-full shadow-sm tracking-wide">
                    Ecuador (SENAE &amp; SRI)
                </span>
            </div>
        </div>

        <div class="p-3.5 sm:p-6 space-y-6 sm:space-y-8">

            <!-- BARRA DE ACCIÓN RÁPIDA -->
            <div class="bg-[#111111] text-white p-3.5 sm:p-4 rounded-xl flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3.5 shadow-md">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="bg-[#FF6B00] text-white text-[11px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider shrink-0 mt-0.5 sm:mt-0">Embarque</span>
                    <div>
                        <p id="estadoEmbarqueTexto" class="text-xs sm:text-sm font-semibold text-white">Datos de prueba cargados</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Adaptable para móvil y PC • Suite PREMIUM de Gestión Logística y Financiera</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:flex items-center gap-2 w-full md:w-auto">
                    <button onclick="abrirModalInfoISD()" class="min-h-[44px] text-xs bg-red-500/15 hover:bg-red-500/25 text-red-300 border border-red-500/40 font-bold px-3 py-2 rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Tarifas ISD</span>
                    </button>
                    <button onclick="abrirModalCatalogoICE(null)" class="min-h-[44px] text-xs bg-[#FF6B00]/15 hover:bg-[#FF6B00]/25 text-[#FF6B00] border border-[#FF6B00]/50 font-bold px-3 py-2 rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span>Catálogo ICE</span>
                    </button>
                    <button onclick="borrarDatos()" class="min-h-[44px] text-xs bg-gray-800 hover:bg-gray-700 active:bg-gray-600 text-[#D4AF37] border border-[#D4AF37]/40 font-bold px-3 py-2 rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Borrar</span>
                    </button>
                    <button onclick="abrirModalNuevoItem()" class="min-h-[44px] text-xs bg-[#D4AF37] hover:bg-[#c29d2b] active:scale-[0.99] text-[#111111] font-bold px-3 
py-2 rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5">
    			<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      			    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
   			</svg>
                        <span>Producto</span>
                    </button>
                </div>
            </div>


            <!-- PANEL REORGANIZADO: CONTROL MATEMÁTICO DEL EMBARQUE -->
            <section id="panelAuditoriaCalculo" class="rounded-2xl border border-[#D4AF37]/50 bg-[#111111] p-4 sm:p-5 shadow-lg" aria-labelledby="tituloAuditoriaCalculo">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <div>
                        <p class="text-[10px] font-extrabold tracking-[0.18em] uppercase text-[#D4AF37]">Control de calidad</p>
                        <h2 id="tituloAuditoriaCalculo" class="text-lg sm:text-xl font-black text-white">Auditoría rápida del cálculo</h2>
                        <p class="text-xs text-gray-300 mt-1">Verifica entradas y coherencia aritmética sin modificar los datos del embarque.</p>
                    </div>
                    <button type="button" onclick="validarDatosDashboard()" class="min-h-[42px] rounded-xl border border-[#D4AF37] bg-[#D4AF37] px-4 py-2 text-sm font-extrabold text-[#111111] hover:bg-[#f0d46a]">Ejecutar revisión</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-gray-700 bg-gray-900/70 p-3">
                        <p class="text-xs text-gray-300">Datos de entrada</p>
                        <p id="auditoriaEstadoEntradas" class="mt-1 font-extrabold text-[#D4AF37]" aria-live="polite">Revisando…</p>
                    </div>
                    <div class="rounded-xl border border-gray-700 bg-gray-900/70 p-3">
                        <p class="text-xs text-gray-300">Conciliación CIF</p>
                        <p id="auditoriaEstadoCIF" class="mt-1 font-extrabold text-[#D4AF37]" aria-live="polite">Revisando…</p>
                    </div>
                    <div class="rounded-xl border border-gray-700 bg-gray-900/70 p-3">
                        <p class="text-xs text-gray-300">Costo de bodega</p>
                        <p id="auditoriaEstadoCosto" class="mt-1 font-extrabold text-[#D4AF37]" aria-live="polite">Revisando…</p>
                    </div>
                </div>
                <p id="auditoriaNotaLegal" class="mt-3 text-[11px] leading-relaxed text-orange-200">Esta revisión comprueba consistencia interna; no confirma la clasificación arancelaria, base imponible, exenciones ni tarifas legales vigentes.</p>
            </section>

            <!-- NAVEGACIÓN RÁPIDA PREMIUM -->
            <nav class="ux-nav rounded-xl" aria-label="Navegación rápida del sistema">
                <a class="ux-primary" href="#dashboardImportacion">▦ Dashboard</a>
                <a href="#panelHerramientasPRO">▤ Importaciones</a>
                <a href="#panelPremiumComercial">↗ Rentabilidad</a>
                <a href="#panelProveedoresUX">♧ Proveedores</a>
                <a href="#panelUsuariosUX">♙ Usuarios</a>
                <a href="#panelReportesUX">⇩ Reportes y respaldos</a>
                <button type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑ Arriba</button>
                <button type="button" onclick="validarDatosDashboard()" title="Revisa campos numéricos antes de exportar">✓ Validar datos</button>
            </nav>

            <!-- ESPACIO DE TRABAJO PRO: gestión comercial, alertas y respaldos por archivo -->
            <section id="panelHerramientasPRO" class="space-y-4 ux-section-target" aria-labelledby="tituloHerramientasPRO">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1"><span class="pro-pill">PRO · FASE 1</span><span class="pro-pill">TEMA OSCURO</span><span class="pro-pill">Guardado y respaldos</span></div>
                        <h2 id="tituloHerramientasPRO" class="text-xl sm:text-2xl font-black text-white">Centro de control de importaciones</h2>
                        <p class="pro-muted mt-1">Rentabilidad, simulación, proveedores, cotizaciones, alertas y respaldo.</p><p class="pro-muted mt-1">Premium local: simulador comercial, escenarios guardados y exportación CSV. Para cuentas seguras y sincronización entre equipos se necesita un backend.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="pro-btn" onclick="exportarLiquidacionExcel()">↓ Excel</button>
                        <button class="pro-btn" onclick="exportarReportePDF()">↓ PDF</button>
                        <button class="pro-btn pro-btn-primary" onclick="exportarRespaldoPRO()">Crear respaldo</button>
                    </div>
                </div>
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="pro-kpi"><div class="k">Productos calculados</div><div class="v" id="proKpiProductos">0</div><div class="pro-muted">En el embarque actual</div></div>
                    <div class="pro-kpi"><div class="k">Costo total estimado</div><div class="v" id="proKpiCosto">$0.00</div><div class="pro-muted">Según cálculo actual</div></div>
                    <div class="pro-kpi"><div class="k">Alertas activas</div><div class="v" id="proKpiAlertas">0</div><div class="pro-muted">Umbrales configurables</div></div>
                    <div class="pro-kpi"><div class="k">Respaldo por archivo</div><div class="v text-base" id="proKpiNube">Listo para exportar</div><div class="pro-muted">JSON para guardar y restaurar</div></div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div id="panelProveedoresUX" class="pro-module space-y-3 ux-section-target">
                        <div><h3 class="pro-section-title">Proveedores y cotizaciones</h3><p class="pro-muted mt-1">Registra proveedores y compara precios de compra. Los datos se guardan en este navegador.</p></div>
                        <form id="proProveedorForm" class="grid grid-cols-1 sm:grid-cols-2 gap-3" onsubmit="guardarProveedorPRO(event)">
                            <div><label class="pro-label" for="proProvNombre">Proveedor / empresa</label><input id="proProvNombre" class="pro-field" required maxlength="100" placeholder="Ej. Shanghai Trading"></div>
                            <div><label class="pro-label" for="proProvContacto">Contacto / correo</label><input id="proProvContacto" class="pro-field" maxlength="120" placeholder="Correo o teléfono"></div>
                            <div><label class="pro-label" for="proCotProducto">Producto cotizado</label><input id="proCotProducto" class="pro-field" required maxlength="120" placeholder="Nombre del producto"></div>
                            <div><label class="pro-label" for="proCotPrecio">Precio unitario USD</label><input id="proCotPrecio" class="pro-field" type="number" min="0" step="0.0001" required placeholder="0.00"></div>
                            <div><label class="pro-label" for="proCotCantidad">Cantidad</label><input id="proCotCantidad" class="pro-field" type="number" min="1" step="1" value="1" required></div>
                            <div><label class="pro-label" for="proCotNota">Condiciones / plazo</label><input id="proCotNota" class="pro-field" maxlength="160" placeholder="Incoterm, entrega, vigencia..."></div>
                            <div class="sm:col-span-2 flex flex-wrap gap-2"><button class="pro-btn pro-btn-primary" type="submit">Guardar proveedor y cotización</button><button class="pro-btn" type="button" onclick="limpiarFormularioProveedorPRO()">Limpiar</button></div>
                        </form>
                        <div id="proTablaProveedores" class="pro-table-wrap"><div class="p-4 pro-muted">Todavía no hay proveedores registrados.</div></div>
                    </div>
                    <div class="pro-module space-y-3">
                        <div><h3 class="pro-section-title">Alertas de costos</h3><p class="pro-muted mt-1">Define un costo unitario máximo para detectar productos por encima del presupuesto.</p></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div><label class="pro-label" for="proUmbralCosto">Umbral costo unitario USD</label><input id="proUmbralCosto" class="pro-field" type="number" min="0" step="0.0001" value="100"></div>
                            <div><label class="pro-label" for="proUmbralMargen">Margen mínimo (%)</label><input id="proUmbralMargen" class="pro-field" type="number" min="0" max="100" step="0.5" value="15"></div>
                        </div>
                        <button class="pro-btn pro-btn-primary" onclick="actualizarAlertasPRO()">Revisar costos ahora</button>
                        <div id="proListaAlertas" class="space-y-2"><div class="pro-muted">Calcula el embarque para revisar las alertas.</div></div>
                        <div class="border-t border-slate-700 pt-3"><h4 class="text-sm font-bold text-white">Historial de embarques</h4><p class="pro-muted mt-1 mb-2">Conserva productos y campos del formulario por nombre. El historial anterior sigue disponible. Descarga respaldos JSON periódicos.</p><div class="flex flex-wrap gap-2"><button class="pro-btn" onclick="guardarEmbarqueLocalStorage()">Guardar embarque</button><button class="pro-btn" onclick="cargarEmbarqueLocalStorage()">Cargar embarque</button><button class="pro-btn" onclick="exportarRespaldoPRO()">Descargar JSON</button><label class="pro-btn cursor-pointer">Importar respaldo<input type="file" accept="application/json,.json" class="hidden" onchange="importarRespaldoPRO(event)"></label></div></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div id="panelUsuariosUX" class="pro-module space-y-3 ux-section-target">
                        <div><h3 class="pro-section-title">Usuarios y permisos (preparación)</h3><p class="pro-muted mt-1">Organiza roles de referencia para la futura versión multiusuario. Esta lista local NO es autenticación ni seguridad real.</p></div>
                        <form id="proUsuarioForm" class="grid grid-cols-1 sm:grid-cols-3 gap-3" onsubmit="guardarUsuarioPRO(event)">
                            <div class="sm:col-span-2"><label class="pro-label" for="proUsuarioNombre">Nombre / correo</label><input id="proUsuarioNombre" class="pro-field" required maxlength="120" placeholder="usuario@empresa.com"></div>
                            <div><label class="pro-label" for="proUsuarioRol">Rol</label><select id="proUsuarioRol" class="pro-field"><option>Administrador</option><option>Operador</option><option>Consulta</option></select></div>
                            <div class="sm:col-span-3"><button class="pro-btn pro-btn-primary" type="submit">Añadir usuario de referencia</button></div>
                        </form>
                        <div id="proTablaUsuarios" class="pro-table-wrap"><div class="p-4 pro-muted">No hay usuarios de referencia.</div></div>
                    </div>
                    <div id="panelReportesUX" class="pro-module space-y-3 ux-section-target">
                        <div><h3 class="pro-section-title">Almacenamiento mediante archivos</h3><p class="pro-muted mt-1">Crea un archivo de respaldo para guardar en tu computadora o unidad externa y restáuralo cuando lo necesites.</p></div>
                        <div class="rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-3 text-xs text-emerald-200">Tus respaldos se descargan en tu dispositivo. Para trasladar información a otra computadora, importa el archivo JSON allí. No se suben datos a servidores.</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><button class="pro-btn pro-btn-primary" onclick="exportarRespaldoPRO()">Descargar respaldo completo (.json)</button><label class="pro-btn cursor-pointer text-center">Restaurar desde archivo JSON<input type="file" accept="application/json,.json" class="hidden" onchange="importarRespaldoPRO(event)"></label></div>
                        <div class="flex flex-wrap gap-2"><button class="pro-btn" onclick="exportarProveedoresCSVPRO()">Exportar proveedores a CSV</button><button class="pro-btn" onclick="document.getElementById('proArchivoRespaldo').click()">Seleccionar archivo</button><input id="proArchivoRespaldo" type="file" accept="application/json,.json" class="hidden" onchange="importarRespaldoPRO(event)"></div>
                        <div class="rounded-xl border border-slate-600 p-3 space-y-2" aria-live="polite">
                            <div class="flex flex-wrap items-center justify-between gap-2"><strong class="text-sm">Estado del almacenamiento local</strong><button type="button" class="pro-btn" onclick="verificarIntegridadLocalPRO()">Revisar estado</button></div>
                            <p id="proEstadoIntegridad" class="pro-muted">Comprueba la disponibilidad del almacenamiento y la cantidad de registros guardados en este navegador.</p>
                        </div>
                        <p class="pro-muted">Recomendación: descarga un respaldo antes de borrar productos o reemplazar información. El JSON incluye partidas, datos locales de la aplicación, historial, cotizaciones, roles de referencia, bitácora y parámetros visibles.</p>
                    </div>
                </div>
            </section>

            <!-- PANEL PREMIUM: simulador comercial y escenarios guardados -->
            <section id="panelPremiumComercial" class="pro-module space-y-4 my-4 ux-section-target" aria-labelledby="tituloPremiumComercial">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1"><span class="pro-pill">PREMIUM</span><span class="pro-pill">SIMULACIÓN COMERCIAL</span></div>
                        <h2 id="tituloPremiumComercial" class="pro-section-title">Simulador de rentabilidad y escenarios</h2>
                        <p class="pro-muted mt-1">Prueba precios y cantidades sin cambiar los valores de tu cálculo de importación. Los escenarios se guardan localmente en este navegador.</p>
                    </div>
                    <div class="flex flex-wrap gap-2"><button class="pro-btn" type="button" onclick="exportarEscenariosPremiumCSV()">Exportar escenarios CSV</button><button class="pro-btn" type="button" onclick="limpiarEscenariosPremium()">Vaciar escenarios</button></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div><label class="pro-label" for="premiumProducto">Producto del embarque</label><select id="premiumProducto" class="pro-field" onchange="actualizarSimuladorPremium()"><option value="">Calcula o agrega productos primero</option></select></div>
                    <div><label class="pro-label" for="premiumCantidad">Unidades a vender</label><input id="premiumCantidad" class="pro-field" type="number" min="1" step="1" value="100" oninput="actualizarSimuladorPremium()"></div>
                    <div><label class="pro-label" for="premiumPrecioVenta">Precio de venta unitario USD</label><input id="premiumPrecioVenta" class="pro-field" type="number" min="0" step="0.0001" value="0" oninput="actualizarSimuladorPremium()"></div>
                    <div><label class="pro-label" for="premiumCostoExtra">Gasto adicional por unidad USD</label><input id="premiumCostoExtra" class="pro-field" type="number" min="0" step="0.0001" value="0" oninput="actualizarSimuladorPremium()"></div>
                </div>
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="pro-kpi"><div class="k">Costo unitario estimado</div><div class="v" id="premiumCostoUnit">$0.00</div><div class="pro-muted">Incluye gasto adicional</div></div>
                    <div class="pro-kpi"><div class="k">Venta total proyectada</div><div class="v" id="premiumVentaTotal">$0.00</div><div class="pro-muted">Precio × unidades</div></div>
                    <div class="pro-kpi"><div class="k">Utilidad estimada</div><div class="v" id="premiumUtilidad">$0.00</div><div class="pro-muted">Antes de gastos no incluidos</div></div>
                    <div class="pro-kpi"><div class="k">Margen sobre ventas</div><div class="v" id="premiumMargen">0.0%</div><div class="pro-muted">Utilidad ÷ ventas</div></div>
                </div>
                <div class="flex flex-wrap items-center gap-2"><button class="pro-btn pro-btn-primary" type="button" onclick="guardarEscenarioPremium()">Guardar escenario</button><span id="premiumPuntoEquilibrio" class="pro-muted">Punto de equilibrio: $0.00 por unidad</span></div>
                <div id="premiumTablaEscenarios" class="pro-table-wrap"><div class="p-4 pro-muted">Aún no hay escenarios guardados.</div></div>
                <p class="pro-muted text-xs">Nota: la simulación usa el costo unitario calculado por la herramienta y suma el gasto adicional ingresado. No reemplaza la liquidación aduanera ni incluye automáticamente costos comerciales que no hayas declarado.</p>
            </section>

            <!-- PREMIUM: PANEL EJECUTIVO Y BITÁCORA LOCAL -->
            <section id="panelPremium" class="space-y-4 ux-section-target" aria-labelledby="tituloPremium">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                    <div><div class="flex items-center gap-2 mb-1"><span class="pro-pill">PREMIUM · HERRAMIENTAS</span></div><h2 id="tituloPremium" class="text-xl sm:text-2xl font-black text-white">Panel ejecutivo y control comercial</h2><p class="pro-muted mt-1">Calcula precios sugeridos, estima utilidad y conserva una bitácora de acciones importantes en este navegador.</p></div>
                    <button class="pro-btn" type="button" onclick="premiumExportarBitacora()">Exportar bitácora CSV</button>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="pro-module space-y-3">
                        <h3 class="pro-section-title">Simulador de precio y margen</h3>
                        <p class="pro-muted">Usa el costo total actual del embarque. Es una simulación comercial; no cambia las partidas ni las fórmulas tributarias.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div><label class="pro-label" for="premiumExecUnidades">Unidades vendibles</label><input id="premiumExecUnidades" class="pro-field" type="number" min="1" step="1" value="1" oninput="premiumCalcularEscenario()"></div>
                            <div><label class="pro-label" for="premiumExecMargen">Margen objetivo sobre venta (%)</label><input id="premiumExecMargen" class="pro-field" type="number" min="0" max="95" step="0.5" value="30" oninput="premiumCalcularEscenario()"></div>
                            <div><label class="pro-label" for="premiumExecGastosVenta">Gastos comerciales totales USD</label><input id="premiumExecGastosVenta" class="pro-field" type="number" min="0" step="0.0001" value="0" oninput="premiumCalcularEscenario()"></div>
                            <div><label class="pro-label" for="premiumExecPrecioManual">Precio de venta manual (opcional)</label><input id="premiumExecPrecioManual" class="pro-field" type="number" min="0" step="0.0001" placeholder="Dejar vacío para precio sugerido" oninput="premiumCalcularEscenario()"></div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="pro-kpi"><div class="k">Costo por unidad</div><div class="v" id="premiumExecCostoUnidad">$0.00</div></div>
                            <div class="pro-kpi"><div class="k">Precio sugerido</div><div class="v" id="premiumExecPrecioSugerido">$0.00</div></div>
                            <div class="pro-kpi"><div class="k">Utilidad estimada</div><div class="v" id="premiumExecUtilidad">$0.00</div></div>
                            <div class="pro-kpi"><div class="k">Margen estimado</div><div class="v" id="premiumExecMargenReal">0.0%</div></div>
                        </div>
                        <p id="premiumExecNotaEscenario" class="pro-muted">Actualiza el embarque para ver el cálculo comercial.</p>
                    </div>
                    <div class="pro-module space-y-3">
                        <div class="flex items-center justify-between gap-2"><h3 class="pro-section-title">Bitácora de actividad</h3><span id="premiumConteoBitacora" class="pro-pill">0 eventos</span></div>
                        <p class="pro-muted">Registra acciones clave para seguimiento interno. La bitácora permanece en este navegador y puede incluirse en un respaldo.</p>
                        <form class="grid grid-cols-1 sm:grid-cols-3 gap-2" onsubmit="premiumRegistrarManual(event)">
                            <div class="sm:col-span-2"><label class="pro-label" for="premiumNotaActividad">Nota / acción realizada</label><input id="premiumNotaActividad" class="pro-field" maxlength="220" required placeholder="Ej. Validé cotización del proveedor"></div>
                            <div class="flex items-end"><button class="pro-btn pro-btn-primary w-full" type="submit">Registrar</button></div>
                        </form>
                        <div id="premiumBitacoraLista" class="pro-table-wrap"><div class="p-4 pro-muted">Todavía no hay eventos.</div></div>
                        <div class="flex flex-wrap gap-2"><button class="pro-btn" type="button" onclick="premiumRegistrar('Respaldo manual solicitado')">Registrar revisión</button><button class="pro-btn" type="button" onclick="premiumLimpiarBitacora()">Vaciar bitácora</button></div>
                    </div>
                </div>
                <div class="rounded-xl border border-amber-400/30 bg-amber-400/5 p-3 text-xs text-amber-100"><strong>Nota de seguridad:</strong> Los roles de esta lista son referencias y los datos viven en el navegador. El paquete PHP incorpora un acceso administrativo separado; la vista previa HTML no tiene autenticación. Aún no hay permisos por usuario ni sincronización entre dispositivos.</div>
                <div class="rounded-xl border border-sky-400/30 bg-sky-400/5 p-3 text-xs text-sky-100"><strong>Control tributario:</strong> los valores de arancel, ICE, IVA e ISD son estimaciones parametrizadas, no una liquidación oficial. El arancel/ICE depende de la subpartida y las reglas vigentes; ISD 2026 puede ser 5%, 2.5% o 0% según operación y listado aplicable. Verifique contra SENAE/SRI y la declaración real antes de pagar.</div>
            </section>

            <!-- PANEL 4 NUEVAS FUNCIONALIDADES AVANZADAS (Literal 1, 2, 4) -->
            <div id="mpiLogistica" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- 1. COMPARADOR LOGÍSTICO LCL vs FCL (W/M) -->
                <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-200 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-xs font-black uppercase text-blue-950 flex items-center gap-1.5">
                                <span class="w-2 h-3 bg-blue-600 rounded-sm"></span>
                                <span>Logística LCL vs. FCL (W/M)</span>
                            </h3>
                            <span id="badgeRecomendacionContenedor" class="text-[9px] font-extrabold bg-blue-600 text-white px-2 py-0.5 rounded">Calculando</span>
                        </div>
                        <p class="text-[11px] text-gray-600 mb-2.5">Regla Peso vs. Medida (Ton / CBM):</p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-white p-2 rounded-lg border border-blue-100">
                                <span class="text-[9px] text-gray-500 font-bold block">Volumen:</span>
                                <strong id="kpiLogisticaCBM" class="text-blue-900 text-xs">0.00 m³</strong>
                            </div>
                            <div class="bg-white p-2 rounded-lg border border-blue-100">
                                <span class="text-[9px] text-gray-500 font-bold block">Peso Carga:</span>
                                <strong id="kpiLogisticaPeso" class="text-blue-900 text-xs">0.00 Ton</strong>
                            </div>
                        </div>
                    </div>
                    <p id="kpiLogisticaConsejo" class="text-[10px] text-blue-800 mt-2 font-medium bg-blue-100/60 p-1.5 rounded-lg border border-blue-200">
                        Analizando flete óptimo...
                    </p>
                </div>

                <!-- 2. CAPITAL DE TRABAJO Y DESEMBOLSO INICIAL -->
                <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-200 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-xs font-black uppercase text-emerald-950 flex items-center gap-1.5">
                                <span class="w-2 h-3 bg-emerald-600 rounded-sm"></span>
                                <span>Capital Total Requerido</span>
                            </h3>
                            <span class="text-[9px] font-extrabold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">Desembolso</span>
                        </div>
                        <p class="text-[11px] text-gray-600 mb-2">Liquidez total para traer el embarque:</p>
                        <p id="kpiDesembolsoTotalRequerido" class="text-lg font-black text-emerald-900">$0.00</p>
                    </div>
                    <div class="text-[10px] text-gray-600 pt-1.5 border-t border-emerald-200/80 space-y-0.5">
                        <div class="flex justify-between"><span>Pago exterior (FOB + flete + seguro):</span><strong id="kpiSubDesembolsoExterior">$0.00</strong></div>
                        <div class="flex justify-between"><span>Aduana + IVA + ISD + locales + otros:</span><strong id="kpiSubDesembolsoLocal">$0.00</strong></div>
                    </div>
                </div>

                <!-- 3. RETORNO DE INVERSIÓN (ROI) Y RENTABILIDAD GLOBAL -->
                <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-200 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-xs font-black uppercase text-amber-950 flex items-center gap-1.5">
                                <span class="w-2 h-3 bg-[#D4AF37] rounded-sm"></span>
                                <span>Retorno de Inversión (ROI)</span>
                            </h3>
                            <span class="text-[9px] font-extrabold bg-amber-100 text-amber-900 px-2 py-0.5 rounded">Rentabilidad</span>
                        </div>
                        <p class="text-[11px] text-gray-600 mb-1">Rendimiento sobre capital invertido:</p>
                        <div class="flex items-baseline gap-2">
                            <p id="kpiROIPVP" class="text-lg font-black text-amber-900">0.0%</p>
                            <span class="text-[10px] text-gray-500 font-bold">(Canal PVP)</span>
                        </div>
                    </div>
                    <div class="text-[10px] text-gray-600 pt-1.5 border-t border-amber-200/80 space-y-0.5">
                        <div class="flex justify-between"><span>ROI Mayorista:</span><strong id="kpiROIMayorista" class="text-gray-900">0.0%</strong></div>
                        <div class="flex justify-between"><span>ROI Distribuidor:</span><strong id="kpiROIEventual" class="text-gray-900">0.0%</strong></div>
                    </div>
                </div>

                <!-- 4. PUNTO DE EQUILIBRIO (BREAK-EVEN DEL CONTENEDOR) -->
                <div class="bg-purple-50/50 p-4 rounded-xl border border-purple-200 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <h3 class="text-xs font-black uppercase text-purple-950 flex items-center gap-1.5">
                                <span class="w-2 h-3 bg-purple-600 rounded-sm"></span>
                                <span>Punto de Equilibrio</span>
                            </h3>
                            <span class="text-[9px] font-extrabold bg-purple-100 text-purple-900 px-2 py-0.5 rounded">Break-Even</span>
                        </div>
                        <p class="text-[11px] text-gray-600 mb-1">Unidades y % para recuperar inversión:</p>
                        <div class="flex items-baseline gap-2">
                            <p id="kpiPuntoEquilibrioPct" class="text-lg font-black text-purple-900">0.0%</p>
                            <span class="text-[10px] text-gray-500 font-bold">del embarque</span>
                        </div>
                    </div>
                    <div class="text-[10px] text-gray-600 pt-1.5 border-t border-purple-200/80 space-y-0.5">
                        <div class="flex justify-between"><span>Unidades a vender (PVP):</span><strong id="kpiPuntoEquilibrioUnidades">0 u</strong></div>
                        <div class="flex justify-between"><span>Ventas de Equilibrio:</span><strong id="kpiPuntoEquilibrioUSD">$0.00</strong></div>
                    </div>
                </div>

            </div>

            <!-- SIMULADOR WHAT-IF Y CONTROLES ADICIONALES -->
            <div id="mpiSimulacion" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-8 bg-orange-50/30 p-4 rounded-xl border border-orange-200 flex flex-col justify-between">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <h3 class="text-xs font-black uppercase text-[#FF6B00] flex items-center gap-2">
                            <span class="w-2 h-3 bg-[#FF6B00] rounded-sm"></span>
                            <span>Simulador de Escenarios What-If (Sensibilidad)</span>
                        </h3>
                        <span class="text-[10px] text-gray-500 font-semibold">Simula fluctuaciones en tiempo real sin alterar la plantilla</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div class="bg-white p-2.5 rounded-xl border border-orange-200/80 shadow-xs">
                            <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1">
                                <span>Variación Flete Internacional:</span>
                                <span id="labelSliderFlete" class="bg-orange-100 text-[#FF6B00] px-2 py-0.5 rounded font-black">0%</span>
                            </div>
                            <input type="range" id="sliderFletePct" min="-50" max="100" value="0" step="5" oninput="aplicarSimuladorWhatIf()" class="w-full accent-[#FF6B00] cursor-pointer">
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-orange-200/80 shadow-xs">
                            <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1">
                                <span>Variación Aranceles Aduaneros:</span>
                                <span id="labelSliderArancel" class="bg-orange-100 text-[#FF6B00] px-2 py-0.5 rounded font-black">0%</span>
                            </div>
                            <input type="range" id="sliderArancelPct" min="-50" max="100" value="0" step="5" oninput="aplicarSimuladorWhatIf()" class="w-full accent-[#FF6B00] cursor-pointer">
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <div>
                        <h3 class="text-xs font-black uppercase text-[#111111] flex items-center gap-2 mb-1">
                            <span class="w-2 h-3 bg-[#D4AF37] rounded-sm"></span>
                            <span>Importaciones guardadas</span>
                        </h3>
                        <p class="text-[11px] text-gray-500 mb-2">Guarda productos y campos del formulario con un nombre:</p>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex gap-1.5">
                            <input type="text" id="nombreCotizacionInput" maxlength="120" aria-label="Nombre de la importación" placeholder="Nombre de la importación" class="w-full bg-white border border-gray-300 rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-[#D4AF37]">
                            <button onclick="guardarEmbarqueLocalStorage()" class="bg-[#111111] text-[#D4AF37] px-3 py-1 rounded-lg text-xs font-bold hover:bg-gray-800 shrink-0 cursor-pointer transition">Guardar importación</button>
                        </div>
                        <div class="flex gap-1.5">
                            <select id="selectCotizacionesGuardadas" class="w-full bg-white border border-gray-300 rounded-lg px-2 py-1 text-xs text-gray-700 focus:outline-none focus:border-[#D4AF37]">
                                <option value="">-- Cargar cotización guardada --</option>
                            </select>
                            <button onclick="cargarEmbarqueLocalStorage()" class="bg-gray-200 text-gray-700 border border-gray-300 px-3 py-1 rounded-lg text-xs font-bold hover:bg-gray-300 shrink-0 cursor-pointer transition">Cargar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOQUE 1: CONFIGURACIÓN DE GASTOS LOCALES, FLETE, SEGURO AUTOMÁTICO E ISD -->
            <div id="mpiGastos" class="bg-gray-50 rounded-xl border border-gray-200 shadow-sm overflow-hidden"><div class="pro-module" style="margin:12px"><h3 class="pro-section-title">Certificado de origen y tarifa preferencial</h3><p class="pro-muted">En Productos, pulsa «Configurar» en la columna % Arancel para ingresar el arancel general y la tarifa final preferencial de cada partida; marca «Aplicar certificado de origen» y guarda los cambios. Por ejemplo: general 20 %, preferencial 17 %. Ingresa 17, no el descuento de 3 puntos. La aplicación no verifica certificados ni consulta automáticamente el cronograma. Confirma subpartida, acuerdo y año. El certificado no modifica por sí solo FODINFA, ICE ni ISD. El IVA se recalcula con el arancel aplicado.</p><label class="pro-label" for="mpiReferenciaOrigen">Referencia del certificado / acuerdo / año (opcional)</label><input id="mpiReferenciaOrigen" class="pro-field" maxlength="180" placeholder="Ej. Ecuador–China · 2026 · certificado…"><p class="pro-muted">Si activas el simulador de variación arancelaria, este ajustará la tarifa seleccionada. Usa variación 0 % para el cálculo sin simulación.</p></div>
                <div onclick="toggleSeccion('contenidoBloque1', 'iconoAcordeon1')" class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-2 cursor-pointer select-none hover:bg-gray-100/70 transition">
                    <h3 class="text-[#111111] font-bold text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-4 bg-[#FF6B00] inline-block rounded-sm shrink-0"></span>
                        <span>1. Gastos Locales, Flete Internacional, ISD Automático y Seguro Automático</span>
                    </h3>
                    <div class="flex items-center gap-2.5 ml-auto">
                        <span class="text-[11px] text-gray-500 font-medium hidden sm:inline">Total Locales + Bancos + ISD:</span>
                        <span id="resumenGastosLocalesBadge" class="text-xs sm:text-sm font-extrabold bg-[#111111] text-[#D4AF37] px-3 py-1 rounded-lg">$1,500.00</span>
                        <button type="button" class="p-1.5 rounded-lg bg-white border border-gray-300 text-gray-700" aria-label="Mostrar u ocultar gastos locales">
                            <svg id="iconoAcordeon1" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="contenidoBloque1" class="px-4 pb-5 sm:px-5 sm:pb-5 pt-1 border-t border-gray-200/80 space-y-5">
                    
                    <!-- 8 Rubros Operativos y Bancarios -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mt-3">
                        <div>
                            <label for="gastosAgenteAduana" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>1. Honorarios Agente de Aduana ($)</span>
                                <span class="text-[10px] font-extrabold bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded shrink-0">Aduana</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="gastosAgenteAduana" value="260.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="tasasPortuarias" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>2. Tasas Portuarias y Operador ($)</span>
                                <span class="text-[10px] font-extrabold bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded shrink-0">Puerto</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="tasasPortuarias" value="190.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="gastosNaviera" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>3. Gastos Naviera (Handling, BL) ($)</span>
                                <span class="text-[10px] font-extrabold bg-gray-200 text-gray-800 px-1.5 py-0.5 rounded shrink-0">Naviera</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="gastosNaviera" value="180.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="gastosContenedor" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>4. Turno y Devol. Contenedor ($)</span>
                                <span class="text-[10px] font-extrabold bg-gray-200 text-gray-800 px-1.5 py-0.5 rounded shrink-0">Patio</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="gastosContenedor" value="220.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="transporteDescarga" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>5. Flete Terrestre + Descarga ($)</span>
                                <span class="text-[10px] font-extrabold bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded shrink-0">Logística</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="transporteDescarga" value="350.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="gastosViaticos" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>6. Viáticos y Alimentación ($)</span>
                                <span class="text-[10px] font-extrabold bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded shrink-0">Personal</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="gastosViaticos" value="85.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="extrasRuta" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>7. Extras de Ruta (Peajes, Custodia) ($)</span>
                                <span class="text-[10px] font-extrabold bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded shrink-0">Ruta</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="extrasRuta" value="65.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                        <div>
                            <label for="comisionBancos" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>8. Comisión Bancos (Swift / Giros) ($)</span>
                                <span class="text-[10px] font-extrabold bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded shrink-0">Bancos</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="comisionBancos" value="65.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                        </div>
                    </div>

                    <!-- PANELES GEMELOS: 9. ISD AUTOMÁTICO Y 11. SEGURO AUTOMÁTICO -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        
                        <!-- 9) MÓDULO ISD SRI ECUADOR -->
                        <div class="bg-white border-2 border-red-200 rounded-xl p-4 shadow-xs space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black uppercase bg-red-600 text-white px-2 py-0.5 rounded">9. SRI ISD Ecuador</span>
                                    <h4 class="text-xs sm:text-sm font-extrabold text-[#111111] uppercase">Impuesto a la Salida de Divisas (ISD)</h4>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" onclick="abrirModalInfoISD()" class="text-[11px] font-bold text-red-700 hover:underline cursor-pointer">
                                        Ver Tarifas Vigentes
                                    </button>
                                    <span id="badgeModoISD" class="text-[10px] font-extrabold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">Cálculo Automático</span>
                                </div>
                            </div>

                            <!-- Botones Rápidos de Tarifas ISD -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 mb-1.5">Seleccionar Tarifa Vigente ISD:</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                    <button type="button" id="btnISD5" onclick="seleccionarTarifaISDGlobal('5')" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>General 5%</span>
                                        <span class="text-[9px] font-normal opacity-80">Giros / Proveedores</span>
                                    </button>
                                    <button type="button" id="btnISD25" onclick="seleccionarTarifaISDGlobal('2.5')" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>Diferenciada 2.5%</span>
                                        <span class="text-[9px] font-normal opacity-80">Subpartidas habilitadas</span>
                                    </button>
                                    <button type="button" id="btnISD0" onclick="seleccionarTarifaISDGlobal('0')" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>Tarifa 0%</span>
                                        <span class="text-[9px] font-normal opacity-80">Solo casos previstos en norma</span>
                                    </button>
                                    <button type="button" id="btnISDMixto" onclick="seleccionarTarifaISDGlobal('POR_PARTIDA')" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>Por Partida</span>
                                        <span class="text-[9px] font-normal opacity-80">5% / 2.5% / 0%</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <div class="sm:col-span-5">
                                    <label for="baseCalculoISD" class="block text-[11px] font-bold text-gray-700 mb-1">Base Imponible Giro Exterior:</label>
                                    <select id="baseCalculoISD" onchange="activarModoAutoISD()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-2.5 py-2 text-xs font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                        <option value="CIF" selected>Valor en Aduana estimado (CIF)</option>
                                        <option value="FOB_FLETE">FOB + Flete Internacional</option>
                                        <option value="FOB">Solo Valor FOB Mercadería (estimación)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="modoCalculoISD" class="block text-[11px] font-bold text-gray-700 mb-1">Modo ISD:</label>
                                    <select id="modoCalculoISD" onchange="recalcularTodo()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-2.5 py-2 text-xs font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                        <option value="AUTO" selected>Automático</option>
                                        <option value="MANUAL">Manual ($)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-4">
                                    <label for="impuestoISD" class="block text-[11px] font-bold text-red-700 mb-1 flex items-center justify-between">
                                        <span>Monto ISD Total ($)</span>
                                        <span id="labelPorcentajeEfectivoISD" class="text-[10px] font-extrabold text-red-600">Auto</span>
                                    </label>
                                    <input type="number" inputmode="decimal" step="0.0001" min="0" id="impuestoISD" value="85.00" oninput="alEditarISDManual()" class="w-full min-h-[46px] bg-red-50/40 border border-red-300 rounded-xl px-3 py-2 text-base sm:text-sm font-black text-[#111111] focus:outline-none focus:border-red-500 gold-glow">
                                </div>
                            </div>
                            <p id="resumenFormulaISD" class="text-[11px] text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                                El cálculo automático es orientativo. La base, exenciones y tarifa dependen del pago, la subpartida y la normativa vigente; confirme antes de usarlo como liquidación.
                            </p>
                        </div>

                        <!-- 11) MÓDULO SEGURO INTERNACIONAL AUTOMÁTICO -->
                        <div class="bg-white border-2 border-blue-200 rounded-xl p-4 shadow-xs space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black uppercase bg-blue-600 text-white px-2 py-0.5 rounded">11. Suma al CIF</span>
                                    <h4 class="text-xs sm:text-sm font-extrabold text-[#111111] uppercase">Seguro Internacional de Transporte</h4>
                                </div>
                                <span id="badgeModoSeguro" class="text-[10px] font-extrabold bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Cálculo Automático (1.00%)</span>
                            </div>

                            <!-- Botones Rápidos de Tasa de Seguro -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-[11px] font-bold text-gray-600">Tasa de seguro estimada (%):</label>
                                    <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-blue-800 cursor-pointer">
                                        <input type="checkbox" id="chkReemplazarSeguroPlantilla" checked onchange="recalcularTodo()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>Reemplazar seguro unitario por cálculo automático</span>
                                    </label>
                                </div>
                                <div class="grid grid-cols-4 gap-1.5">
                                    <button type="button" onclick="fijarTasaSeguro(0.50)" id="btnSeg05" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>0.50%</span>
                                        <span class="text-[9px] font-normal opacity-80">Corporativo</span>
                                    </button>
                                    <button type="button" onclick="fijarTasaSeguro(1.00)" id="btnSeg10" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>1.00%</span>
                                        <span class="text-[9px] font-normal opacity-80">Ejemplo editable</span>
                                    </button>
                                    <button type="button" onclick="fijarTasaSeguro(1.50)" id="btnSeg15" class="min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border transition cursor-pointer flex flex-col items-center justify-center">
                                        <span>1.50%</span>
                                        <span class="text-[9px] font-normal opacity-80">Carga Especial</span>
                                    </button>
                                    <div class="relative">
                                        <input type="number" inputmode="decimal" step="0.05" min="0" max="20" id="tasaSeguroPct" value="1.00" oninput="activarModoAutoSeguro()" class="w-full min-h-[40px] bg-blue-50/50 border border-blue-300 rounded-lg px-2 py-1 text-center text-xs font-black text-blue-900 focus:outline-none focus:border-blue-600" title="Porcentaje de seguro automático">
                                        <span class="block text-[9px] text-center text-gray-500 font-semibold mt-0.5">% Personalizado</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <div class="sm:col-span-5">
                                    <label for="baseCalculoSeguro" class="block text-[11px] font-bold text-gray-700 mb-1">Base Cálculo del Seguro:</label>
                                    <select id="baseCalculoSeguro" onchange="activarModoAutoSeguro()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-2.5 py-2 text-xs font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                        <option value="FOB_FLETE" selected>FOB + Flete (CFR)</option>
                                        <option value="FOB">Solo Valor FOB</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-3">
                                    <label for="modoCalculoSeguro" class="block text-[11px] font-bold text-gray-700 mb-1">Modo Seguro:</label>
                                    <select id="modoCalculoSeguro" onchange="recalcularTodo()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-2.5 py-2 text-xs font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                        <option value="AUTO" selected>Automático (%)</option>
                                        <option value="MANUAL">Manual ($)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-4">
                                    <label for="seguroInternacionalGlobal" class="block text-[11px] font-bold text-blue-800 mb-1 flex items-center justify-between">
                                        <span>Seguro Total ($)</span>
                                        <span class="text-[10px] font-extrabold text-blue-600">Suma al CIF</span>
                                    </label>
                                    <input type="number" inputmode="decimal" step="0.0001" min="0" id="seguroInternacionalGlobal" value="0.00" oninput="alEditarSeguroManual()" class="w-full min-h-[46px] bg-blue-50/40 border border-blue-300 rounded-xl px-3 py-2 text-base sm:text-sm font-black text-[#111111] focus:outline-none focus:border-blue-600 gold-glow">
                                </div>
                            </div>
                            <p id="resumenFormulaSeguro" class="text-[11px] text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                                Si desmarca la casilla, el seguro unitario de cada partida se suma al seguro global prorrateado. Revise que no esté registrando dos veces la misma póliza.
                            </p>
                        </div>
                    </div>

                    <!-- PARÁMETROS GLOBALES COMPLEMENTARIOS -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3.5 sm:gap-4">
                        <div class="bg-blue-50/40 p-2.5 rounded-xl border border-blue-200/80 premium-param-flete">
                            <label for="fleteInternacionalGlobal" class="block text-xs font-bold text-[#111111] mb-1.5 flex items-center justify-between gap-1">
                                <span>10. Flete Internacional Adicional ($)</span>
                                <span class="text-[10px] font-extrabold bg-[#111111] text-[#D4AF37] px-1.5 py-0.5 rounded shrink-0">Suma al CIF</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="fleteInternacionalGlobal" value="0.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-blue-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                            <p class="text-[10px] text-gray-700 mt-1">Ingrese solo el flete adicional aplicable al valor en aduana. Evite duplicar flete por partida y, si la factura incluye IVA u otros conceptos, verifique su desglose antes de sumar.</p>
                        </div>

                        <div class="bg-amber-50/50 p-2.5 rounded-xl border border-[#D4AF37]/60 premium-param-iva">
                            <label for="tasaIVA" class="block text-xs font-bold text-[#111111] mb-1.5 flex items-center justify-between gap-1">
                                <span>12. IVA Vigente Ecuador (%)</span>
                                <span class="text-[10px] font-extrabold bg-[#D4AF37] text-[#111111] px-1.5 py-0.5 rounded shrink-0">SRI</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.5" min="0" max="30" id="tasaIVA" value="15" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-[#D4AF37] rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-black text-[#111111] focus:outline-none focus:border-[#111111] gold-glow">
                            <p class="text-[10px] text-gray-600 mt-1">Tasa editable. Confirme la tarifa aplicable a la fecha, subpartida y régimen antes de liquidar.</p>
                            <label class="flex items-start gap-2 mt-2 text-[10px] font-semibold text-gray-700"><input type="checkbox" id="ivaImportacionRecuperable" checked onchange="recalcularTodo()" class="mt-0.5 rounded border-gray-300"><span>IVA de importación recuperable como crédito tributario. Desmarque si no es recuperable; en ese caso se incorpora al costo.</span></label>
                        </div>

                        <div class="bg-[#111827] p-2.5 rounded-xl border border-[#D4AF37]/60 premium-param-iva-venta">
                            <label for="tasaIVAVenta" class="block text-xs font-bold text-[#FFD76A] mb-1.5">IVA para precio de venta (%)</label>
                            <input type="number" inputmode="decimal" step="0.5" min="0" max="30" id="tasaIVAVenta" value="15" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-[#1F2937] border border-[#D4AF37] rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-black text-[#FFD76A] focus:outline-none focus:border-[#FFD76A]">
                            <p class="text-[10px] text-[#E9C96A] mt-1">Independiente del IVA de importación. No asuma que la tasa de venta es 15 % para todos los productos: confirme el tratamiento vigente por tipo de bien, incluido material de construcción, según el SRI.</p>
                        </div>

                        <div class="p-2.5 rounded-xl border border-violet-200 bg-violet-50/50 premium-param-otros-rubros">
                            <label for="otrosRubrosGravadosDeclaracion" class="block text-xs font-bold text-[#111111] mb-1.5">Otros rubros gravados en declaración (USD)</label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="otrosRubrosGravadosDeclaracion" value="0.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-violet-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-violet-500 gold-glow" aria-describedby="ayudaOtrosRubrosGravados">
                            <p id="ayudaOtrosRubrosGravados" class="text-[10px] text-gray-600 mt-1">Solo tasas, derechos o recargos que efectivamente consten en la declaración aduanera. No ingresar gastos locales comerciales aquí.</p>
                        </div>

                        <div class="p-2.5 rounded-xl border border-gray-200 bg-white premium-param-prorrateo">
                            <label for="metodoProrrateo" class="block text-xs font-bold text-gray-700 mb-1.5 flex items-center justify-between gap-1">
                                <span>13. Prorrateo Gastos Locales</span>
                                <span class="text-[10px] font-extrabold bg-gray-200 text-gray-800 px-1.5 py-0.5 rounded shrink-0">Criterio</span>
                            </label>
                            <select id="metodoProrrateo" onchange="recalcularTodo()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-3 py-2.5 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                <option value="CIF">Por Valor CIF (SENAE)</option>
                                <option value="FOB">Por Valor FOB</option>
                                <option value="PESO">Por Peso Kg</option>
                                <option value="CANTIDAD">Por Unidades</option>
                                <option value="VOLUMEN">Por Volumen CBM</option>
                            </select>
                        </div>

                        <div class="p-2.5 rounded-xl border border-orange-200 bg-orange-50/30 premium-param-ice">
                            <label for="impuestoICE" class="block text-xs font-bold text-[#FF6B00] mb-1.5 flex items-center justify-between gap-1">
                                <span>14. Ajuste Global ICE ($)</span>
                                <span class="text-[10px] font-extrabold bg-[#FF6B00]/15 text-[#FF6B00] px-1.5 py-0.5 rounded shrink-0">ICE Global</span>
                            </label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="impuestoICE" value="0.00" oninput="recalcularTodo()" class="w-full min-h-[46px] bg-white border border-orange-300 rounded-xl px-3.5 py-2.5 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#FF6B00] gold-glow">
                        </div>

                        <div class="flex flex-col justify-end p-2.5">
                            <span class="block text-[11px] font-semibold text-gray-500 mb-1.5">Restaurar Valores:</span>
                            <button type="button" onclick="restablecerValoresBaseLiteral1()" class="w-full min-h-[46px] bg-[#111111] hover:bg-gray-800 active:scale-[0.99] text-[#D4AF37] border border-[#D4AF37] rounded-xl px-3 py-2.5 text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-sm transition cursor-pointer">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Valores Base</span>
                            </button>
                        </div>
                    </div>

                    <!-- DESGLOSE EN VIVO DE LOS 9 GASTOS LOCALES E ISD -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-gray-200 shadow-xs space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider flex items-center gap-1.5" style="color:#00E5FF; text-shadow:0 0 5px rgba(0,229,255,.65), 0 0 11px rgba(0,229,255,.35);">
                                <span class="w-2 h-2 rounded-full bg-[#00E5FF]" style="box-shadow:0 0 7px rgba(0,229,255,.9);"></span>
                                <span>Desglose en Vivo de Gastos Operativos, Ruta, Bancos e ISD (9 Rubros)</span>
                            </span>
                            <span id="labelSumaNueveRubros" class="text-xs font-black text-[#111111] bg-[#D4AF37]/25 px-2.5 py-0.5 rounded-md">$0.00 (100%)</span>
                        </div>
                        <div id="barraProgresoGastosLocales" class="w-full h-3 bg-gray-100 rounded-full overflow-hidden flex"></div>
                        <div id="pillsDesgloseGastosLocales" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 pt-1"></div>
                    </div>

                    <!-- MÓDULO ESPECIAL ICE (SRI / SENAE ECUADOR) -->
                    <div class="bg-white border-2 border-[#D4AF37]/60 rounded-xl p-4 shadow-sm">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-gray-200">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="bg-[#111111] text-[#D4AF37] text-[10px] font-black uppercase px-2.5 py-1 rounded">SRI / SENAE Ecuador</span>
                                    <h4 class="text-xs sm:text-sm font-extrabold text-[#111111] uppercase">Impuesto a los Consumos Especiales (ICE) — 3 Modalidades</h4>
                                </div>
                                <p class="text-[11px] sm:text-xs text-gray-500 mt-1">
                                    Aplica tarifas <strong>1. Específicas ($ USD)</strong>, <strong>2. Ad-Valorem (%)</strong> o <strong>3. Mixtas ($ USD + %)</strong>. Configura cada ítem o aplica un preset oficial a todo el embarque:
                                </p>
                            </div>
                            <button type="button" onclick="abrirModalCatalogoICE(null)" class="min-h-[42px] shrink-0 bg-[#111111] hover:bg-gray-800 text-[#D4AF37] border border-[#D4AF37] text-xs font-bold px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Ver Catálogo SRI</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5 pt-3.5 items-end">
                            <div class="md:col-span-5">
                                <label for="presetICEGlobalSelect" class="block text-xs font-bold text-gray-700 mb-1">Catálogo Oficial SRI de Bienes Gravados con ICE:</label>
                                <select id="presetICEGlobalSelect" onchange="seleccionarPresetICEGlobal()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                    <option value="NINGUNO">-- Sin ICE (0% y $0.00) - Mercadería General --</option>
                                    <optgroup label="1. Tarifas Específicas (en Dólares USD)">
                                        <option value="ESP_CIGARRILLOS">Cigarrillos — USD 0.16 por unidad</option>
                                        <option value="ESP_ALCOHOL">Alcohol y bebidas alcohólicas — USD 10.41 por L de alcohol puro</option>
                                        <option value="ESP_CERVEZA_ART">Cerveza artesanal — USD 1.56 por L de alcohol puro</option>
                                        <option value="ESP_CERVEZA_IND">Cerveza industrial — USD 13.62 por L de alcohol puro</option>
                                        <option value="ESP_AZUCARADAS">Bebidas azucaradas (&gt;25g azúcar/L) — USD 0.18 por 100g azúcar</option>
                                        <option value="ESP_FUNDAS">Fundas plásticas — USD 0.08 por funda</option>
                                    </optgroup>
                                    <optgroup label="2. Tarifas Ad-Valorem (en Porcentaje %)">
                                        <option value="ADV_GASEOSAS">Bebidas gaseosas (&le;25g azúcar/L) — 10% Ad-Valorem</option>
                                        <option value="ADV_PERFUMES">Perfumes y aguas de tocador — 20% Ad-Valorem</option>
                                        <option value="ADV_VIDEOJUEGOS">Videojuegos y bingos — 35% Ad-Valorem</option>
                                        <option value="ADV_VEHICULOS">Vehículos motorizados — 15% Ad-Valorem</option>
                                        <option value="ADV_SERVICIOS_TV">Servicios TV pagada / telefonía fija — 15% Ad-Valorem</option>
                                        <option value="ADV_ARMAS">Armas de fuego y municiones — 300% Ad-Valorem</option>
                                    </optgroup>
                                    <optgroup label="3. Tarifas Mixtas (USD + % simultáneos)">
                                        <option value="MIX_ALCOHOL">Alcohol y licores (Mixto) — USD 10.41/L puro + 75% Ad-Valorem</option>
                                        <option value="MIX_CERVEZA_ART">Cerveza artesanal (Mixto) — USD 1.56/L puro + 75% Ad-Valorem</option>
                                        <option value="MIX_CERVEZA_IND">Cerveza industrial (Mixto) — USD 13.62/L puro + 75% Ad-Valorem</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label for="globalICEEspecificoUSD" class="block text-xs font-bold text-gray-700 mb-1">Tarifa Específica ($)</label>
                                <input type="number" inputmode="decimal" step="0.0001" min="0" id="globalICEEspecificoUSD" value="0.00" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                            </div>
                            <div class="md:col-span-2">
                                <label for="globalICEFactor" class="block text-xs font-bold text-gray-700 mb-1" title="Factor por unidad física">Factor Medida/Unid.</label>
                                <input type="number" inputmode="decimal" step="0.0001" min="0" id="globalICEFactor" value="1.00" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-semibold text-gray-700 focus:outline-none focus:border-[#D4AF37]">
                            </div>
                            <div class="md:col-span-1">
                                <label for="globalICEAdValoremPct" class="block text-xs font-bold text-[#FF6B00] mb-1">% Ad-Val</label>
                                <input type="number" inputmode="decimal" step="0.5" min="0" max="500" id="globalICEAdValoremPct" value="0" class="w-full min-h-[46px] bg-orange-50/50 border border-orange-300 rounded-xl px-2.5 py-2 text-base sm:text-sm font-extrabold text-[#FF6B00] focus:outline-none focus:border-[#FF6B00]">
                            </div>
                            <div class="md:col-span-2">
                                <button type="button" onclick="aplicarPresetICEATodos()" class="w-full min-h-[46px] bg-[#FF6B00] hover:bg-[#e56000] active:scale-[0.99] text-white font-bold text-xs px-3 py-2.5 rounded-xl shadow-sm transition cursor-pointer">
                                    Aplicar al Embarque
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOQUE 2: MÁRGENES DE GANANCIA POR CANAL COMERCIAL -->
            <div id="mpiMargenes" class="bg-gray-50 rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div onclick="toggleSeccion('contenidoBloque2', 'iconoAcordeon2')" class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-2 cursor-pointer select-none hover:bg-gray-100/70 transition">
                    <h3 class="text-[#111111] font-bold text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2.5 h-4 bg-[#D4AF37] inline-block rounded-sm shrink-0"></span>
                        <span>2. Márgenes de Comercialización y Precios de Venta (Rentabilidad)</span>
                    </h3>
                    <div class="flex items-center gap-2 ml-auto">
                        <span class="text-xs font-bold text-gray-600 hidden sm:inline">Canales Mayorista, Distribuidor y PVP</span>
                        <button type="button" class="p-1.5 rounded-lg bg-white border border-gray-300 text-gray-700" aria-label="Mostrar u ocultar márgenes">
                            <svg id="iconoAcordeon2" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div id="contenidoBloque2" class="px-4 pb-5 sm:px-5 sm:pb-5 pt-2 border-t border-gray-200/80">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 bg-white p-3 rounded-xl border border-gray-200">
                        <span class="text-xs font-bold text-gray-700">Fórmula Comercial para Determinar Precios:</span>
                        <select id="formulaMargen" onchange="actualizarPreciosComerciales()" class="min-h-[44px] bg-gray-50 border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-semibold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                            <option value="MARKUP">Markup sobre Costo (Costo * (1 + %))</option>
                            <option value="MARGEN_FINANCIERO">Margen Financiero sobre Venta (Costo / (1 - %))</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                        <div id="cardCanalMayoristaNeon" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black uppercase text-[#111111]">Canal Mayorista</span>
                                <span class="text-[11px] font-bold bg-gray-100 text-gray-700 px-2 py-0.5 rounded">Volumen</span>
                            </div>
                            <label for="margenMayorista" class="block text-xs text-gray-500 mb-1.5">Margen deseado (%):</label>
                            <input type="number" inputmode="decimal" step="0.5" min="1" max="95" id="margenMayorista" value="15" oninput="actualizarPreciosComerciales()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                            <div class="mt-3 pt-2.5 border-t border-gray-100 flex justify-between text-xs">
                                <span class="text-gray-500">Utilidad Total:</span>
                                <span id="utilidadTotalMayorista" class="font-bold text-emerald-600">$0.00</span>
                            </div>
                        </div>

                        <div id="cardDistribuidorNeon" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black uppercase text-[#111111]">Distribuidor / Eventual</span>
                                <span class="text-[11px] font-bold bg-[#FF6B00]/10 text-[#FF6B00] px-2 py-0.5 rounded">Canal B2B</span>
                            </div>
                            <label for="margenEventual" class="block text-xs text-gray-500 mb-1.5">Margen deseado (%):</label>
                            <input type="number" inputmode="decimal" step="0.5" min="1" max="95" id="margenEventual" value="25" oninput="actualizarPreciosComerciales()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                            <div class="mt-3 pt-2.5 border-t border-gray-100 flex justify-between text-xs">
                                <span class="text-gray-500">Utilidad Total:</span>
                                <span id="utilidadTotalEventual" class="font-bold text-emerald-600">$0.00</span>
                            </div>
                        </div>

                        <div id="cardPVPNeon" class="bg-white p-4 rounded-xl border border-[#D4AF37]/60 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black uppercase text-[#111111]">Venta al Público (PVP)</span>
                                <span class="text-[11px] font-bold bg-[#111111] text-[#D4AF37] px-2 py-0.5 rounded">Consumidor</span>
                            </div>
                            <label for="margenPVP" class="block text-xs text-gray-500 mb-1.5">Margen deseado (%):</label>
                            <input type="number" inputmode="decimal" step="0.5" min="1" max="95" id="margenPVP" value="40" oninput="actualizarPreciosComerciales()" class="w-full min-h-[46px] bg-gray-50 border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37] gold-glow">
                            <div class="mt-3 pt-2.5 border-t border-gray-100 flex justify-between text-xs">
                                <span class="text-gray-500">Utilidad Total:</span>
                                <span id="utilidadTotalPVP" class="font-bold text-emerald-600">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOQUES 3 Y 4: FORMATOS Y CARGA MASIVA -->
            <div id="mpiCarga" class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
                <!-- BLOQUE 3: DESCARGAS -->
                <div class="lg:col-span-5 bg-gray-50 p-4 sm:p-5 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <div>
                        <p class="text-[#111111] font-bold text-xs sm:text-sm uppercase tracking-wider mb-2 flex items-center gap-2">
                            <span class="w-2.5 h-4 bg-[#111111] inline-block rounded-sm shrink-0"></span>
                            <span>3. Descarga de Archivos de Estructura:</span>
                        </p>
                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            Obtén la plantilla Excel oficial con columnas para FOB, Flete, CBM, Seguro, Aranceles, <strong>% de Merma</strong>, <strong>Tarifas ISD</strong> e <strong>ICE</strong>.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3">
                        <button onclick="descargarPlantilla()" class="download-resource-btn min-h-[48px] w-full flex items-center justify-center space-x-2 border-2 border-[#F5C451] hover:border-[#FF8A24] active:scale-[0.99] bg-[#172033] text-[#F5C451] hover:text-[#FF8A24] font-semibold py-3 px-4 rounded-xl transition duration-300 shadow-sm cursor-pointer">
                            <svg class="w-5 h-5 text-[#FF8A24] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-xs sm:text-sm">Descargar Plantilla Excel (.xlsx)</span>
                        </button>

                        <button onclick="descargarManual()" class="download-resource-btn min-h-[48px] w-full flex items-center justify-center space-x-2 border-2 border-[#F5C451] hover:border-[#FF8A24] active:scale-[0.99] bg-[#172033] text-[#F5C451] hover:text-[#FF8A24] font-semibold py-3 px-4 rounded-xl transition duration-300 shadow-sm cursor-pointer">
                            <svg class="w-5 h-5 text-[#FF8A24] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span class="text-xs sm:text-sm">Manual de Normas SENAE / SRI (.pdf)</span>
                        </button>
                    </div>
                </div>

                <!-- BLOQUE 4: CARGA MASIVA DE EXCEL -->
                <div class="lg:col-span-7 bg-gray-50 p-4 sm:p-5 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                        <p class="text-[#111111] font-bold text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2.5 h-4 bg-[#FF6B00] inline-block rounded-sm shrink-0"></span>
                            <span>4. Sube la plantilla completada para liquidar:</span>
                        </p>
                        <span id="badgeArchivoCargado" class="text-[11px] font-bold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">
                            Compatible con .xlsx y .xls
                        </span>
                    </div>

                    <div id="dropZone" class="border-2 border-dashed border-gray-300 hover:border-[#D4AF37] active:border-[#FF6B00] bg-white rounded-xl p-5 sm:p-7 text-center transition duration-300 cursor-pointer relative group flex-1 flex flex-col items-center justify-center min-h-[150px]">
                        <input type="file" id="inputExcel" accept=".xlsx, .xls" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="verificarArchivo(this)" />
                        <div class="space-y-2.5 pointer-events-none">
                            <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center group-hover:bg-[#FF6B00]/10 transition">
                                <svg class="w-6 h-6 text-gray-500 group-hover:text-[#FF6B00] transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <div>
                                <p id="textoArchivoCargado" class="text-xs sm:text-sm font-bold text-[#111111] group-hover:text-[#D4AF37] transition">
                                    Toca aquí para seleccionar desde tu dispositivo o arrastra el archivo
                                </p>
                                <p class="text-[11px] sm:text-xs text-gray-400 mt-1">
                                    Procesamiento 100% privado en tu navegador (sin enviar tus datos a servidores externos)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD EJECUTIVO: KPIS, GRÁFICOS Y TABLA / TARJETAS -->
            <div id="dashboardImportacion" class="pt-4 border-t-2 border-gray-200 space-y-6 sm:space-y-8 ux-section-target">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-[#111111] text-base sm:text-lg font-extrabold uppercase tracking-wide flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-[#D4AF37] shrink-0"></span>
                            <span>Dashboard Ejecutivo de Liquidación y Márgenes</span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Visualización de Costo Landed Real por Merma, IVA de importación, Asiento Contable y Márgenes Comerciales.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:flex items-center gap-2.5">
                        <!-- LITERAL 3: Exporta Liquidación + Asiento Contable en dos pestañas -->
                        <button onclick="exportarLiquidacionExcel()" class="min-h-[44px] flex items-center justify-center gap-2 bg-[#111111] hover:bg-gray-800 active:scale-[0.99] text-[#D4AF37] border border-[#D4AF37] text-xs font-bold px-4 py-2.5 rounded-xl shadow transition cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Liquidación + Asiento (.xlsx)</span>
                        </button>
                        <button onclick="exportarReportePDF()" class="min-h-[44px] flex items-center justify-center gap-2 bg-[#FF6B00] hover:bg-[#e56000] active:scale-[0.99] text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow transition cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span>Lista de Precios (.pdf)</span>
                        </button>
                    </div>
                </div>

                <!-- 7 KPIS EJECUTIVOS -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3 sm:gap-3.5">
                    <div class="bg-white p-3.5 rounded-xl border border-blue-200 shadow-sm">
                        <div class="flex items-center justify-between text-[10px] text-gray-500 font-bold uppercase">
                            <span>1. Valor CIF Total</span>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        </div>
                        <p id="kpiTotalCIF" class="text-base sm:text-lg font-black text-[#111111] mt-1.5 truncate">$0.00</p>
                        <p id="kpiSubCIF" class="text-[10px] text-gray-500 mt-1 truncate">FOB: $0.00 | Seg: $0.00</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-sm">
                        <div class="flex items-center justify-between text-[10px] text-gray-600 font-bold uppercase">
                            <span>2. Arancel + FODINFA</span>
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        </div>
                        <p id="kpiArancelFodinfa" class="text-base sm:text-lg font-black text-[#111111] mt-1.5 truncate">$0.00</p>
                        <p id="kpiSubArancelFodinfa" class="text-[10px] text-gray-500 mt-1 truncate">AdV + FODINFA 0.5%</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-orange-200 shadow-sm">
                        <div class="flex items-center justify-between text-[10px] text-[#FF6B00] font-bold uppercase">
                            <span>3. Impuesto ICE (SRI)</span>
                            <span class="w-2 h-2 rounded-full bg-[#FF6B00]"></span>
                        </div>
                        <p id="kpiTotalICE" class="text-base sm:text-lg font-black text-[#FF6B00] mt-1.5 truncate">$0.00</p>
                        <p id="kpiSubICE" class="text-[10px] text-gray-500 mt-1 truncate">Esp: $0.00 | AdV: $0.00</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-xl border border-gray-200 shadow-sm">
                        <div class="flex items-center justify-between text-[10px] text-gray-500 font-bold uppercase">
                            <span>4. ISD + Gastos Loc.</span>
                            <span class="w-2 h-2 rounded-full bg-[#D4AF37]"></span>
                        </div>
                        <p id="kpiTotalLocales" class="text-base sm:text-lg font-black text-[#111111] mt-1.5 truncate">$0.00</p>
                        <p id="kpiSubLocales" class="text-[10px] text-gray-500 mt-1 truncate">ISD: $0.00 | Loc: $0.00</p>
                    </div>

                    <div class="bg-slate-900 p-3.5 rounded-xl border-b-4 border-blue-400 shadow-md text-white">
                        <div class="flex items-center justify-between text-[10px] text-blue-300 font-bold uppercase">
                            <span>5. IVA Importación</span>
                            <span id="kpiLabelTasaIVA" class="text-[9px] bg-blue-500/20 text-blue-300 px-1.5 py-0.5 rounded">15%</span>
                        </div>
                        <p id="kpiIVAImportacion" class="text-base sm:text-lg font-black text-white mt-1.5 truncate">$0.00</p>
                        <p class="text-[10px] text-blue-200/80 mt-1 truncate">IVA SENAE / Aduana</p>
                    </div>

                    <div class="bg-[#111111] p-3.5 rounded-xl border-b-4 border-[#D4AF37] shadow-md text-white">
                        <div class="flex items-center justify-between text-[10px] text-[#D4AF37] font-bold uppercase">
                            <span>6. Costo Bodega</span>
                            <span class="text-[9px] bg-[#D4AF37]/20 text-[#D4AF37] px-1.5 py-0.5 rounded">Landed</span>
                        </div>
                        <p id="kpiCostoBodega" class="text-base sm:text-lg font-black text-white mt-1.5 truncate">$0.00</p>
                        <p id="kpiFactorImportacion" class="text-[10px] text-gray-300 mt-1 truncate">Factor: 1.00x</p>
                    </div>

                    <div class="bg-emerald-950 p-3.5 rounded-xl border-b-4 border-emerald-400 shadow-md text-white col-span-2 sm:col-span-1">
                        <div class="flex items-center justify-between text-[10px] text-emerald-300 font-bold uppercase">
                            <span>7. Utilidad PVP</span>
                            <span class="text-[9px] bg-emerald-500/20 text-emerald-300 px-1.5 py-0.5 rounded">Ganancia</span>
                        </div>
                        <p id="kpiUtilidadBrutaPVP" class="text-base sm:text-lg font-black text-emerald-300 mt-1.5 truncate">$0.00</p>
                        <p id="kpiSubUtilidadPVP" class="text-[10px] text-emerald-200/80 mt-1 truncate">Canal PVP Sin IVA</p>
                    </div>
                </div>

                <!-- GRÁFICOS INTERACTIVOS (CHART.JS) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6">
                    <div class="lg:col-span-5 bg-gray-50 p-4 sm:p-5 rounded-xl border border-gray-200 flex flex-col justify-between">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                                <span class="w-2 h-3.5 bg-[#D4AF37] inline-block rounded-sm shrink-0"></span>
                                <span>Composición del Costo Landed Total</span>
                            </h4>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">FOB, Flete+Seguro, Aranceles, ICE e ISD+Gastos Locales</p>
                        </div>
                        <div class="relative h-56 sm:h-64 my-2 flex items-center justify-center">
                            <canvas id="chartComposicionCostos"></canvas>
                        </div>
                        <div id="leyendaComposicion" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-2 border-t border-gray-200"></div>
                    </div>

                    <div class="lg:col-span-7 bg-gray-50 p-4 sm:p-5 rounded-xl border border-gray-200 flex flex-col justify-between">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#111111] flex items-center gap-2">
                                <span class="w-2 h-3.5 bg-[#FF6B00] inline-block rounded-sm shrink-0"></span>
                                <span>Costo Unitario Real (con Merma) vs. Precios</span>
                            </h4>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Comparativa para Mayorista, Distribuidor y PVP (Sin IVA)</p>
                        </div>
                        <div class="relative h-64 sm:h-72 mt-3">
                            <canvas id="chartComparativaPrecios"></canvas>
                        </div>
                    </div>
                </div>

                <!-- FILTRO Y SELECTOR DE VISTA -->
                <div id="mpiFiltroProductos" class="bg-gray-50 p-3.5 sm:p-4 rounded-xl border border-gray-200 space-y-3.5">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                        <div class="relative flex-1">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" id="inputBuscarProducto" oninput="filtrarTabla()" placeholder="Buscar por código SKU, descripción, modalidad ICE o tarifa ISD..." class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>

                        <div class="flex flex-wrap items-center justify-between sm:justify-end gap-2 bg-white p-1.5 rounded-xl border border-gray-200">
                            <span class="text-[11px] font-bold text-gray-500 px-2">Modo:</span>
                            <div class="flex items-center gap-1">
                                <button type="button" id="btnModoTarjetas" onclick="seleccionarModoDispositivo('TARJETAS')" class="min-h-[40px] px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                    <span>Tarjetas (Móvil)</span>
                                </button>
                                <button type="button" id="btnModoTabla" onclick="seleccionarModoDispositivo('TABLA')" class="min-h-[40px] px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Tabla (PC / Tablet)</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="barraColumnasTabla" class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-200">
                        <span class="text-xs font-bold text-gray-600">Columnas de Tabla:</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="cambiarVistaTabla('COMPLETA')" id="btnVistaCompleta" class="min-h-[36px] text-xs font-bold px-3 py-1.5 rounded-lg bg-[#111111] text-[#D4AF37] transition cursor-pointer">
                                Flujo Completo (SENAE + Merma + Precios)
                            </button>
                            <button onclick="cambiarVistaTabla('COSTOS')" id="btnVistaCostos" class="min-h-[36px] text-xs font-semibold px-3 py-1.5 rounded-lg bg-white text-gray-700 border border-gray-300 hover:border-[#D4AF37] transition cursor-pointer">
                                Solo SENAE, ICE &amp; ISD
                            </button>
                            <button onclick="cambiarVistaTabla('PRECIOS')" id="btnVistaPrecios" class="min-h-[36px] text-xs font-semibold px-3 py-1.5 rounded-lg bg-white text-gray-700 border border-gray-300 hover:border-[#D4AF37] transition cursor-pointer">
                                Solo Precios y Márgenes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- VISTA 1: TARJETAS (MÓVIL) -->
                <div id="contenedorVistaTarjetas" class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden"></div>

                <!-- VISTA 2: TABLA MAESTRA (PC / TABLET) -->
                <div id="contenedorVistaTabla" class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#111111] text-white text-[11px] uppercase tracking-wider">
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] sticky-col bg-[#111111]">Código</th>
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] min-w-[170px]">Producto</th>
                                    <th class="py-3.5 px-2 font-bold border-b-2 border-[#D4AF37] text-center">Cant.</th>
                                    <!-- LITERAL 1: Columna de Merma y Unidades Vendibles -->
                                    <th class="py-3.5 px-2 font-bold border-b-2 border-[#D4AF37] text-center bg-red-950/60 text-red-200" title="% Estimado de rotura o merma">% Merma</th>
                                    <th class="py-3.5 px-2.5 font-bold border-b-2 border-[#D4AF37] text-right col-costos">FOB Unit.</th>
                                    <th class="py-3.5 px-2.5 font-bold border-b-2 border-[#D4AF37] text-right col-costos">FOB Total</th>
                                    <th class="py-3.5 px-2.5 font-bold border-b-2 border-[#D4AF37] text-right col-costos bg-gray-900">CIF Total</th>
                                    <th class="py-3.5 px-2 font-bold border-b-2 border-[#D4AF37] text-center col-costos">% Arancel</th>
                                    <th class="py-3.5 px-2 font-bold border-b-2 border-[#D4AF37] text-center col-costos bg-red-950/60 text-red-200">% ISD</th>
                                    <th class="py-3.5 px-2 font-bold border-b-2 border-[#D4AF37] text-center col-costos bg-orange-950/70 text-[#D4AF37]">Modalidad ICE</th>
                                    <th class="py-3.5 px-2.5 font-bold border-b-2 border-[#D4AF37] text-right col-costos">AdVal + FOD + ICE</th>
                                    <th class="py-3.5 px-2.5 font-bold border-b-2 border-[#D4AF37] text-right col-costos">ISD + Locales</th>
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] text-right bg-gray-900 text-[#D4AF37]">Costo Bodega Total</th>
                                    <!-- Costo Unitario Real Ajustado por Merma -->
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] text-right bg-[#D4AF37]/20 text-[#D4AF37]" title="Costo unitario distribuido entre unidades útiles vendibles">Unit. Real Vendible</th>
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] text-right col-precios">P. Mayorista</th>
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] text-right col-precios">P. Eventual</th>
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] text-right col-precios bg-gray-900">PVP (Sin IVA)</th>
                                    <th class="py-3.5 px-3 font-bold border-b-2 border-[#D4AF37] text-right col-precios bg-emerald-950 text-emerald-300">PVP + IVA</th>
                                    <th class="py-3.5 px-2 font-bold border-b-2 border-[#D4AF37] text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaLiquidacionBody" class="divide-y divide-gray-200 text-xs"></tbody>
                            <tfoot id="tablaLiquidacionFoot" class="bg-gray-100 font-extrabold text-xs text-[#111111] border-t-2 border-[#111111]"></tfoot>
                        </table>
                    </div>
                </div>

                <!-- NOTA METODOLÓGICA SENAE / SRI -->
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-600 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <strong class="text-[#111111] uppercase block mb-1">1. Base CIF y Seguro:</strong>
                        <p><strong>CIF</strong> = FOB + Flete + Seguro declarado/estimado. El seguro automático es solo una estimación editable. Arancel y FODINFA (0.5%) se estiman sobre el valor en aduana; confirme la subpartida.</p>
                    </div>
                    <div>
                        <strong class="text-[#111111] uppercase block mb-1">2. Ajuste por Merma / Rotura:</strong>
                        <p>El costo de unidades rotas se reabsorbe automáticamente entre las <strong>unidades útiles</strong>, calculando el Costo Landed Real.</p>
                    </div>
                    <div>
                        <strong class="text-[#111111] uppercase block mb-1">3. Regla W/M y Contenedores:</strong>
                        <p>Compara el peso en toneladas vs. el volumen (CBM) para determinar la tarifa de flete marítimo LCL o paso a FCL.</p>
                    </div>
                    <div>
                        <strong class="text-[#111111] uppercase block mb-1">4. Asiento Contable Automático:</strong>
                        <p>La descarga genera una pestaña contable que separa costo de mercadería, IVA de importación (según recuperabilidad), gastos e ISD.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- BARRA INFERIOR FLOTANTE PARA MÓVILES -->
    <div id="barraMovil" class="fixed bottom-0 left-0 right-0 z-40 md:hidden bg-[#111111] border-t-2 border-[#D4AF37] px-3.5 py-2.5 shadow-2xl">
        <div class="flex items-center justify-between gap-2 max-w-lg mx-auto">
            <div class="grid grid-cols-3 gap-2 flex-1">
                <div>
                    <span class="block text-[9px] uppercase text-gray-400 font-bold">Costo Bodega</span>
                    <span id="mobileBarCostoBodega" class="text-xs font-black text-[#D4AF37] truncate block">$0.00</span>
                </div>
                <div>
                    <span class="block text-[9px] uppercase text-gray-400 font-bold">SENAE + ICE</span>
                    <span id="mobileBarAranceles" class="text-xs font-black text-[#FF6B00] truncate block">$0.00</span>
                </div>
                <div>
                    <span class="block text-[9px] uppercase text-gray-400 font-bold">Utilidad PVP</span>
                    <span id="mobileBarUtilidadPVP" class="text-xs font-black text-emerald-400 truncate block">$0.00</span>
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button onclick="abrirModalCatalogoICE(null)" class="min-h-[40px] bg-[#FF6B00] text-white font-extrabold text-xs px-2.5 py-2 rounded-lg flex items-center gap-1 shadow cursor-pointer">
                    <span>ICE</span>
                </button>
                <button onclick="abrirModalNuevoItem()" class="min-h-[40px] bg-[#D4AF37] text-[#111111] font-extrabold text-xs px-2.5 py-2 rounded-lg flex items-center gap-1 shadow cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Ítem</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 1: INFORMACIÓN Y TARIFAS ISD -->
    <div id="modalInfoISD" class="fixed inset-0 bg-black/75 backdrop-blur-xs z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white border-t-4 sm:border border-[#D4AF37] sm:border-gray-200 rounded-t-2xl sm:rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-[#111111] border-b-4 border-[#D4AF37] px-5 py-4 flex items-center justify-between sticky top-0 z-10">
                <div>
                    <span class="text-[10px] font-black uppercase bg-red-600 text-white px-2 py-0.5 rounded">SRI / BCE Ecuador</span>
                    <h3 class="text-white font-bold text-sm sm:text-base uppercase tracking-wider mt-1">Tarifas del Impuesto a la Salida de Divisas (ISD)</h3>
                </div>
                <button onclick="cerrarModalInfoISD()" class="text-gray-400 hover:text-white p-1.5 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4 text-xs sm:text-sm">
                <div class="space-y-3">
                    <div class="bg-red-50/70 border border-red-200 rounded-xl p-4 flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-block text-[10px] font-black uppercase bg-red-600 text-white px-2 py-0.5 rounded mb-1">1. Tarifa General (5%)</span>
                            <p class="text-gray-700 leading-relaxed">Aplica a pagos a proveedores internacionales de mercadería general, giros al exterior y consumos no exentos.</p>
                        </div>
                        <button type="button" onclick="seleccionarTarifaISDGlobal('5'); cerrarModalInfoISD();" class="shrink-0 min-h-[40px] bg-[#111111] text-[#D4AF37] font-bold text-xs px-3 py-2 rounded-lg cursor-pointer">Aplicar 5%</button>
                    </div>

                    <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-block text-[10px] font-black uppercase bg-[#D4AF37] text-[#111111] px-2 py-0.5 rounded mb-1">2. Tarifa Diferenciada (2,5%)</span>
                            <p class="text-gray-700 leading-relaxed">Aplica a importación de bienes de capital y materias primas vinculadas a sectores productivos estratégicos.</p>
                        </div>
                        <button type="button" onclick="seleccionarTarifaISDGlobal('2.5'); cerrarModalInfoISD();" class="shrink-0 min-h-[40px] bg-[#111111] text-[#D4AF37] font-bold text-xs px-3 py-2 rounded-lg cursor-pointer">Aplicar 2.5%</button>
                    </div>

                    <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-4 flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-block text-[10px] font-black uppercase bg-emerald-600 text-white px-2 py-0.5 rounded mb-1">3. Tarifa Exenta (0%)</span>
                            <p class="text-gray-700 leading-relaxed">Aplica a medicamentos de uso humano, insumos del sector farmacéutico y partidas específicas de salud.</p>
                        </div>
                        <button type="button" onclick="seleccionarTarifaISDGlobal('0'); cerrarModalInfoISD();" class="shrink-0 min-h-[40px] bg-[#111111] text-[#D4AF37] font-bold text-xs px-3 py-2 rounded-lg cursor-pointer">Aplicar 0%</button>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-200 flex justify-end">
                    <button type="button" onclick="cerrarModalInfoISD()" class="min-h-[42px] px-5 py-2 rounded-xl bg-[#111111] text-white font-bold text-xs cursor-pointer">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: CATÁLOGO Y CONFIGURADOR ICE SRI -->
    <div id="modalCatalogoICE" class="fixed inset-0 bg-black/75 backdrop-blur-xs z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white border-t-4 sm:border border-[#D4AF37] sm:border-gray-200 rounded-t-2xl sm:rounded-2xl shadow-2xl max-w-3xl w-full max-h-[92vh] overflow-y-auto">
            <div class="bg-[#111111] border-b-4 border-[#D4AF37] px-5 py-4 flex items-center justify-between sticky top-0 z-10">
                <div>
                    <span class="text-[10px] font-black uppercase bg-[#FF6B00] text-white px-2 py-0.5 rounded">SRI / SENAE Ecuador</span>
                    <h3 id="tituloModalICE" class="text-white font-bold text-sm sm:text-base uppercase tracking-wider mt-1">Configurador de ICE Ecuador</h3>
                </div>
                <button onclick="cerrarModalCatalogoICE()" class="text-gray-400 hover:text-white p-1.5 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-5 text-xs sm:text-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200">
                        <span class="inline-block text-[10px] font-black uppercase bg-[#111111] text-[#D4AF37] px-2 py-0.5 rounded mb-1.5">1. Específica ($)</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Monto fijo en USD por unidad física, litro de alcohol puro, 100g de azúcar o funda.</p>
                    </div>

                    <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200">
                        <span class="inline-block text-[10px] font-black uppercase bg-[#FF6B00] text-white px-2 py-0.5 rounded mb-1.5">2. Ad-Valorem (%)</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Porcentaje sobre la Base Ex-Aduana (CIF + Ad-Valorem + FODINFA).</p>
                    </div>

                    <div class="bg-gray-50 p-3.5 rounded-xl border border-[#D4AF37]">
                        <span class="inline-block text-[10px] font-black uppercase bg-[#D4AF37] text-[#111111] px-2 py-0.5 rounded mb-1.5">3. Mixta ($ + %)</span>
                        <p class="text-xs text-gray-600 leading-relaxed">Combina simultáneamente la tarifa fija en dólares y el porcentaje Ad-Valorem.</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-300 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="font-extrabold text-[#111111] uppercase text-xs sm:text-sm">Asignación de ICE</h4>
                        <span id="badgeProductoSeleccionadoICE" class="text-xs font-bold bg-[#111111] text-[#D4AF37] px-3 py-1 rounded-lg">Todo el Embarque</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Catálogo de referencia ICE (verificar vigencia):</label>
                            <select id="modalICESelectRubro" onchange="cargarRubroEnModalICE()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-semibold focus:outline-none focus:border-[#D4AF37]">
                                <option value="NINGUNO">Sin ICE (Exento)</option>
                                <option value="ESP_CIGARRILLOS">1. Específico: Cigarrillos ($0.16 / u)</option>
                                <option value="ESP_ALCOHOL">1. Específico: Alcohol ($10.41 / L puro)</option>
                                <option value="ESP_CERVEZA_ART">1. Específico: Cerveza artesanal ($1.56 / L puro)</option>
                                <option value="ESP_CERVEZA_IND">1. Específico: Cerveza industrial ($13.62 / L puro)</option>
                                <option value="ESP_AZUCARADAS">1. Específico: Bebidas azucaradas ($0.18 / 100g az)</option>
                                <option value="ESP_FUNDAS">1. Específico: Fundas plásticas ($0.08 / funda)</option>
                                <option value="ADV_GASEOSAS">2. Ad-Valorem: Bebidas gaseosas (10%)</option>
                                <option value="ADV_PERFUMES">2. Ad-Valorem: Perfumes y tocador (20%)</option>
                                <option value="ADV_VIDEOJUEGOS">2. Ad-Valorem: Videojuegos (35%)</option>
                                <option value="ADV_VEHICULOS">2. Ad-Valorem: Vehículos (15%)</option>
                                <option value="ADV_SERVICIOS_TV">2. Ad-Valorem: Servicios TV pagada (15%)</option>
                                <option value="ADV_ARMAS">2. Ad-Valorem: Armas (300%)</option>
                                <option value="MIX_ALCOHOL">3. Mixto: Alcohol ($10.41/L puro + 75%)</option>
                                <option value="MIX_CERVEZA_ART">3. Mixto: Cerveza artesanal ($1.56/L puro + 75%)</option>
                                <option value="MIX_CERVEZA_IND">3. Mixto: Cerveza industrial ($13.62/L puro + 75%)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Modalidad Activa:</label>
                            <select id="modalICEModalidad" onchange="actualizarVistaPreviaModalICE()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                <option value="NINGUNO">NINGUNO (Sin ICE)</option>
                                <option value="ESPECIFICO">1. ESPECÍFICO ($ USD)</option>
                                <option value="AD_VALOREM">2. AD-VALOREM (% Ad-Val)</option>
                                <option value="MIXTO">3. MIXTO ($ USD + %)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tarifa Específica ($):</label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="modalICEEspecificoUSD" value="0.00" oninput="actualizarVistaPreviaModalICE()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Factor por Unidad:</label>
                            <input type="number" inputmode="decimal" step="0.0001" min="0" id="modalICEFactor" value="1.00" oninput="actualizarVistaPreviaModalICE()" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-2 text-base sm:text-sm font-semibold text-gray-800 focus:outline-none focus:border-[#D4AF37]">
                            <p id="ayudaModalICEFactor" class="text-[10px] text-gray-500 mt-0.5">Factor unitario (1.00 por defecto)</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#FF6B00] mb-1">Tarifa Ad-Valorem (%):</label>
                            <input type="number" inputmode="decimal" step="0.5" min="0" max="500" id="modalICEAdValoremPct" value="0" oninput="actualizarVistaPreviaModalICE()" class="w-full min-h-[46px] bg-orange-50/50 border border-orange-300 rounded-xl px-3 py-2 text-base sm:text-sm font-extrabold text-[#FF6B00] focus:outline-none focus:border-[#FF6B00]">
                        </div>
                    </div>
                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-3">
                        <label for="modalICEBaseDeclarada" class="block text-xs font-bold text-gray-800 mb-1">Base imponible ICE ad-valorem declarada para este ítem (USD, total de la línea)</label>
                        <input type="number" inputmode="decimal" min="0" step="0.0001" id="modalICEBaseDeclarada" value="0" oninput="actualizarVistaPreviaModalICE()" class="w-full min-h-[42px] bg-white border border-orange-300 rounded-lg px-3 py-2 text-sm font-bold">
                        <p class="text-[10px] text-gray-600 mt-1">Deje 0 para usar la estimación CIF + arancel + FODINFA. Si la norma del producto establece otra base, ingrese aquí el total correcto de la línea. Solo se aplica al configurar un ítem individual.</p>
                    </div>

                    <div id="previewCalculoModalICE" class="bg-[#111111] text-white p-3.5 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                        <div class="text-xs">
                            <span class="text-[#D4AF37] font-bold uppercase block">Fórmula de Cálculo:</span>
                            <span class="text-gray-300">ICE = (Cant × Factor × USD Esp.) + (Base declarada o ex-aduana estimada × % AdV)</span>
                        </div>
                        <span id="montoPreviewModalICE" class="text-sm sm:text-base font-black text-[#D4AF37] shrink-0">Listo</span>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-200 flex flex-wrap justify-end gap-2.5">
                    <button type="button" onclick="cerrarModalCatalogoICE()" class="min-h-[44px] px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-100 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmarConfiguracionModalICE()" class="min-h-[44px] px-5 py-2 rounded-xl bg-[#111111] text-[#D4AF37] border border-[#D4AF37] font-bold text-xs hover:bg-gray-900 cursor-pointer">
                        Guardar Configuración
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: AGREGAR PRODUCTO MANUALMENTE -->
    <div id="modalNuevoItem" class="fixed inset-0 bg-black/70 backdrop-blur-xs z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white border-t-4 sm:border border-[#D4AF37] sm:border-gray-200 rounded-t-2xl sm:rounded-2xl shadow-2xl max-w-xl w-full max-h-[92vh] overflow-y-auto">
            <div class="bg-[#111111] border-b-4 border-[#D4AF37] px-5 py-4 flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-white font-bold text-sm uppercase tracking-wider">Nuevo Producto (Autocompletado &amp; Divisas)</h3>
                <button onclick="cerrarModalNuevoItem()" class="text-gray-400 hover:text-white p-1 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-5 space-y-4 text-xs sm:text-sm">
                <!-- Selector de Partida Frecuente -->
                <div class="bg-amber-50/50 p-3 rounded-xl border border-amber-200">
                    <label class="block text-xs font-bold text-amber-900 mb-1.5 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Autocompletar desde Base Arancelaria Frecuente:
                    </label>
                    <select id="presetPartidaFrecuente" onchange="autocompletarPartidaArancelaria(this.value)" class="w-full bg-white border border-amber-300 rounded-lg p-2 text-xs font-semibold focus:outline-none focus:border-[#D4AF37]">
                        <option value="">-- Seleccionar producto frecuente --</option>
                        <option value="MOT">Motor Eléctrico / Bien de Capital (Arancel 5%, ISD 2.5%, Merma 1%)</option>
                        <option value="FAR">Insumo Médico / Farmacéutico (Arancel 0%, ISD 0%, Merma 0%)</option>
                        <option value="PER">Perfumes y Tocador (Arancel 20%, ISD 5%, ICE AdV 20%, Merma 2%)</option>
                        <option value="LIC">Licores / Whisky (Arancel 25%, ISD 5%, ICE Mixto, Merma 3%)</option>
                        <option value="VID">Consola de Videojuegos (Arancel 20%, ISD 5%, ICE AdV 35%, Merma 1%)</option>
                    </select>
                </div>

                <!-- Conversor de Moneda Extranjera -->
                <div class="bg-blue-50/40 p-3 rounded-xl border border-blue-200 grid grid-cols-3 gap-3">
                    <div class="col-span-3 pb-1 border-b border-blue-200">
                        <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Conversor a USD para Valor FOB
                        </span>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-900 mb-1">Moneda Origen:</label>
                        <select id="monedaOrigen" onchange="convertirMonedaOrigen()" class="w-full bg-white border border-blue-300 rounded-lg p-1.5 text-xs focus:outline-none focus:border-[#D4AF37]">
                            <option value="USD">USD ($)</option>
                            <option value="RMB">Yuanes (RMB)</option>
                            <option value="EUR">Euros (€)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-900 mb-1">Monto Extranjero:</label>
                        <input type="number" id="montoMonedaExtranjera" value="0.00" step="0.0001" oninput="convertirMonedaOrigen()" class="w-full bg-white border border-blue-300 rounded-lg p-1.5 text-xs focus:outline-none focus:border-[#D4AF37]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-blue-900 mb-1">Tasa Cambio (vs USD):</label>
                        <input type="number" id="tasaCambioDivisa" value="7.25" step="0.0001" oninput="convertirMonedaOrigen()" class="w-full bg-white border border-blue-300 rounded-lg p-1.5 text-xs focus:outline-none focus:border-[#D4AF37]" title="Ej: 1 USD = 7.25 RMB">
                    </div>
                </div>

                <form onsubmit="guardarNuevoItem(event)" class="space-y-4 pt-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Código / SKU *</label>
                            <input type="text" id="nuevoCodigo" required placeholder="Ej: EC-PROD-001" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Cantidad de Unidades *</label>
                            <input type="number" inputmode="numeric" id="nuevaCantidad" required min="1" step="1" value="100" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Descripción del Producto *</label>
                        <input type="text" id="nuevoProducto" required placeholder="Ej: Dispositivo Electrónico Portátil" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-blue-700 mb-1">FOB Unitario ($) *</label>
                            <input type="number" inputmode="decimal" id="nuevoFOB" required min="0.01" step="0.0001" value="25.00" class="w-full min-h-[46px] border border-blue-400 bg-blue-50/20 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Flete Unitario ($)</label>
                            <input type="number" inputmode="decimal" id="nuevoFlete" min="0" step="0.0001" value="2.10" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Seguro Unitario ($)</label>
                            <input type="number" inputmode="decimal" id="nuevoSeguro" min="0" step="0.0001" value="0.27" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Arancel (%)</label>
                            <input type="number" inputmode="decimal" id="nuevoArancel" min="0" max="100" step="0.5" value="15" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]"><label class="pro-label mt-2" for="nuevoArancelPreferencial">Tarifa final preferencial (%)</label><input id="nuevoArancelPreferencial" type="number" min="0" max="100" step="0.0001" placeholder="Ej. 17; no es el descuento" class="pro-field"><label class="mpi-check"><input id="nuevoUsarPreferenciaOrigen" type="checkbox">Aplicar certificado de origen a este producto</label>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-red-700 mb-1">ISD (%)</label>
                            <select id="nuevoTarifaISD" class="w-full min-h-[46px] bg-red-50/40 border border-red-300 rounded-xl px-3 py-2 text-base sm:text-sm font-bold text-[#111111] focus:outline-none focus:border-[#D4AF37]">
                                <option value="5" selected>5%</option>
                                <option value="2.5">2.5%</option>
                                <option value="0">0%</option>
                            </select>
                        </div>
                        <!-- LITERAL 1: Campo de Merma en el Modal -->
                        <div>
                            <label class="block text-xs font-bold text-red-700 mb-1" title="Rotura o merma esperada">% Merma</label>
                            <input type="number" inputmode="decimal" id="nuevoMermaPct" min="0" max="90" step="0.5" value="0.0" class="w-full min-h-[46px] border border-red-300 bg-red-50/30 rounded-xl px-3.5 py-2 text-base sm:text-sm font-bold focus:outline-none focus:border-[#D4AF37]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Peso (Kg)</label>
                            <input type="number" inputmode="decimal" id="nuevoPeso" min="0.01" step="0.0001" value="1.50" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Vol. (CBM)</label>
                            <input type="number" inputmode="decimal" id="nuevoCBMUnitario" min="0.001" step="0.001" value="0.010" class="w-full min-h-[46px] border border-gray-300 rounded-xl px-3.5 py-2 text-base sm:text-sm focus:outline-none focus:border-[#D4AF37]" title="Metros Cúbicos por unidad">
                        </div>
                    </div>

                    <div class="bg-transparent border border-[#D4AF37]/70 rounded-xl p-3.5 space-y-3" style="background-color: transparent !important; color: #FFFFFF;">
                        <span class="block text-xs font-extrabold uppercase" style="color: #D4AF37 !important;">Configuración ICE (estimación — verificar base legal por producto):</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold mb-1" style="color: #FFFFFF !important;">Modalidad ICE:</label>
                                <select id="nuevoModalidadICE" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-1.5 text-base sm:text-xs font-bold focus:outline-none focus:border-[#D4AF37]">
                                    <option value="NINGUNO">NINGUNO (0% y $0.00)</option>
                                    <option value="ESPECIFICO">1. ESPECÍFICO ($ USD)</option>
                                    <option value="AD_VALOREM">2. AD-VALOREM (% base ex-aduana estimada)</option>
                                    <option value="MIXTO">3. MIXTO ($ USD + %)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold mb-1" style="color: #FFFFFF !important;">Factor Unidad:</label>
                                <input type="number" inputmode="decimal" id="nuevoFactorICE" min="0" step="0.0001" value="1.00" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-1.5 text-base sm:text-sm font-semibold focus:outline-none focus:border-[#D4AF37]">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold mb-1" style="color: #FFFFFF !important;">ICE Específico ($):</label>
                                <input type="number" inputmode="decimal" id="nuevoICEEspecifico" min="0" step="0.0001" value="0.00" class="w-full min-h-[46px] bg-white border border-gray-300 rounded-xl px-3 py-1.5 text-base sm:text-sm font-bold focus:outline-none focus:border-[#D4AF37]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold mb-1" style="color: #FF8A00 !important;">ICE Ad-Valorem (%):</label>
                                <input type="number" inputmode="decimal" id="nuevoICE" min="0" max="500" step="0.5" value="0" class="w-full min-h-[46px] bg-white border border-orange-300 rounded-xl px-3 py-1.5 text-base sm:text-sm font-bold text-[#FF6B00] focus:outline-none focus:border-[#D4AF37]">
                            </div>
                        </div>
                        <p class="text-[10px] rounded-lg p-2" style="background-color: transparent !important; color: #FFFFFF !important; border: 1px solid rgba(212,175,55,.35);">La estimación ad-valorem usa CIF + arancel + FODINFA como base ex-aduana aproximada. La base legal del ICE cambia según el producto; verifique la subpartida y la base específica con la normativa del SRI antes de usar el resultado para una declaración.</p>
                    </div>

                    <div class="pt-3 border-t border-gray-200 flex justify-end gap-2.5">
                        <button type="button" onclick="cerrarModalNuevoItem()" class="min-h-[44px] px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-semibold text-xs hover:bg-gray-100 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" class="min-h-[44px] px-5 py-2 rounded-xl bg-[#111111] text-[#D4AF37] border border-[#D4AF37] font-bold text-xs hover:bg-gray-900 cursor-pointer">
                            Agregar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: LÓGICA DE NEGOCIO INTEGRAL -->
    <script>
        const CATALOGO_OFICIAL_ICE_SRI = {
            NINGUNO: { nombre: 'Sin ICE (Mercadería General)', modalidad: 'NINGUNO', usd: 0.00, factor: 1.00, pct: 0, ayuda: 'Unidad estándar (1.00)' },
            ESP_CIGARRILLOS: { nombre: 'Cigarrillos ($0.16/unidad)', modalidad: 'ESPECIFICO', usd: 0.16, factor: 1.00, pct: 0, ayuda: '1.00 por cigarrillo' },
            ESP_ALCOHOL: { nombre: 'Alcohol ($10.41/L puro)', modalidad: 'ESPECIFICO', usd: 10.41, factor: 0.30, pct: 0, ayuda: 'Litros de alcohol puro por envase' },
            ESP_CERVEZA_ART: { nombre: 'Cerveza artesanal ($1.56/L puro)', modalidad: 'ESPECIFICO', usd: 1.56, factor: 0.025, pct: 0, ayuda: 'Litros de alcohol puro' },
            ESP_CERVEZA_IND: { nombre: 'Cerveza industrial ($13.62/L puro)', modalidad: 'ESPECIFICO', usd: 13.62, factor: 0.02, pct: 0, ayuda: 'Litros de alcohol puro' },
            ESP_AZUCARADAS: { nombre: 'Bebidas azucaradas ($0.18/100g az)', modalidad: 'ESPECIFICO', usd: 0.18, factor: 1.00, pct: 0, ayuda: 'Tramos de 100g de azúcar' },
            ESP_FUNDAS: { nombre: 'Fundas plásticas ($0.08/funda)', modalidad: 'ESPECIFICO', usd: 0.08, factor: 1.00, pct: 0, ayuda: '1.00 por funda plástica' },
            ADV_GASEOSAS: { nombre: 'Gaseosas <=25g azúcar/L (10%)', modalidad: 'AD_VALOREM', usd: 0.00, factor: 1.00, pct: 10, ayuda: '10% sobre Base Ex-Aduana' },
            ADV_PERFUMES: { nombre: 'Perfumes y aguas de tocador (20%)', modalidad: 'AD_VALOREM', usd: 0.00, factor: 1.00, pct: 20, ayuda: '20% sobre Base Ex-Aduana' },
            ADV_VIDEOJUEGOS: { nombre: 'Videojuegos y bingos (35%)', modalidad: 'AD_VALOREM', usd: 0.00, factor: 1.00, pct: 35, ayuda: '35% sobre Base Ex-Aduana' },
            ADV_VEHICULOS: { nombre: 'Vehículos motorizados (15%)', modalidad: 'AD_VALOREM', usd: 0.00, factor: 1.00, pct: 15, ayuda: 'Tramo promedio 15%' },
            ADV_SERVICIOS_TV: { nombre: 'Servicios TV pagada (15%)', modalidad: 'AD_VALOREM', usd: 0.00, factor: 1.00, pct: 15, ayuda: '15% Ad-Valorem' },
            ADV_ARMAS: { nombre: 'Armas y municiones (300%)', modalidad: 'AD_VALOREM', usd: 0.00, factor: 1.00, pct: 300, ayuda: '300% sobre Base Ex-Aduana' },
            MIX_ALCOHOL: { nombre: 'Alcohol Mixto ($10.41/L + 75%)', modalidad: 'MIXTO', usd: 10.41, factor: 0.30, pct: 75, ayuda: '$10.41/L alcohol puro + 75% Ad-Valorem' },
            MIX_CERVEZA_ART: { nombre: 'Cerveza artesanal Mixto ($1.56/L + 75%)', modalidad: 'MIXTO', usd: 1.56, factor: 0.025, pct: 75, ayuda: '$1.56/L puro + 75% Ad-Valorem' },
            MIX_CERVEZA_IND: { nombre: 'Cerveza industrial Mixto ($13.62/L + 75%)', modalidad: 'MIXTO', usd: 13.62, factor: 0.02, pct: 75, ayuda: '$13.62/L puro + 75% Ad-Valorem' }
        };

        let itemsImportacion = [];
        let itemsCalculados = [];
        let vistaActual = 'COMPLETA';
        let modoDispositivo = window.innerWidth < 1024 ? 'TARJETAS' : 'TABLA';
        let modoFijadoManualmente = false;
        let indiceItemModalICE = null;
        let tarifaISDActiva = 'POR_PARTIDA';

        let chartCostosInstance = null;
        let chartPreciosInstance = null;

        // LITERAL 1: Partidas de prueba con columna de merma (% merma)
        const EMBARQUE_DEMO = [
            { codigo: 'EC-MOT-101', producto: 'Motor Eléctrico Trifásico 5HP', cantidad: 40, mermaPct: 0.0, fobUnitario: 145.00, fleteUnitario: 12.50, seguroUnitario: 1.58, arancelPorcentaje: 5, tarifaISD: 2.5, modalidadICE: 'NINGUNO', iceEspecificoUSD: 0.00, factorICE: 1.00, icePorcentaje: 0, pesoUnitarioKg: 28.0, cbmUnitario: 0.15 },
            { codigo: 'EC-FAR-108', producto: 'Insumo Farmacéutico Inhalador', cantidad: 150, mermaPct: 0.0, fobUnitario: 16.00, fleteUnitario: 1.20, seguroUnitario: 0.17, arancelPorcentaje: 0, tarifaISD: 0, modalidadICE: 'NINGUNO', iceEspecificoUSD: 0.00, factorICE: 1.00, icePorcentaje: 0, pesoUnitarioKg: 0.25, cbmUnitario: 0.005 },
            { codigo: 'EC-PER-204', producto: 'Perfume Eau de Parfum 100ml', cantidad: 120, mermaPct: 2.5, fobUnitario: 24.00, fleteUnitario: 1.80, seguroUnitario: 0.26, arancelPorcentaje: 20, tarifaISD: 5, modalidadICE: 'AD_VALOREM', iceEspecificoUSD: 0.00, factorICE: 1.00, icePorcentaje: 20, pesoUnitarioKg: 0.45, cbmUnitario: 0.012 },
            { codigo: 'EC-LIC-309', producto: 'Whisky Escocés 750ml 40% Alc.', cantidad: 60, mermaPct: 3.0, fobUnitario: 18.50, fleteUnitario: 1.60, seguroUnitario: 0.20, arancelPorcentaje: 25, tarifaISD: 5, modalidadICE: 'MIXTO', iceEspecificoUSD: 10.41, factorICE: 0.30, icePorcentaje: 75, pesoUnitarioKg: 1.35, cbmUnitario: 0.015 },
            { codigo: 'EC-BEB-412', producto: 'Bebida Energizante Pack', cantidad: 200, mermaPct: 1.5, fobUnitario: 8.50, fleteUnitario: 0.85, seguroUnitario: 0.09, arancelPorcentaje: 15, tarifaISD: 5, modalidadICE: 'ESPECIFICO', iceEspecificoUSD: 0.18, factorICE: 1.50, icePorcentaje: 0, pesoUnitarioKg: 1.20, cbmUnitario: 0.008 },
            { codigo: 'EC-VID-601', producto: 'Consola de Videojuegos 4K', cantidad: 30, mermaPct: 0.0, fobUnitario: 210.00, fleteUnitario: 9.50, seguroUnitario: 2.20, arancelPorcentaje: 20, tarifaISD: 5, modalidadICE: 'AD_VALOREM', iceEspecificoUSD: 0.00, factorICE: 1.00, icePorcentaje: 35, pesoUnitarioKg: 3.8, cbmUnitario: 0.04 }
        ];

        function fmtUSD(valor) {
            const num = Number(valor) || 0;
            return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function toggleSeccion(idContenido, idIcono) {
            const contenido = document.getElementById(idContenido);
            const icono = document.getElementById(idIcono);
            if (!contenido || !icono) return;

            if (contenido.classList.contains('hidden')) {
                contenido.classList.remove('hidden');
                icono.classList.remove('-rotate-90');
            } else {
                contenido.classList.add('hidden');
                icono.classList.add('-rotate-90');
            }
        }

        function toggleDetalleTarjeta(idx) {
            const el = document.getElementById('detalleTarjeta_' + idx);
            if (el) el.classList.toggle('hidden');
        }

        function seleccionarModoDispositivo(modo, esManual = true) {
            modoDispositivo = modo;
            if (esManual) modoFijadoManualmente = true;

            const contTarjetas = document.getElementById('contenedorVistaTarjetas');
            const contTabla = document.getElementById('contenedorVistaTabla');
            const barraCols = document.getElementById('barraColumnasTabla');
            const btnTarjetas = document.getElementById('btnModoTarjetas');
            const btnTabla = document.getElementById('btnModoTabla');

            if (modo === 'TARJETAS') {
                contTarjetas.classList.remove('hidden');
                contTabla.classList.add('hidden');
                barraCols.classList.add('hidden');
                btnTarjetas.className = 'min-h-[40px] px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 bg-[#111111] text-[#D4AF37] shadow-sm transition cursor-pointer';
                btnTabla.className = 'min-h-[40px] px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 text-gray-600 hover:text-[#111111] transition cursor-pointer';
            } else {
                contTarjetas.classList.add('hidden');
                contTabla.classList.remove('hidden');
                barraCols.classList.remove('hidden');
                btnTabla.className = 'min-h-[40px] px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 bg-[#111111] text-[#D4AF37] shadow-sm transition cursor-pointer';
                btnTarjetas.className = 'min-h-[40px] px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 text-gray-600 hover:text-[#111111] transition cursor-pointer';
            }
        }

        window.addEventListener('resize', () => {
            if (!modoFijadoManualmente) {
                const nuevoModo = window.innerWidth < 1024 ? 'TARJETAS' : 'TABLA';
                if (nuevoModo !== modoDispositivo) {
                    seleccionarModoDispositivo(nuevoModo, false);
                }
            }
        });

        let toastTimeout;
        function mostrarNotificacion(titulo, mensaje, tipo = 'exito') {
            const toast = document.getElementById('toastNotification');
            const titleEl = document.getElementById('toastTitle');
            const msgEl = document.getElementById('toastMessage');
            const inner = document.getElementById('toastInner');

            titleEl.textContent = titulo;
            msgEl.textContent = mensaje;

            if (tipo === 'error') {
                inner.className = 'bg-[#111111] border-l-4 border-red-500 text-white px-4 py-3.5 rounded-xl shadow-2xl flex items-start gap-3';
                titleEl.className = 'font-bold text-red-400 uppercase tracking-wider text-xs';
            } else {
                inner.className = 'bg-[#111111] border-l-4 border-[#D4AF37] text-white px-4 py-3.5 rounded-xl shadow-2xl flex items-start gap-3';
                titleEl.className = 'font-bold text-[#D4AF37] uppercase tracking-wider text-xs';
            }

            toast.classList.remove('hidden');
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 4500);
        }

        function cerrarToast() {
            document.getElementById('toastNotification').classList.add('hidden');
        }

        // HISTORIAL LOCALSTORAGE
        // Guardado e historial definidos al final, en la etapa 1.

        // AUTOCOMPLETADO ARANCELARIO
        function autocompletarPartidaArancelaria(val) {
            if(!val) return;
            if(val === 'MOT') {
                document.getElementById('nuevoCodigo').value = 'EC-MOT-' + Math.floor(Math.random()*900+100);
                document.getElementById('nuevoProducto').value = 'Motor Eléctrico Trifásico (Bien de Capital)';
                document.getElementById('nuevoArancel').value = 5;
                document.getElementById('nuevoTarifaISD').value = 2.5;
                document.getElementById('nuevoMermaPct').value = 1.0;
                document.getElementById('nuevoCBMUnitario').value = 0.15;
                document.getElementById('nuevoModalidadICE').value = 'NINGUNO';
            } else if(val === 'FAR') {
                document.getElementById('nuevoCodigo').value = 'EC-FAR-' + Math.floor(Math.random()*900+100);
                document.getElementById('nuevoProducto').value = 'Insumo Médico / Farmacéutico';
                document.getElementById('nuevoArancel').value = 0;
                document.getElementById('nuevoTarifaISD').value = 0;
                document.getElementById('nuevoMermaPct').value = 0.0;
                document.getElementById('nuevoCBMUnitario').value = 0.005;
                document.getElementById('nuevoModalidadICE').value = 'NINGUNO';
            } else if(val === 'PER') {
                document.getElementById('nuevoCodigo').value = 'EC-PER-' + Math.floor(Math.random()*900+100);
                document.getElementById('nuevoProducto').value = 'Perfume Eau de Parfum 100ml';
                document.getElementById('nuevoArancel').value = 20;
                document.getElementById('nuevoTarifaISD').value = 5;
                document.getElementById('nuevoMermaPct').value = 2.5;
                document.getElementById('nuevoCBMUnitario').value = 0.012;
                document.getElementById('nuevoModalidadICE').value = 'AD_VALOREM';
                document.getElementById('nuevoICE').value = 20;
            } else if(val === 'LIC') {
                document.getElementById('nuevoCodigo').value = 'EC-LIC-' + Math.floor(Math.random()*900+100);
                document.getElementById('nuevoProducto').value = 'Whisky Escocés 750ml 40% Alc.';
                document.getElementById('nuevoArancel').value = 25;
                document.getElementById('nuevoTarifaISD').value = 5;
                document.getElementById('nuevoMermaPct').value = 3.0;
                document.getElementById('nuevoCBMUnitario').value = 0.015;
                document.getElementById('nuevoModalidadICE').value = 'MIXTO';
                document.getElementById('nuevoICEEspecifico').value = 10.41;
                document.getElementById('nuevoFactorICE').value = 0.30;
                document.getElementById('nuevoICE').value = 75;
            } else if(val === 'VID') {
                document.getElementById('nuevoCodigo').value = 'EC-VID-' + Math.floor(Math.random()*900+100);
                document.getElementById('nuevoProducto').value = 'Consola de Videojuegos 4K';
                document.getElementById('nuevoArancel').value = 20;
                document.getElementById('nuevoTarifaISD').value = 5;
                document.getElementById('nuevoMermaPct').value = 1.0;
                document.getElementById('nuevoCBMUnitario').value = 0.04;
                document.getElementById('nuevoModalidadICE').value = 'AD_VALOREM';
                document.getElementById('nuevoICE').value = 35;
            }
        }

        // CONVERSOR DE MONEDA EXTRANJERA
        function convertirMonedaOrigen() {
            const origen = document.getElementById('monedaOrigen').value;
            const monto = parseFloat(document.getElementById('montoMonedaExtranjera').value) || 0;
            const tasa = parseFloat(document.getElementById('tasaCambioDivisa').value) || 1;
            
            if(origen === 'USD') {
                document.getElementById('nuevoFOB').value = monto.toFixed(4);
            } else {
                const enUSD = tasa > 0 ? (monto / tasa) : monto;
                document.getElementById('nuevoFOB').value = enUSD.toFixed(4);
            }
        }

        function aplicarSimuladorWhatIf() {
            recalcularTodo();
        }

        function abrirModalInfoISD() {
            document.getElementById('modalInfoISD').classList.remove('hidden');
        }

        function cerrarModalInfoISD() {
            document.getElementById('modalInfoISD').classList.add('hidden');
        }

        function seleccionarTarifaISDGlobal(tarifa) {
            tarifaISDActiva = String(tarifa);
            document.getElementById('modoCalculoISD').value = 'AUTO';

            if (tarifaISDActiva !== 'POR_PARTIDA') {
                const numTarifa = parseFloat(tarifaISDActiva) || 0;
                itemsImportacion.forEach(it => {
                    it.tarifaISD = numTarifa;
                });
            }

            actualizarBotonesTarifaISD();
            recalcularTodo();
            mostrarNotificacion('Tarifa ISD Configurada', `Tarifa ISD de ${tarifa}% aplicada.`);
        }

        function actualizarBotonesTarifaISD() {
            const mapa = {
                '5': document.getElementById('btnISD5'),
                '2.5': document.getElementById('btnISD25'),
                '0': document.getElementById('btnISD0'),
                'POR_PARTIDA': document.getElementById('btnISDMixto')
            };

            Object.keys(mapa).forEach(k => {
                const btn = mapa[k];
                if (!btn) return;
                if (k === tarifaISDActiva) {
                    btn.className = 'min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-extrabold border-2 border-[#D4AF37] bg-[#111111] text-[#D4AF37] shadow-xs transition cursor-pointer flex flex-col items-center justify-center';
                } else {
                    btn.className = 'min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border border-gray-300 bg-gray-50 text-gray-700 hover:border-[#D4AF37] transition cursor-pointer flex flex-col items-center justify-center';
                }
            });
        }

        function activarModoAutoISD() {
            document.getElementById('modoCalculoISD').value = 'AUTO';
            recalcularTodo();
        }

        function alEditarISDManual() {
            document.getElementById('modoCalculoISD').value = 'MANUAL';
            recalcularTodo();
        }

        function fijarTasaSeguro(pct) {
            document.getElementById('tasaSeguroPct').value = Number(pct).toFixed(2);
            document.getElementById('modoCalculoSeguro').value = 'AUTO';
            document.getElementById('chkReemplazarSeguroPlantilla').checked = true;
            recalcularTodo();
            mostrarNotificacion('Seguro Configurado', `Seguro automático fijado al ${Number(pct).toFixed(2)}%.`);
        }

        function actualizarBotonesTasaSeguro() {
            const pctActual = leerInputNumero('tasaSeguroPct', 1.00);
            const modoSeg = document.getElementById('modoCalculoSeguro').value;
            const botones = [
                { id: 'btnSeg05', val: 0.50 },
                { id: 'btnSeg10', val: 1.00 },
                { id: 'btnSeg15', val: 1.50 }
            ];
            botones.forEach(b => {
                const el = document.getElementById(b.id);
                if (!el) return;
                if (modoSeg === 'AUTO' && Math.abs(pctActual - b.val) < 0.001) {
                    el.className = 'min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-extrabold border-2 border-blue-500 bg-[#111111] text-[#D4AF37] shadow-xs transition cursor-pointer flex flex-col items-center justify-center';
                } else {
                    el.className = 'min-h-[40px] px-2 py-1.5 rounded-lg text-[11px] font-bold border border-gray-300 bg-gray-50 text-gray-700 hover:border-blue-400 transition cursor-pointer flex flex-col items-center justify-center';
                }
            });
        }

        function activarModoAutoSeguro() {
            document.getElementById('modoCalculoSeguro').value = 'AUTO';
            recalcularTodo();
        }

        function alEditarSeguroManual() {
            document.getElementById('modoCalculoSeguro').value = 'MANUAL';
            recalcularTodo();
        }

        function seleccionarPresetICEGlobal() {
            const clave = document.getElementById('presetICEGlobalSelect').value;
            const cfg = CATALOGO_OFICIAL_ICE_SRI[clave] || CATALOGO_OFICIAL_ICE_SRI.NINGUNO;
            document.getElementById('globalICEEspecificoUSD').value = cfg.usd.toFixed(4);
            document.getElementById('globalICEFactor').value = cfg.factor;
            document.getElementById('globalICEAdValoremPct').value = cfg.pct;
        }

        function aplicarPresetICEATodos() {
            const clave = document.getElementById('presetICEGlobalSelect').value;
            const cfg = CATALOGO_OFICIAL_ICE_SRI[clave] || CATALOGO_OFICIAL_ICE_SRI.NINGUNO;
            const usd = leerInputNumero('globalICEEspecificoUSD', 0);
            const factor = leerInputNumero('globalICEFactor', 1);
            const pct = leerInputNumero('globalICEAdValoremPct', 0);

            let mod = cfg.modalidad;
            if (usd > 0 && pct > 0) mod = 'MIXTO';
            else if (usd > 0) mod = 'ESPECIFICO';
            else if (pct > 0) mod = 'AD_VALOREM';
            else mod = 'NINGUNO';

            itemsImportacion.forEach(it => {
                it.modalidadICE = mod;
                it.iceEspecificoUSD = usd;
                it.factorICE = factor;
                it.icePorcentaje = pct;
            });

            recalcularTodo();
            mostrarNotificacion('ICE Aplicado', `Regla ICE ${mod} asignada a todos los productos.`);
        }

        function abrirModalCatalogoICE(indexItem = null) {
            indiceItemModalICE = indexItem;
            const badge = document.getElementById('badgeProductoSeleccionadoICE');
            document.getElementById('modalICESelectRubro').value = 'NINGUNO';

            if (indexItem !== null && itemsImportacion[indexItem]) {
                const it = itemsImportacion[indexItem];
                badge.textContent = `Ítem: ${it.codigo} - ${it.producto}`;
                document.getElementById('modalICEModalidad').value = it.modalidadICE || 'NINGUNO';
                document.getElementById('modalICEEspecificoUSD').value = Number(it.iceEspecificoUSD || 0).toFixed(4);
                document.getElementById('modalICEFactor').value = Number(it.factorICE || 1);
                document.getElementById('modalICEAdValoremPct').value = Number(it.icePorcentaje || 0);
                document.getElementById('modalICEBaseDeclarada').value = Number(it.iceBaseDeclarada || 0).toFixed(4);
                document.getElementById('modalICEBaseDeclarada').disabled = false;
            } else {
                badge.textContent = 'Todo el Embarque';
                document.getElementById('modalICEModalidad').value = 'NINGUNO';
                document.getElementById('modalICEEspecificoUSD').value = document.getElementById('globalICEEspecificoUSD').value;
                document.getElementById('modalICEFactor').value = document.getElementById('globalICEFactor').value;
                document.getElementById('modalICEAdValoremPct').value = document.getElementById('globalICEAdValoremPct').value;
                document.getElementById('modalICEBaseDeclarada').value = '0';
                document.getElementById('modalICEBaseDeclarada').disabled = true;
            }

            actualizarVistaPreviaModalICE();
            document.getElementById('modalCatalogoICE').classList.remove('hidden');
        }

        function cerrarModalCatalogoICE() {
            document.getElementById('modalCatalogoICE').classList.add('hidden');
        }

        function cargarRubroEnModalICE() {
            const clave = document.getElementById('modalICESelectRubro').value;
            const cfg = CATALOGO_OFICIAL_ICE_SRI[clave] || CATALOGO_OFICIAL_ICE_SRI.NINGUNO;
            document.getElementById('modalICEModalidad').value = cfg.modalidad;
            document.getElementById('modalICEEspecificoUSD').value = cfg.usd.toFixed(4);
            document.getElementById('modalICEFactor').value = cfg.factor;
            document.getElementById('modalICEAdValoremPct').value = cfg.pct;
            document.getElementById('ayudaModalICEFactor').textContent = cfg.ayuda;
            actualizarVistaPreviaModalICE();
        }

        function actualizarVistaPreviaModalICE() {
            const mod = document.getElementById('modalICEModalidad').value;
            const usd = leerInputNumero('modalICEEspecificoUSD', 0);
            const factor = leerInputNumero('modalICEFactor', 1);
            const pct = leerInputNumero('modalICEAdValoremPct', 0);
            const previewEl = document.getElementById('montoPreviewModalICE');

            if (indiceItemModalICE !== null && itemsCalculados[indiceItemModalICE]) {
                const it = itemsCalculados[indiceItemModalICE];
                const baseExAduanaEstimada = it.cifTotal + it.adValoremTotal + it.fodinfaTotal;
                const baseDeclarada = leerInputNumero('modalICEBaseDeclarada', 0);
                const baseICE = baseDeclarada > 0 ? baseDeclarada : baseExAduanaEstimada;
                const espCalc = (mod === 'ESPECIFICO' || mod === 'MIXTO') ? (it.cantidad * factor * usd) : 0;
                const advCalc = (mod === 'AD_VALOREM' || mod === 'MIXTO') ? (baseICE * (pct / 100)) : 0;
                previewEl.textContent = `Estimado Ítem: ${fmtUSD(espCalc + advCalc)} (${baseDeclarada > 0 ? 'base declarada' : 'base estimada'})`;
            } else {
                previewEl.textContent = `Modalidad: ${mod} ($${usd.toFixed(2)} | ${pct}%)`;
            }
        }

        function confirmarConfiguracionModalICE() {
            const mod = document.getElementById('modalICEModalidad').value;
            const usd = leerInputNumero('modalICEEspecificoUSD', 0);
            const factor = leerInputNumero('modalICEFactor', 1);
            const pct = leerInputNumero('modalICEAdValoremPct', 0);

            if (indiceItemModalICE !== null && itemsImportacion[indiceItemModalICE]) {
                const it = itemsImportacion[indiceItemModalICE];
                it.modalidadICE = mod;
                it.iceEspecificoUSD = (mod === 'ESPECIFICO' || mod === 'MIXTO') ? usd : 0;
                it.factorICE = factor;
                it.icePorcentaje = (mod === 'AD_VALOREM' || mod === 'MIXTO') ? pct : 0;
                it.iceBaseDeclarada = Math.max(0, leerInputNumero('modalICEBaseDeclarada', 0));
                mostrarNotificacion('ICE Actualizado', `Configurado para ${it.producto}.`);
            } else {
                itemsImportacion.forEach(it => {
                    it.modalidadICE = mod;
                    it.iceEspecificoUSD = (mod === 'ESPECIFICO' || mod === 'MIXTO') ? usd : 0;
                    it.factorICE = factor;
                    it.icePorcentaje = (mod === 'AD_VALOREM' || mod === 'MIXTO') ? pct : 0;
                    it.iceBaseDeclarada = 0;
                });
                mostrarNotificacion('ICE Masivo', `Se actualizó la modalidad ${mod} en todo el embarque.`);
            }

            cerrarModalCatalogoICE();
            recalcularTodo();
        }

        function cargarDatosDemo() {
            itemsImportacion = JSON.parse(JSON.stringify(EMBARQUE_DEMO));
            tarifaISDActiva = 'POR_PARTIDA';
            document.getElementById('modoCalculoISD').value = 'AUTO';
            document.getElementById('modoCalculoSeguro').value = 'AUTO';
            document.getElementById('tasaSeguroPct').value = '1.00';
            document.getElementById('chkReemplazarSeguroPlantilla').checked = true;
            
            document.getElementById('sliderFletePct').value = 0;
            document.getElementById('sliderArancelPct').value = 0;

            actualizarBotonesTarifaISD();
            document.getElementById('estadoEmbarqueTexto').textContent = `Demo Activo (${itemsImportacion.length} partidas con Merma, ISD y 3 modalidades ICE)`;
            document.getElementById('badgeArchivoCargado').textContent = 'Demo Activo';
            recalcularTodo();
            mostrarNotificacion('Demo Cargado', 'Se restablecieron los ítems de prueba.');
        }

        function borrarDatos() {
            itemsImportacion = [];
            recalcularTodo();
            document.getElementById('estadoEmbarqueTexto').textContent = 'Embarque vacío (0 ítems)';
            document.getElementById('badgeArchivoCargado').textContent = 'Vacío';
            mostrarNotificacion('Embarque Borrado', 'Se han eliminado todos los productos del listado.');
        }

        function leerInputNumero(id, valorPorDefecto = 0) {
            const el = document.getElementById(id);
            if (!el) return valorPorDefecto;
            const val = parseFloat(el.value);
            return isNaN(val) ? valorPorDefecto : val;
        }

        function actualizarPreciosComerciales() {
            recalcularTodo();
        }

        function restablecerValoresBaseLiteral1() {
            document.getElementById('gastosAgenteAduana').value = '260.00';
            document.getElementById('tasasPortuarias').value = '190.00';
            document.getElementById('gastosNaviera').value = '180.00';
            document.getElementById('gastosContenedor').value = '220.00';
            document.getElementById('transporteDescarga').value = '350.00';
            document.getElementById('gastosViaticos').value = '85.00';
            document.getElementById('extrasRuta').value = '65.00';
            document.getElementById('comisionBancos').value = '65.00';
            document.getElementById('fleteInternacionalGlobal').value = '0.00';
            document.getElementById('tasaIVA').value = '15';
            if (document.getElementById('tasaIVAVenta')) document.getElementById('tasaIVAVenta').value = '15';
            if (document.getElementById('ivaImportacionRecuperable')) document.getElementById('ivaImportacionRecuperable').checked = true;
            document.getElementById('metodoProrrateo').value = 'CIF';
            document.getElementById('impuestoICE').value = '0.00';
            
            document.getElementById('sliderFletePct').value = 0;
            document.getElementById('sliderArancelPct').value = 0;

            tarifaISDActiva = 'POR_PARTIDA';
            document.getElementById('modoCalculoISD').value = 'AUTO';
            document.getElementById('baseCalculoISD').value = 'FOB_FLETE';
            document.getElementById('modoCalculoSeguro').value = 'AUTO';
            document.getElementById('baseCalculoSeguro').value = 'FOB_FLETE';
            document.getElementById('tasaSeguroPct').value = '1.00';
            document.getElementById('chkReemplazarSeguroPlantilla').checked = true;
            actualizarBotonesTarifaISD();

            recalcularTodo();
            mostrarNotificacion('Valores Restablecidos', 'Se restablecieron los gastos locales, seguro e ISD.');
        }

        function renderizarDesgloseGastosLocales(rubros, totalGastosLocalesMasISD) {
            const barraEl = document.getElementById('barraProgresoGastosLocales');
            const pillsEl = document.getElementById('pillsDesgloseGastosLocales');
            const labelSumaEl = document.getElementById('labelSumaNueveRubros');
            if (!barraEl || !pillsEl || !labelSumaEl) return;

            const total = Math.max(0.01, totalGastosLocalesMasISD);
            labelSumaEl.textContent = `${fmtUSD(totalGastosLocalesMasISD)} (100%)`;
            labelSumaEl.style.color = '#FF1744';
            labelSumaEl.style.textShadow = '0 0 4px rgba(255,23,68,.65), 0 0 9px rgba(255,23,68,.35)';
            labelSumaEl.style.border = '1px solid rgba(255,23,68,.55)';

            barraEl.innerHTML = rubros.map(r => {
                const pct = totalGastosLocalesMasISD > 0 ? ((r.valor / total) * 100) : 0;
                if (pct <= 0) return '';
                return `<div style="width:${pct.toFixed(2)}%; background-color:${r.color}" title="${r.nombre}: ${fmtUSD(r.valor)} (${pct.toFixed(1)}%)" class="h-full transition-all duration-300"></div>`;
            }).join('');

            pillsEl.innerHTML = rubros.map(r => {
                const pct = totalGastosLocalesMasISD > 0 ? ((r.valor / total) * 100).toFixed(1) : '0.0';
                return `
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-[11px]">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color:${r.color}"></span>
                            <span class="font-medium text-gray-700 truncate" title="${r.nombre}">${r.nombre}</span>
                        </div>
                        <div class="text-right shrink-0 ml-1.5">
                            <span class="font-extrabold" style="color:#FF1744;text-shadow:0 0 4px rgba(255,23,68,.65),0 0 8px rgba(255,23,68,.3);">${fmtUSD(r.valor)}</span>
                            <span class="text-[10px] text-gray-500 ml-0.5">(${pct}%)</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Validación preventiva de datos de entrada: evita resultados aparentemente válidos con datos inválidos.
        function validarDatosCalculo() {
            const errores = [];
            document.querySelectorAll('[data-error-calculo]').forEach(el => {el.removeAttribute('aria-invalid');el.removeAttribute('data-error-calculo');});
            const numeros = document.querySelectorAll('input[type="number"], input[type="range"]');
            const esControlAuxiliar = el => Boolean(el.closest('#mpiDialogoArancel, #modalNuevoItem, #modalCatalogoICE, #modalInfoISD, #panelHerramientasPRO, #panelPremiumComercial, #panelPremium, #panelProveedoresUX, #panelUsuariosUX, #panelReportesUX, #mpi-panel-proveedores, #mpi-panel-administracion, #mpi-panel-reportes, #mpi-panel-rentabilidad'));
            numeros.forEach(el => {
                if (el.disabled || esControlAuxiliar(el)) return;
                if (el.validity.badInput || (el.required && el.value === '')) { errores.push(`${el.id}: ingrese un número válido.`); return; }
                if (el.value === '') return; // los modales y módulos auxiliares se validan al guardar su propio formulario
                const valor = Number(el.value);
                if (!Number.isFinite(valor)) {
                    errores.push(`${el.id || 'Campo numérico'}: ingrese un número válido.`);
                    return;
                }
                if (el.min !== '' && valor < Number(el.min)) errores.push(`${el.id || 'Campo numérico'}: el valor no puede ser menor que ${el.min}.`);
                if (el.max !== '' && valor > Number(el.max)) errores.push(`${el.id || 'Campo numérico'}: el valor no puede ser mayor que ${el.max}.`);
            });

            const camposMonetariosNoNegativos = [
                'gastosAgenteAduana','tasasPortuarias','gastosNaviera','gastosContenedor',
                'transporteDescarga','gastosViaticos','extrasRuta','comisionBancos','impuestoISD',
                'seguroInternacionalGlobal','fleteInternacionalGlobal','otrosRubrosGravadosDeclaracion',
                'impuestoICE','globalICEEspecificoUSD','globalICEFactor','globalICEAdValoremPct',
                'montoMonedaExtranjera','tasaCambioDivisa'
            ];
            camposMonetariosNoNegativos.forEach(id => {
                const el = document.getElementById(id);
                if (!el || el.value === '') return;
                const valor = Number(el.value);
                if (!Number.isFinite(valor) || valor < 0) errores.push(`${id}: el importe debe ser un número igual o mayor que cero.`);
            });

            if (!Array.isArray(itemsImportacion) || itemsImportacion.length === 0) {
                errores.push('Agregue al menos una partida válida antes de calcular o exportar.');
            }
            if (Array.isArray(itemsImportacion)) {
                itemsImportacion.forEach((item, idx) => {
                    const n = idx + 1;
                    if(item.usarPreferenciaOrigen && !mpiTarifaPreferencialValida(item))errores.push(`Partida ${n}: tarifa preferencial inválida.`);
                    if(!['NINGUNO','ESPECIFICO','AD_VALOREM','MIXTO'].includes(item.modalidadICE||'NINGUNO'))errores.push(`Partida ${n}: modalidad ICE no reconocida.`);
                    if (!Number.isFinite(Number(item.cantidad)) || Number(item.cantidad) <= 0) errores.push(`Partida ${n}: la cantidad debe ser mayor que cero.`);
                    if (!Number.isFinite(Number(item.fobUnitario)) || Number(item.fobUnitario) <= 0) errores.push(`Partida ${n}: el FOB unitario debe ser mayor que cero.`);
                    if (!Number.isFinite(Number(item.fleteUnitario)) || Number(item.fleteUnitario) < 0) errores.push(`Partida ${n}: el flete unitario no puede ser negativo.`);
                    if (!Number.isFinite(Number(item.seguroUnitario)) || Number(item.seguroUnitario) < 0) errores.push(`Partida ${n}: el seguro unitario no puede ser negativo.`);
                    if (!Number.isFinite(Number(item.arancelPorcentaje)) || Number(item.arancelPorcentaje) < 0 || Number(item.arancelPorcentaje) > 100) errores.push(`Partida ${n}: el arancel debe estar entre 0 y 100%.`);
                    const tarifaISDValidar = Number(item.tarifaISD ?? 5);
                    if (!Number.isFinite(tarifaISDValidar) || tarifaISDValidar < 0 || tarifaISDValidar > 100) errores.push(`Partida ${n}: la tarifa ISD debe estar entre 0 y 100%; verifique la tarifa legal de su operación.`);
                    if (!Number.isFinite(Number(item.mermaPct)) || Number(item.mermaPct) < 0 || Number(item.mermaPct) > 90) errores.push(`Partida ${n}: la merma debe estar entre 0 y 90%.`);
                    if (!Number.isFinite(Number(item.iceEspecificoUSD)) || Number(item.iceEspecificoUSD) < 0) errores.push(`Partida ${n}: el ICE específico no puede ser negativo.`);
                    if (!Number.isFinite(Number(item.icePorcentaje)) || Number(item.icePorcentaje) < 0 || Number(item.icePorcentaje) > 500) errores.push(`Partida ${n}: el ICE ad valorem debe estar entre 0 y 500%.`);
                    if (!Number.isFinite(Number(item.factorICE)) || Number(item.factorICE) < 0) errores.push(`Partida ${n}: el factor ICE no puede ser negativo.`);
                });
            }
            errores.forEach(error => {
                const el = document.getElementById(error.split(':')[0]);
                if (el && el.matches('input')) {el.setAttribute('aria-invalid','true');el.setAttribute('data-error-calculo','true');}
            });
            return [...new Set(errores)];
        }

        function actualizarBannerValidacion(errores) {
            const banner = document.getElementById('validacionCalculoBanner');
            const lista = document.getElementById('validacionCalculoLista');
            if (!banner || !lista) return;
            lista.innerHTML = '';
            const vacio = errores?.length === 1 && Array.isArray(itemsImportacion) && itemsImportacion.length === 0;
            banner.dataset.estado = vacio ? 'vacio' : 'error';
            banner.setAttribute('role', vacio ? 'status' : 'alert');
            banner.setAttribute('aria-live', vacio ? 'polite' : 'assertive');
            document.getElementById('validacionCalculoTitulo').textContent = vacio ? 'Comienza tu importación' : 'Revisión requerida antes de calcular';
            document.getElementById('validacionAcciones').style.display = itemsImportacion.length ? 'none' : 'flex';
            if (vacio) errores = ['Todavía no hay productos. Agrega uno con cantidad y FOB unitario mayores que cero, o importa tu Excel. Los resultados estarán disponibles al completar los datos.'];
            if (!errores || errores.length === 0) {
                banner.classList.add('hidden');
                return;
            }
            errores.slice(0, 8).forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                lista.appendChild(li);
            });
            if (errores.length > 8) {
                const li = document.createElement('li');
                li.textContent = `Y ${errores.length - 8} observaciones adicionales. Revise también las partidas indicadas.`;
                lista.appendChild(li);
            }
            banner.classList.remove('hidden');
        }

        function validarAntesDeExportar(nombreReporte = 'reporte') {
            const errores = validarDatosCalculo();
            actualizarBannerValidacion(errores);
            if (errores.length) {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Exportación pendiente', itemsImportacion.length ? `Corrija ${errores.length} error(es) antes de exportar ${nombreReporte}.` : 'Agregue un producto o importe su Excel antes de exportar.', 'error');
                }
                document.getElementById('validacionCalculoBanner')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            }
            return true;
        }

        function invalidarResultadosCalculo() {
            itemsCalculados = [];
            document.querySelectorAll('[id^="kpi"], [id^="mobileBar"], [id^="utilidadTotal"]').forEach(el => {
                if (!el.children.length && !el.id.startsWith('kpiLabel')) el.textContent = '—';
            });
            ['tablaLiquidacionBody','tablaLiquidacionFoot','contenedorVistaTarjetas','leyendaComposicion','barraProgresoGastosLocales','pillsDesgloseGastosLocales'].forEach(id => {
                const el = document.getElementById(id); if (el) el.replaceChildren();
            });
            const suma = document.getElementById('labelSumaNueveRubros'); if (suma) suma.textContent = 'Pendiente de cálculo';
            if (chartCostosInstance) {chartCostosInstance.destroy();chartCostosInstance = null;}
            if (chartPreciosInstance) {chartPreciosInstance.destroy();chartPreciosInstance = null;}
        }

        // ================= MOTOR PRINCIPAL DE LIQUIDACIÓN =================
        function recalcularTodo() {
            const erroresIntegridad = validarDatosCalculo();
            actualizarBannerValidacion(erroresIntegridad);
            if (erroresIntegridad.length) { invalidarResultadosCalculo(); return; }
            actualizarBotonesTarifaISD();
            actualizarBotonesTasaSeguro();

            const varFletePct = parseFloat(document.getElementById('sliderFletePct')?.value || 0) / 100;
            const varArancelPct = parseFloat(document.getElementById('sliderArancelPct')?.value || 0) / 100;
            if(document.getElementById('labelSliderFlete')) document.getElementById('labelSliderFlete').textContent = (varFletePct * 100) + '%';
            if(document.getElementById('labelSliderArancel')) document.getElementById('labelSliderArancel').textContent = (varArancelPct * 100) + '%';

            const gAgenteAduana = leerInputNumero('gastosAgenteAduana', 0);
            const gTasasPortuarias = leerInputNumero('tasasPortuarias', 0);
            const gNaviera = leerInputNumero('gastosNaviera', 0);
            const gContenedor = leerInputNumero('gastosContenedor', 0);
            const gTransporte = leerInputNumero('transporteDescarga', 0);
            const gViaticos = leerInputNumero('gastosViaticos', 0);
            const gExtrasRuta = leerInputNumero('extrasRuta', 0);
            const gBancos = leerInputNumero('comisionBancos', 0);
            const totalOchoGastosSinISD = gAgenteAduana + gTasasPortuarias + gNaviera + gContenedor + gTransporte + gViaticos + gExtrasRuta + gBancos;

            const iceGlobalUSD = leerInputNumero('impuestoICE', 0);
            const fleteGlobalExtra = leerInputNumero('fleteInternacionalGlobal', 0);
            const tasaIVA = Math.max(0, Math.min(30, leerInputNumero('tasaIVA', 15)));
            const tasaIVAVenta = Math.max(0, Math.min(30, leerInputNumero('tasaIVAVenta', 15)));
            const ivaImportacionRecuperable = document.getElementById('ivaImportacionRecuperable')?.checked !== false;
            const otrosRubrosGravadosDeclaracion = Math.max(0, leerInputNumero('otrosRubrosGravadosDeclaracion', 0));
            const metodoProrrateo = document.getElementById('metodoProrrateo').value;

            const modoSeguro = document.getElementById('modoCalculoSeguro').value;
            const tasaSeguroPct = leerInputNumero('tasaSeguroPct', 1.00);
            const baseCalculoSeguro = document.getElementById('baseCalculoSeguro').value;
            const reemplazarSeguroPlantilla = document.getElementById('chkReemplazarSeguroPlantilla').checked;

            const modoISD = document.getElementById('modoCalculoISD').value;
            const baseCalculoISD = document.getElementById('baseCalculoISD').value;

            document.getElementById('badgeIVA').textContent = tasaIVA + '%';
            document.getElementById('kpiLabelTasaIVA').textContent = tasaIVA + '%';

            const margenMay = leerInputNumero('margenMayorista', 15) / 100;
            const margenEve = leerInputNumero('margenEventual', 25) / 100;
            const margenPVP = leerInputNumero('margenPVP', 40) / 100;
            const formulaMargen = document.getElementById('formulaMargen').value;

            let sumaFOB = 0;
            let sumaFleteBase = 0;
            let sumaPeso = 0;
            let sumaCantidad = 0;
            let sumaCBM = 0;

            itemsImportacion.forEach(item => {
                const cant = Number(item.cantidad);
                const fobU = Math.max(0, Number(item.fobUnitario) || 0);
                const fleteU = Math.max(0, Number(item.fleteUnitario) || 0);
                const pesoU = Math.max(0, Number(item.pesoUnitarioKg) || 0);
                const cbmU = Math.max(0, Number(item.cbmUnitario) || 0);
                
                sumaFOB += cant * fobU;
                sumaFleteBase += cant * (fleteU * (1 + varFletePct));
                sumaPeso += cant * pesoU;
                sumaCantidad += cant;
                sumaCBM += cant * cbmU;
            });

            // LITERAL 2: COMPARADOR LOGÍSTICO LCL vs FCL (W/M)
            const pesoToneladas = sumaPeso / 1000;
            document.getElementById('kpiLogisticaCBM').textContent = sumaCBM.toFixed(2) + ' m³';
            document.getElementById('kpiLogisticaPeso').textContent = pesoToneladas.toFixed(2) + ' Ton';

            const badgeCont = document.getElementById('badgeRecomendacionContenedor');
            const consejoCont = document.getElementById('kpiLogisticaConsejo');

            if (sumaCBM <= 0) {
                badgeCont.textContent = 'Sin Carga';
                badgeCont.className = 'text-[9px] font-extrabold bg-gray-600 text-white px-2 py-0.5 rounded';
                consejoCont.textContent = 'Ingrese ítems para calcular flete marítimo.';
            } else if (sumaCBM < 14) {
                badgeCont.textContent = 'Sugerido: LCL';
                badgeCont.className = 'text-[9px] font-extrabold bg-blue-600 text-white px-2 py-0.5 rounded';
                const mayorWM = sumaCBM >= pesoToneladas ? `${sumaCBM.toFixed(2)} CBM (Volumen)` : `${pesoToneladas.toFixed(2)} Ton (Peso)`;
                consejoCont.textContent = `Orientación preliminar por volumen: ${mayorWM}. Compare cotizaciones LCL/FCL, recargos, ruta y días libres antes de decidir.`;
            } else if (sumaCBM < 32) {
                badgeCont.textContent = 'Sugerido: FCL 20GP';
                badgeCont.className = 'text-[9px] font-extrabold bg-emerald-600 text-white px-2 py-0.5 rounded';
                const pctOcupacion = ((sumaCBM / 28) * 100).toFixed(0);
                consejoCont.textContent = `Ocupación teórica aproximada: ${pctOcupacion}% de 28 m³ para 20GP. No implica que FCL sea más barato; compare cotizaciones y restricciones de carga.`;
            } else {
                badgeCont.textContent = 'Sugerido: FCL 40HC';
                badgeCont.className = 'text-[9px] font-extrabold bg-[#FF6B00] text-white px-2 py-0.5 rounded';
                const pctOcupacion = ((sumaCBM / 68) * 100).toFixed(0);
                consejoCont.textContent = `Ocupación teórica aproximada: ${pctOcupacion}% de 68 m³ para 40HC. Verifique volumen útil, peso, dimensiones y cotizaciones.`;
            }

            const fleteTotalEmbarque = sumaFleteBase + fleteGlobalExtra;
            const baseSeguroGlobal = (baseCalculoSeguro === 'FOB_FLETE') ? (sumaFOB + fleteTotalEmbarque) : sumaFOB;

            let seguroGlobalCalculado = 0;
            if (modoSeguro === 'AUTO') {
                seguroGlobalCalculado = baseSeguroGlobal * (tasaSeguroPct / 100);
                document.getElementById('seguroInternacionalGlobal').value = seguroGlobalCalculado.toFixed(4);
                document.getElementById('badgeModoSeguro').textContent = `Cálculo Automático (${tasaSeguroPct.toFixed(2)}%)`;
                document.getElementById('badgeModoSeguro').className = 'text-[10px] font-extrabold bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full';
                document.getElementById('badgeHeaderSeguro').textContent = `Auto ${tasaSeguroPct.toFixed(1)}%`;
                document.getElementById('resumenFormulaSeguro').textContent =
                    `Seguro automático: ${tasaSeguroPct.toFixed(2)}% sobre ${baseCalculoSeguro === 'FOB_FLETE' ? 'FOB+Flete' : 'FOB'} → ${fmtUSD(seguroGlobalCalculado)}`;
            } else {
                seguroGlobalCalculado = leerInputNumero('seguroInternacionalGlobal', 0);
                document.getElementById('badgeModoSeguro').textContent = 'Modo Manual ($)';
                document.getElementById('badgeModoSeguro').className = 'text-[10px] font-extrabold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full';
                document.getElementById('badgeHeaderSeguro').textContent = fmtUSD(seguroGlobalCalculado);
                document.getElementById('resumenFormulaSeguro').textContent =
                    `Seguro en modo manual: ${fmtUSD(seguroGlobalCalculado)} asignado a la Base CIF.`;
            }

            let sumaCIFPreliminar = 0;
            let sumaISDAutomatico = 0;

            const preliminares = itemsImportacion.map(item => {
                const cant = Number(item.cantidad);
                const fobTotal = cant * (Number(item.fobUnitario) || 0);
                const partFOB = sumaFOB > 0 ? (fobTotal / sumaFOB) : (1 / Math.max(1, itemsImportacion.length));
                const fleteExtraAsignado = fleteGlobalExtra * partFOB;
                
                const fleteUnitarioModificado = (Number(item.fleteUnitario) || 0) * (1 + varFletePct);
                const fleteTotal = (cant * fleteUnitarioModificado) + fleteExtraAsignado;

                let seguroTotal = 0;
                const baseSegItem = (baseCalculoSeguro === 'FOB_FLETE') ? (fobTotal + fleteTotal) : fobTotal;
                if (modoSeguro === 'AUTO') {
                    if (reemplazarSeguroPlantilla) {
                        seguroTotal = baseSegItem * (tasaSeguroPct / 100);
                    } else {
                        seguroTotal = (cant * (Number(item.seguroUnitario) || 0)) + (seguroGlobalCalculado * partFOB);
                    }
                } else {
                    seguroTotal = reemplazarSeguroPlantilla ? (seguroGlobalCalculado * partFOB) : ((cant * (Number(item.seguroUnitario) || 0)) + (seguroGlobalCalculado * partFOB));
                }

                const cifTotal = fobTotal + fleteTotal + seguroTotal;
                sumaCIFPreliminar += cifTotal;

                const tarifaISDItem = (tarifaISDActiva !== 'POR_PARTIDA')
                    ? (parseFloat(tarifaISDActiva) || 0)
                    : (Number(item.tarifaISD ?? 5));
                const baseISDItem = baseCalculoISD === 'CIF' ? cifTotal : ((baseCalculoISD === 'FOB_FLETE') ? (fobTotal + fleteTotal) : fobTotal);
                const isdAutoItem = baseISDItem * (tarifaISDItem / 100);
                sumaISDAutomatico += isdAutoItem;

                return { cant, fobTotal, fleteTotal, seguroTotal, cifTotal, tarifaISDItem, baseISDItem, isdAutoItem, partFOB };
            });

            let gISD = 0;
            if (modoISD === 'AUTO') {
                gISD = sumaISDAutomatico;
                document.getElementById('impuestoISD').value = gISD.toFixed(4);
                const baseTotalISD = baseCalculoISD === 'CIF' ? sumaCIFPreliminar : ((baseCalculoISD === 'FOB_FLETE') ? (sumaFOB + fleteTotalEmbarque) : sumaFOB);
                const pctEfectivoISD = baseTotalISD > 0 ? ((gISD / baseTotalISD) * 100).toFixed(2) : '0.00';
                document.getElementById('labelPorcentajeEfectivoISD').textContent = `Efectivo: ${pctEfectivoISD}%`;
                document.getElementById('badgeModoISD').textContent = `Cálculo Automático (${tarifaISDActiva === 'POR_PARTIDA' ? 'Por Partida' : tarifaISDActiva + '%'})`;
                document.getElementById('badgeModoISD').className = 'text-[10px] font-extrabold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full';
                document.getElementById('badgeHeaderISD').textContent = `Auto (${pctEfectivoISD}%)`;
                document.getElementById('resumenFormulaISD').textContent =
                    `ISD orientativo sobre ${baseCalculoISD === 'CIF' ? 'CIF estimado' : (baseCalculoISD === 'FOB_FLETE' ? 'FOB+Flete' : 'FOB')} → ${fmtUSD(gISD)}. No es liquidación legal: confirme base imponible, exenciones, subpartida y tarifa vigente para cada pago.`;
            } else {
                gISD = leerInputNumero('impuestoISD', 0);
                document.getElementById('labelPorcentajeEfectivoISD').textContent = 'Manual';
                document.getElementById('badgeModoISD').textContent = 'Modo Manual ($)';
                document.getElementById('badgeModoISD').className = 'text-[10px] font-extrabold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full';
                document.getElementById('badgeHeaderISD').textContent = fmtUSD(gISD);
                document.getElementById('resumenFormulaISD').textContent =
                    `ISD fijado manualmente en ${fmtUSD(gISD)}.`;
            }

            const totalGastosLocalesMasISD = totalOchoGastosSinISD + gISD;
            document.getElementById('resumenGastosLocalesBadge').textContent = fmtUSD(totalGastosLocalesMasISD + iceGlobalUSD);

            const rubrosDesglose = [
                { id: 'gastosAgenteAduana', nombre: '1. Agente de Aduana', valor: gAgenteAduana, color: '#111111' },
                { id: 'tasasPortuarias', nombre: '2. Tasas Portuarias', valor: gTasasPortuarias, color: '#D4AF37' },
                { id: 'gastosNaviera', nombre: '3. Gastos Naviera', valor: gNaviera, color: '#4B5563' },
                { id: 'gastosContenedor', nombre: '4. Contenedor', valor: gContenedor, color: '#6B7280' },
                { id: 'transporteDescarga', nombre: '5. Flete Terrestre', valor: gTransporte, color: '#10B981' },
                { id: 'gastosViaticos', nombre: '6. Viáticos', valor: gViaticos, color: '#8B5CF6' },
                { id: 'extrasRuta', nombre: '7. Extras de Ruta', valor: gExtrasRuta, color: '#FF6B00' },
                { id: 'comisionBancos', nombre: '8. Comisión Bancos', valor: gBancos, color: '#3B82F6' },
                { id: 'impuestoISD', nombre: '9. Impuesto ISD', valor: gISD, color: '#EF4444' }
            ];
            renderizarDesgloseGastosLocales(rubrosDesglose, totalGastosLocalesMasISD);

            let totUnidades = 0, totUnidadesVendibles = 0, totPeso = 0, totFOB = 0, totFlete = 0, totSeguro = 0;
            let totFleteSeguro = 0, totCIF = 0, totAdValorem = 0, totFODINFA = 0;
            let totICEEspecifico = 0, totICEAdValorem = 0, totICEGlobal = 0, totICE = 0, totISD = 0;
            let totGastosLocales = 0, totCostoBodega = 0, totIVAImportacion = 0, totOtrosRubrosGravados = 0;
            let totVentaMayorista = 0, totVentaEventual = 0, totVentaPVP = 0;

            itemsCalculados = itemsImportacion.map((item, idx) => {
                const pre = preliminares[idx];
                const cant = pre.cant;
                
                // LITERAL 1: CÁLCULO DE MERMA Y UNIDADES ÚTILES VENDIBLES
                const mermaPct = Math.max(0, Math.min(90, Number(item.mermaPct) || 0));
                const unidadesVendibles = Math.max(0.000001, cant * (1 - (mermaPct / 100)));

                const pesoTotal = cant * (Number(item.pesoUnitarioKg) || 0);
                const cbmTotal = cant * Math.max(0, Number(item.cbmUnitario) || 0);
                const fobTotal = pre.fobTotal;
                const fleteTotal = pre.fleteTotal;
                const seguroTotal = pre.seguroTotal;
                const fleteSeguroTotal = fleteTotal + seguroTotal;
                const cifTotal = pre.cifTotal;

                const arancelBase = mpiTarifaAplicable(item);
                const arancelEfectivoPct = Math.max(0, arancelBase * (1 + varArancelPct));

                const adValoremTotal = cifTotal * (arancelEfectivoPct / 100);
                const fodinfaTotal = cifTotal * 0.005;
                const baseExAduana = cifTotal + adValoremTotal + fodinfaTotal;

                const iceEspUSD = Math.max(0, Number(item.iceEspecificoUSD) || 0);
                const factorICE = Math.max(0, Number(item.factorICE ?? 1));
                const icePct = Math.max(0, Number(item.icePorcentaje) || 0);
                let modalidadICE = item.modalidadICE || 'NINGUNO';

                if (modalidadICE === 'NINGUNO') {
                    if (iceEspUSD > 0 && icePct > 0) modalidadICE = 'MIXTO';
                    else if (iceEspUSD > 0) modalidadICE = 'ESPECIFICO';
                    else if (icePct > 0) modalidadICE = 'AD_VALOREM';
                }

                let iceEspecificoItem = 0;
                if (modalidadICE === 'ESPECIFICO' || modalidadICE === 'MIXTO') {
                    iceEspecificoItem = cant * factorICE * iceEspUSD;
                }

                const iceBaseDeclarada = Math.max(0, Number(item.iceBaseDeclarada) || 0);
                const baseICEAplicada = iceBaseDeclarada > 0 ? iceBaseDeclarada : baseExAduana;
                let iceAdValoremItem = 0;
                if (modalidadICE === 'AD_VALOREM' || modalidadICE === 'MIXTO') {
                    iceAdValoremItem = baseICEAplicada * (icePct / 100);
                }

                const partCIF = sumaCIFPreliminar > 0 ? (cifTotal / sumaCIFPreliminar) : (1 / Math.max(1, itemsImportacion.length));
                const iceGlobalAsignado = iceGlobalUSD * partCIF;

                const iceTotalItem = iceEspecificoItem + iceAdValoremItem + iceGlobalAsignado;
                const otrosRubrosGravadosItem = otrosRubrosGravadosDeclaracion * partCIF;
                const arancelesTotalesItem = adValoremTotal + fodinfaTotal + iceTotalItem;

                let factorProrrateo = 0;
                if (metodoProrrateo === 'CIF' && sumaCIFPreliminar > 0) factorProrrateo = cifTotal / sumaCIFPreliminar;
                else if (metodoProrrateo === 'FOB' && sumaFOB > 0) factorProrrateo = fobTotal / sumaFOB;
                else if (metodoProrrateo === 'PESO' && sumaPeso > 0) factorProrrateo = pesoTotal / sumaPeso;
                else if (metodoProrrateo === 'CANTIDAD' && sumaCantidad > 0) factorProrrateo = cant / sumaCantidad;
                else if (metodoProrrateo === 'VOLUMEN' && sumaCBM > 0) factorProrrateo = cbmTotal / sumaCBM;
                else factorProrrateo = 1 / Math.max(1, itemsImportacion.length);

                const isdAsignado = (modoISD === 'AUTO') ? pre.isdAutoItem : (gISD * factorProrrateo);
                const gastoLocalAsignado = (totalOchoGastosSinISD * factorProrrateo) + isdAsignado;

                const baseIVA = cifTotal + adValoremTotal + fodinfaTotal + iceTotalItem + otrosRubrosGravadosItem;
                const ivaImportacionItem = baseIVA * (tasaIVA / 100);
                const costoBodegaTotal = cifTotal + arancelesTotalesItem + otrosRubrosGravadosItem + gastoLocalAsignado + (ivaImportacionRecuperable ? 0 : ivaImportacionItem);
                
                // Costo unitario real sobre unidades vendibles; el IVA se capitaliza solo si no es recuperable.
                const costoUnitarioBodega = costoBodegaTotal / unidadesVendibles;

                let precioMayorista = 0;
                let precioEventual = 0;
                let precioPVP = 0;

                if (formulaMargen === 'MARGEN_FINANCIERO') {
                    precioMayorista = costoUnitarioBodega / Math.max(0.05, (1 - margenMay));
                    precioEventual = costoUnitarioBodega / Math.max(0.05, (1 - margenEve));
                    precioPVP = costoUnitarioBodega / Math.max(0.05, (1 - margenPVP));
                } else {
                    precioMayorista = costoUnitarioBodega * (1 + margenMay);
                    precioEventual = costoUnitarioBodega * (1 + margenEve);
                    precioPVP = costoUnitarioBodega * (1 + margenPVP);
                }

                const pvpConIVA = precioPVP * (1 + (tasaIVAVenta / 100));

                totUnidades += cant;
                totUnidadesVendibles += unidadesVendibles;
                totPeso += pesoTotal;
                totFOB += fobTotal;
                totFlete += fleteTotal;
                totSeguro += seguroTotal;
                totFleteSeguro += fleteSeguroTotal;
                totCIF += cifTotal;
                totAdValorem += adValoremTotal;
                totFODINFA += fodinfaTotal;
                totICEEspecifico += iceEspecificoItem;
                totICEAdValorem += iceAdValoremItem;
                totICEGlobal += iceGlobalAsignado;
                totICE += iceTotalItem;
                totISD += isdAsignado;
                totGastosLocales += gastoLocalAsignado;
                totCostoBodega += costoBodegaTotal;
                totIVAImportacion += ivaImportacionItem;
                totOtrosRubrosGravados += otrosRubrosGravadosItem;

                // Las ventas proyectadas se calculan sobre las unidades que efectivamente se pueden vender
                totVentaMayorista += precioMayorista * unidadesVendibles;
                totVentaEventual += precioEventual * unidadesVendibles;
                totVentaPVP += precioPVP * unidadesVendibles;

                return {
                    ...item,
                    cantidad: cant,
                    mermaPct,
                    unidadesVendibles,
                    pesoTotal,
                    cbmTotal,
                    fobTotal,
                    fleteTotal,
                    seguroTotal,
                    fleteSeguroTotal,
                    cifTotal,
                    arancelEfectivoPct,
                    adValoremTotal,
                    fodinfaTotal,
                    baseExAduana,
                    iceBaseDeclarada,
                    baseICEAplicada,
                    modalidadICE,
                    iceEspecificoUSD: iceEspUSD,
                    factorICE,
                    icePorcentaje: icePct,
                    iceEspecificoItem,
                    iceAdValoremItem,
                    iceGlobalAsignado,
                    iceTotalItem,
                    otrosRubrosGravadosItem,
                    arancelesTotalesItem,
                    tarifaISD: pre.tarifaISDItem,
                    isdAsignado,
                    gastoLocalAsignado,
                    costoBodegaTotal,
                    costoUnitarioBodega,
                    ivaImportacionItem,
                    ivaImportacionRecuperable,
                    precioMayorista,
                    precioEventual,
                    precioPVP,
                    pvpConIVA
                };
            });

            const totArancelMasFodinfa = totAdValorem + totFODINFA;
            const totTributosMasICE = totArancelMasFodinfa + totICE;
            const utilidadMay = totVentaMayorista - totCostoBodega;
            const utilidadEve = totVentaEventual - totCostoBodega;
            const utilidadPVP = totVentaPVP - totCostoBodega;
            const factorImportacion = totFOB > 0 ? (totCostoBodega / totFOB) : 1;

            // LITERAL 4: CAPITAL, ROI Y PUNTO DE EQUILIBRIO
            // El capital requerido mide salida de caja, incluso si el IVA luego es recuperable.
            // Incluye seguro y otros rubros declarados, que antes quedaban fuera del KPI.
            const desembolsoExterior = totFOB + totFlete + totSeguro;
            const desembolsoAduanaLocal = totTributosMasICE + totGastosLocales + totIVAImportacion + totOtrosRubrosGravados;
            const desembolsoTotalCapital = desembolsoExterior + desembolsoAduanaLocal;

            const roiPVP = desembolsoTotalCapital > 0 ? ((utilidadPVP / desembolsoTotalCapital) * 100) : 0;
            const roiMayorista = desembolsoTotalCapital > 0 ? ((utilidadMay / desembolsoTotalCapital) * 100) : 0;
            const roiEventual = desembolsoTotalCapital > 0 ? ((utilidadEve / desembolsoTotalCapital) * 100) : 0;

            const puntoEquilibrioPct = totVentaPVP > 0 ? ((totCostoBodega / totVentaPVP) * 100) : 0;
            const puntoEquilibrioUnidades = Math.ceil((puntoEquilibrioPct / 100) * totUnidadesVendibles);

            document.getElementById('kpiDesembolsoTotalRequerido').textContent = fmtUSD(desembolsoTotalCapital);
            document.getElementById('kpiSubDesembolsoExterior').textContent = fmtUSD(desembolsoExterior);
            document.getElementById('kpiSubDesembolsoLocal').textContent = fmtUSD(desembolsoAduanaLocal);

            document.getElementById('kpiROIPVP').textContent = roiPVP.toFixed(1) + '%';
            document.getElementById('kpiROIMayorista').textContent = roiMayorista.toFixed(1) + '%';
            document.getElementById('kpiROIEventual').textContent = roiEventual.toFixed(1) + '%';

            document.getElementById('kpiPuntoEquilibrioPct').textContent = puntoEquilibrioPct.toFixed(1) + '%';
            document.getElementById('kpiPuntoEquilibrioUnidades').textContent = `${puntoEquilibrioUnidades.toLocaleString()} u`;
            document.getElementById('kpiPuntoEquilibrioUSD').textContent = fmtUSD(totCostoBodega);

            // KPIS PRINCIPALES
            document.getElementById('kpiTotalCIF').textContent = fmtUSD(totCIF);
            document.getElementById('kpiSubCIF').textContent = `FOB: ${fmtUSD(totFOB)} | Seg: ${fmtUSD(totSeguro)}`;

            document.getElementById('kpiArancelFodinfa').textContent = fmtUSD(totArancelMasFodinfa);
            document.getElementById('kpiSubArancelFodinfa').textContent = `AdV: ${fmtUSD(totAdValorem)} | FOD: ${fmtUSD(totFODINFA)}`;

            document.getElementById('kpiTotalICE').textContent = fmtUSD(totICE);
            document.getElementById('kpiSubICE').textContent = `Esp: ${fmtUSD(totICEEspecifico)} | AdV: ${fmtUSD(totICEAdValorem)} | Global: ${fmtUSD(totICEGlobal)}`;

            document.getElementById('kpiTotalLocales').textContent = fmtUSD(totGastosLocales);
            document.getElementById('kpiSubLocales').textContent = `ISD: ${fmtUSD(totISD)} | Loc: ${fmtUSD(totGastosLocales - totISD)}`;

            document.getElementById('kpiIVAImportacion').textContent = fmtUSD(totIVAImportacion);

            document.getElementById('kpiCostoBodega').textContent = fmtUSD(totCostoBodega);
            document.getElementById('kpiFactorImportacion').textContent = `Factor s/FOB: ${factorImportacion.toFixed(2)}x`;

            document.getElementById('kpiUtilidadBrutaPVP').textContent = fmtUSD(utilidadPVP);
            document.getElementById('kpiSubUtilidadPVP').textContent = `Venta PVP: ${fmtUSD(totVentaPVP)}`;

            document.getElementById('mobileBarCostoBodega').textContent = fmtUSD(totCostoBodega);
            document.getElementById('mobileBarAranceles').textContent = fmtUSD(totTributosMasICE);
            document.getElementById('mobileBarUtilidadPVP').textContent = fmtUSD(utilidadPVP);

            document.getElementById('utilidadTotalMayorista').textContent = fmtUSD(utilidadMay);
            document.getElementById('utilidadTotalEventual').textContent = fmtUSD(utilidadEve);
            document.getElementById('utilidadTotalPVP').textContent = fmtUSD(utilidadPVP);

            renderizarTabla({ totUnidades, totUnidadesVendibles, totFOB, totCIF, totTributosMasICE, totGastosLocales, totCostoBodega });
            renderizarTarjetasMoviles();
            actualizarGraficos(totFOB, totFleteSeguro, totArancelMasFodinfa, totICE, totGastosLocales, totCostoBodega, totOtrosRubrosGravados, ivaImportacionRecuperable ? 0 : totIVAImportacion);
        }

        function actualizarGraficos(totFOB, totFleteSeguro, totArancelMasFodinfa, totICE, totGastosLocales, totCostoBodega, totOtrosRubrosGravados = 0, ivaNoRecuperable = 0) {
            const ctxCostos = document.getElementById('chartComposicionCostos');
            const ctxPrecios = document.getElementById('chartComparativaPrecios');
            if (!ctxCostos || !ctxPrecios) return;

            const labelsCostos = ['FOB Mercadería', 'Flete + Seguro', 'Arancel + FODINFA', 'ICE (SRI)', 'ISD + Gastos Locales', 'Otros rubros + IVA no recuperable'];
            const dataCostos = [totFOB, totFleteSeguro, totArancelMasFodinfa, totICE, totGastosLocales, totOtrosRubrosGravados + ivaNoRecuperable];
            const coloresCostos = ['#111111', '#2563EB', '#D4AF37', '#FF6B00', '#10B981', '#8B5CF6'];

            if (chartCostosInstance) chartCostosInstance.destroy();
            chartCostosInstance = new Chart(ctxCostos, {
                type: 'doughnut',
                data: {
                    labels: labelsCostos,
                    datasets: [{
                        data: dataCostos,
                        backgroundColor: coloresCostos,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const v = ctx.raw || 0;
                                    const pct = totCostoBodega > 0 ? ((v / totCostoBodega) * 100).toFixed(1) : '0.0';
                                    return ` ${ctx.label}: ${fmtUSD(v)} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });

            const leyendaEl = document.getElementById('leyendaComposicion');
            if (leyendaEl) {
                leyendaEl.innerHTML = labelsCostos.map((lbl, i) => {
                    const val = dataCostos[i];
                    const pct = totCostoBodega > 0 ? ((val / totCostoBodega) * 100).toFixed(1) : '0.0';
                    return `
                        <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded-lg border border-gray-200">
                            <span class="flex items-center gap-1.5 text-[11px] font-semibold text-gray-700 truncate">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color:${coloresCostos[i]}"></span>
                                <span class="truncate">${lbl}</span>
                            </span>
                            <span class="text-[11px] font-extrabold text-[#111111] ml-1 shrink-0">${fmtUSD(val)} (${pct}%)</span>
                        </div>
                    `;
                }).join('');
            }

            const labelsSKU = itemsCalculados.map(it => it.codigo);
            const dataCostoU = itemsCalculados.map(it => Number(it.costoUnitarioBodega.toFixed(2)));
            const dataMayorista = itemsCalculados.map(it => Number(it.precioMayorista.toFixed(2)));
            const dataEventual = itemsCalculados.map(it => Number(it.precioEventual.toFixed(2)));
            const dataPVP = itemsCalculados.map(it => Number(it.precioPVP.toFixed(2)));

            if (chartPreciosInstance) chartPreciosInstance.destroy();
            chartPreciosInstance = new Chart(ctxPrecios, {
                type: 'bar',
                data: {
                    labels: labelsSKU,
                    datasets: [
                        { label: 'Costo Unit. Real', data: dataCostoU, backgroundColor: '#111111', borderRadius: 4 },
                        { label: 'P. Mayorista', data: dataMayorista, backgroundColor: '#9CA3AF', borderRadius: 4 },
                        { label: 'P. Eventual', data: dataEventual, backgroundColor: '#FF6B00', borderRadius: 4 },
                        { label: 'PVP Sin IVA', data: dataPVP, backgroundColor: '#D4AF37', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } } },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) { return ` ${ctx.dataset.label}: ${fmtUSD(ctx.raw)}`; }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: function(val) { return '$' + val; }, font: { size: 10 } }
                        },
                        x: { ticks: { font: { size: 10, weight: 'bold' } } }
                    }
                }
            });
        }

        function renderizarTabla(totales) {
            const tbody = document.getElementById('tablaLiquidacionBody');
            const tfoot = document.getElementById('tablaLiquidacionFoot');
            if (!tbody || !tfoot) return;

            const filtro = (document.getElementById('inputBuscarProducto')?.value || '').trim().toLowerCase();

            tbody.innerHTML = itemsCalculados.map((it, idx) => {
                const textoBusqueda = `${it.codigo} ${it.producto} ${it.modalidadICE} ${it.tarifaISD}%`.toLowerCase();
                if (filtro && !textoBusqueda.includes(filtro)) return '';

                return `
                    <tr class="hover:bg-amber-50/40 transition">
                        <td class="py-2.5 px-3 font-extrabold text-[#111111] sticky-col bg-white border-r border-gray-100">${proEsc(it.codigo)}</td>
                        <td class="py-2.5 px-3 font-semibold text-gray-800">
                            <div>${proEsc(it.producto)}</div>
                            <div class="text-[10px] text-gray-400">Vendibles: ${Number(it.unidadesVendibles.toFixed(3))} u | Vol: ${(it.cantidad * (it.cbmUnitario || 0.01)).toFixed(2)} m³</div>
                        </td>
                        <td class="py-2 px-2 text-center">
                            <input type="number" min="1" step="1" value="${it.cantidad}" onchange="editarCampoItem(${idx}, 'cantidad', this.value)" class="w-16 text-center bg-gray-50 border border-gray-300 rounded-lg py-1 px-1 font-bold text-[#111111] focus:border-[#D4AF37] focus:outline-none">
                        </td>
                        <!-- LITERAL 1: Input de Merma -->
                        <td class="py-2 px-2 text-center bg-red-50/40">
                            <input type="number" min="0" max="90" step="0.5" value="${it.mermaPct}" onchange="editarCampoItem(${idx}, 'mermaPct', this.value)" class="w-14 text-center bg-white border border-red-300 rounded-lg py-1 px-1 font-bold text-red-700 focus:outline-none" title="% rotura esperada">
                        </td>
                        <td class="py-2 px-2.5 text-right col-costos">
                            <input type="number" min="0" step="0.0001" value="${Number(it.fobUnitario).toFixed(4)}" onchange="editarCampoItem(${idx}, 'fobUnitario', this.value)" class="w-20 text-right bg-gray-50 border border-gray-300 rounded-lg py-1 px-1.5 font-semibold text-[#111111] focus:border-[#D4AF37] focus:outline-none">
                        </td>
                        <td class="py-2.5 px-2.5 text-right font-semibold text-gray-700 col-costos">${fmtUSD(it.fobTotal)}</td>
                        <td class="py-2.5 px-2.5 text-right font-bold text-[#111111] bg-gray-50 col-costos">${fmtUSD(it.cifTotal)}</td>
                        <td class="py-2 px-2 text-center col-costos">
                            ${mpiControlArancel(idx,it)}
                        </td>
                        <td class="py-2 px-2 text-center col-costos bg-red-50/40">
                            <select onchange="editarCampoItem(${idx}, 'tarifaISD', this.value)" class="bg-white border border-red-300 rounded-lg py-1 px-1 text-[11px] font-extrabold text-red-700 focus:outline-none">
                                <option value="5" ${Number(it.tarifaISD) === 5 ? 'selected' : ''}>5%</option>
                                <option value="2.5" ${Number(it.tarifaISD) === 2.5 ? 'selected' : ''}>2.5%</option>
                                <option value="0" ${Number(it.tarifaISD) === 0 ? 'selected' : ''}>0%</option>
                            </select>
                        </td>
                        <td class="py-2 px-2 text-center col-costos bg-orange-50/40">
                            <div class="flex items-center justify-center gap-1">
                                <select onchange="cambiarModalidadICEItem(${idx}, this.value)" class="bg-white border border-orange-300 rounded-lg py-1 px-1.5 text-[10px] font-extrabold text-[#111111] focus:outline-none">
                                    <option value="NINGUNO" ${it.modalidadICE === 'NINGUNO' ? 'selected' : ''}>SIN ICE</option>
                                    <option value="ESPECIFICO" ${it.modalidadICE === 'ESPECIFICO' ? 'selected' : ''}>1. ESP</option>
                                    <option value="AD_VALOREM" ${it.modalidadICE === 'AD_VALOREM' ? 'selected' : ''}>2. ADV</option>
                                    <option value="MIXTO" ${it.modalidadICE === 'MIXTO' ? 'selected' : ''}>3. MIX</option>
                                </select>
                                <button type="button" onclick="abrirModalCatalogoICE(${idx})" class="p-1 rounded bg-[#111111] text-[#D4AF37] hover:bg-gray-800" title="Configurar ICE">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="py-2.5 px-2.5 text-right font-semibold text-gray-700 col-costos">${fmtUSD(it.arancelesTotalesItem)}</td>
                        <td class="py-2.5 px-2.5 text-right font-semibold text-gray-700 col-costos">${fmtUSD(it.gastoLocalAsignado)}</td>
                        <td class="py-2.5 px-3 text-right font-extrabold text-[#111111] bg-gray-100">${fmtUSD(it.costoBodegaTotal)}</td>
                        <td class="py-2.5 px-3 text-right font-black text-[#111111] bg-[#D4AF37]/20" title="Costo unitario por unidad vendible">${fmtUSD(it.costoUnitarioBodega)}</td>
                        <td class="py-2.5 px-3 text-right font-bold text-gray-800 col-precios">${fmtUSD(it.precioMayorista)}</td>
                        <td class="py-2.5 px-3 text-right font-bold text-[#FF6B00] col-precios">${fmtUSD(it.precioEventual)}</td>
                        <td class="py-2.5 px-3 text-right font-extrabold text-[#111111] bg-gray-50 col-precios">${fmtUSD(it.precioPVP)}</td>
                        <td class="py-2.5 px-3 text-right font-black text-emerald-800 bg-emerald-50 col-precios">${fmtUSD(it.pvpConIVA)}</td>
                        <td class="py-2.5 px-2 text-center">
                            <button type="button" onclick="eliminarItem(${idx})" class="text-gray-400 hover:text-red-600 p-1 transition cursor-pointer" title="Eliminar ítem">
                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            tfoot.innerHTML = `
                <tr>
                    <td class="py-3 px-3 sticky-col bg-gray-100">TOTALES</td>
                    <td class="py-3 px-3">${itemsCalculados.length} Partidas</td>
                    <td class="py-3 px-2 text-center">${totales.totUnidades.toLocaleString()} u</td>
                    <td class="py-3 px-2 text-center text-red-700 font-bold">${Math.round(totales.totUnidadesVendibles).toLocaleString()} vend.</td>
                    <td class="py-3 px-2.5 text-right col-costos">-</td>
                    <td class="py-3 px-2.5 text-right col-costos">${fmtUSD(totales.totFOB)}</td>
                    <td class="py-3 px-2.5 text-right col-costos">${fmtUSD(totales.totCIF)}</td>
                    <td class="py-3 px-2 text-center col-costos">-</td>
                    <td class="py-3 px-2 text-center col-costos">-</td>
                    <td class="py-3 px-2 text-center col-costos">-</td>
                    <td class="py-3 px-2.5 text-right col-costos">${fmtUSD(totales.totTributosMasICE)}</td>
                    <td class="py-3 px-2.5 text-right col-costos">${fmtUSD(totales.totGastosLocales)}</td>
                    <td class="py-3 px-3 text-right bg-[#111111] text-[#D4AF37]">${fmtUSD(totales.totCostoBodega)}</td>
                    <td class="py-3 px-3 text-right bg-[#D4AF37]/30">-</td>
                    <td class="py-3 px-3 text-right col-precios">-</td>
                    <td class="py-3 px-3 text-right col-precios">-</td>
                    <td class="py-3 px-3 text-right col-precios">-</td>
                    <td class="py-3 px-3 text-right col-precios">-</td>
                    <td class="py-3 px-2"></td>
                </tr>
            `;

            aplicarVisibilidadColumnas();
        }

        function renderizarTarjetasMoviles() {
            const cont = document.getElementById('contenedorVistaTarjetas');
            if (!cont) return;

            const filtro = (document.getElementById('inputBuscarProducto')?.value || '').trim().toLowerCase();

            cont.innerHTML = itemsCalculados.map((it, idx) => {
                const textoBusqueda = `${it.codigo} ${it.producto} ${it.modalidadICE} ${it.tarifaISD}%`.toLowerCase();
                if (filtro && !textoBusqueda.includes(filtro)) return '';

                return `
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                        <div class="bg-[#111111] px-4 py-3 flex items-center justify-between gap-2 border-b-2 border-[#D4AF37]">
                            <div class="truncate">
                                <span class="text-[10px] font-extrabold uppercase bg-[#D4AF37] text-[#111111] px-2 py-0.5 rounded">${proEsc(it.codigo)}</span>
                                <h4 class="text-white font-bold text-sm truncate mt-1">${proEsc(it.producto)}</h4>
                            </div>
                            <button type="button" onclick="eliminarItem(${idx})" class="text-gray-400 hover:text-red-400 p-1.5 shrink-0 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 space-y-3.5 text-xs">
                            <div class="grid grid-cols-4 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Cant.</label>
                                    <input type="number" inputmode="numeric" min="1" value="${it.cantidad}" onchange="editarCampoItem(${idx}, 'cantidad', this.value)" class="w-full min-h-[42px] bg-gray-50 border border-gray-300 rounded-xl px-2 text-center font-bold text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-red-700 mb-1">% Merma</label>
                                    <input type="number" inputmode="decimal" step="0.5" value="${it.mermaPct}" onchange="editarCampoItem(${idx}, 'mermaPct', this.value)" class="w-full min-h-[42px] bg-red-50/50 border border-red-300 rounded-xl px-1 text-center font-bold text-xs text-red-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">FOB ($)</label>
                                    <input type="number" inputmode="decimal" step="0.0001" value="${Number(it.fobUnitario).toFixed(4)}" onchange="editarCampoItem(${idx}, 'fobUnitario', this.value)" class="w-full min-h-[42px] bg-gray-50 border border-gray-300 rounded-xl px-2 text-center font-bold text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">% Arancel</label>
                                    ${mpiControlArancel(idx,it)}
                                </div>
                            </div>

                            <div class="bg-orange-50/70 border border-orange-200 rounded-xl p-2.5 flex items-center justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase text-[#FF6B00] block">ICE (${proEsc(it.modalidadICE)}): ${fmtUSD(it.iceTotalItem)}</span>
                                    <span class="text-[11px] text-gray-600">Esp: $${Number(it.iceEspecificoUSD).toFixed(2)} | AdV: ${it.icePorcentaje}%</span>
                                </div>
                                <button type="button" onclick="abrirModalCatalogoICE(${idx})" class="min-h-[38px] bg-[#111111] text-[#D4AF37] font-bold text-[11px] px-3 py-1.5 rounded-lg shrink-0 cursor-pointer">
                                    ICE
                                </button>
                            </div>

                            <div class="bg-[#111111] text-white p-3 rounded-xl flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase text-gray-400 font-bold block">Total Bodega</span>
                                    <span class="text-sm font-extrabold text-white">${fmtUSD(it.costoBodegaTotal)}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] uppercase text-[#D4AF37] font-bold block">Unit. Real Vendible</span>
                                    <span class="text-base font-black text-[#D4AF37]">${fmtUSD(it.costoUnitarioBodega)}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div class="bg-gray-50 p-2 rounded-xl border border-gray-200">
                                    <span class="text-[10px] text-gray-500 font-bold block">Mayorista</span>
                                    <span class="font-extrabold text-gray-800 text-xs">${fmtUSD(it.precioMayorista)}</span>
                                </div>
                                <div class="bg-orange-50/50 p-2 rounded-xl border border-orange-200">
                                    <span class="text-[10px] text-[#FF6B00] font-bold block">Eventual</span>
                                    <span class="font-extrabold text-[#FF6B00] text-xs">${fmtUSD(it.precioEventual)}</span>
                                </div>
                                <div class="bg-emerald-50 p-2 rounded-xl border border-emerald-200">
                                    <span class="text-[10px] text-emerald-800 font-bold block">PVP + IVA</span>
                                    <span class="font-black text-emerald-800 text-xs">${fmtUSD(it.pvpConIVA)}</span>
                                </div>
                            </div>

                            <button type="button" onclick="toggleDetalleTarjeta(${idx})" class="w-full py-1.5 text-[11px] font-bold text-gray-600 hover:text-[#111111] bg-gray-100 rounded-lg transition cursor-pointer">
                                Ver / Ocultar Desglose SENAE
                            </button>
                            <div id="detalleTarjeta_${idx}" class="hidden bg-gray-50 p-3 rounded-xl border border-gray-200 space-y-1 text-[11px] text-gray-600">
                                <div class="flex justify-between"><span>Unidades Vendibles:</span><strong class="text-emerald-700">${Number(it.unidadesVendibles.toFixed(3))} u</strong></div>
                                <div class="flex justify-between"><span>CBM Partida:</span><strong class="text-blue-700">${(it.cantidad * (it.cbmUnitario || 0.01)).toFixed(2)} m³</strong></div>
                                <div class="flex justify-between"><span>CIF Total:</span><strong class="text-[#111111]">${fmtUSD(it.cifTotal)}</strong></div>
                                <div class="flex justify-between"><span>Seguro:</span><strong>${fmtUSD(it.seguroTotal)}</strong></div>
                                <div class="flex justify-between"><span>Ad-Valorem + FODINFA:</span><strong>${fmtUSD(it.adValoremTotal + it.fodinfaTotal)}</strong></div>
                                <div class="flex justify-between"><span>ISD (${it.tarifaISD}%):</span><strong class="text-red-700">${fmtUSD(it.isdAsignado)}</strong></div>
                                <div class="flex justify-between"><span>8 Gastos Locales:</span><strong>${fmtUSD(it.gastoLocalAsignado - it.isdAsignado)}</strong></div>
                                <div class="flex justify-between"><span>IVA de importación:</span><strong class="text-blue-700">${fmtUSD(it.ivaImportacionItem)}</strong></div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function editarCampoItem(index, campo, valor) {
            if (!itemsImportacion[index]) return;
            const num = Number(valor);
            const limites = {cantidad:[1,Infinity],fobUnitario:[Number.MIN_VALUE,Infinity],mermaPct:[0,90],arancelPorcentaje:[0,100],tarifaISD:[0,100],icePorcentaje:[0,500]};
            const [min,max] = limites[campo] || [0,Infinity];
            if (String(valor).trim()==='' || !Number.isFinite(num) || num<min || num>max) {
                document.querySelectorAll('input[onchange],select[onchange]').forEach(el=>{
                    if ((el.getAttribute('onchange')||'').includes(`editarCampoItem(${index}, '${campo}',`)) el.value=itemsImportacion[index][campo];
                });
                mostrarNotificacion('Dato no válido','Se conservó el valor anterior. Revisa el rango permitido para este campo.','error');
                return;
            }
            if(campo==='arancelPorcentaje' && itemsImportacion[index].usarPreferenciaOrigen && !mpiTarifaPreferencialValida({...itemsImportacion[index],arancelPorcentaje:num})){mostrarNotificacion('Arancel no válido','La tarifa general no puede ser menor que la preferencial activa.','error');recalcularTodo();return;}
            itemsImportacion[index][campo] = num;

            if (campo === 'tarifaISD') {
                tarifaISDActiva = 'POR_PARTIDA';
                document.getElementById('modoCalculoISD').value = 'AUTO';
            }
            if (campo === 'iceEspecificoUSD' || campo === 'icePorcentaje') {
                const it = itemsImportacion[index];
                if ((it.iceEspecificoUSD || 0) > 0 && (it.icePorcentaje || 0) > 0) it.modalidadICE = 'MIXTO';
                else if ((it.iceEspecificoUSD || 0) > 0) it.modalidadICE = 'ESPECIFICO';
                else if ((it.icePorcentaje || 0) > 0) it.modalidadICE = 'AD_VALOREM';
                else it.modalidadICE = 'NINGUNO';
            }
            recalcularTodo();
        }

        function cambiarModalidadICEItem(index, nuevaMod) {
            if (!itemsImportacion[index]) return;
            const it = itemsImportacion[index];
            it.modalidadICE = nuevaMod;
            if (nuevaMod === 'NINGUNO') {
                it.iceEspecificoUSD = 0;
                it.icePorcentaje = 0;
            } else if (nuevaMod === 'ESPECIFICO' && (!it.iceEspecificoUSD || it.iceEspecificoUSD <= 0)) {
                it.iceEspecificoUSD = 0.18;
                it.icePorcentaje = 0;
            } else if (nuevaMod === 'AD_VALOREM' && (!it.icePorcentaje || it.icePorcentaje <= 0)) {
                it.icePorcentaje = 20;
                it.iceEspecificoUSD = 0;
            } else if (nuevaMod === 'MIXTO') {
                if (!it.iceEspecificoUSD || it.iceEspecificoUSD <= 0) it.iceEspecificoUSD = 10.41;
                if (!it.icePorcentaje || it.icePorcentaje <= 0) it.icePorcentaje = 75;
            }
            recalcularTodo();
        }

        function eliminarItem(index) {
            if (itemsImportacion.length <= 1) {
                mostrarNotificacion('Aviso', 'Debes mantener al menos 1 producto en el cálculo.', 'error');
                return;
            }
            const eliminado = itemsImportacion.splice(index, 1)[0];
            recalcularTodo();
            mostrarNotificacion('Ítem Eliminado', `Se retiró "${eliminado.producto}".`);
        }

        function filtrarTabla() {
            recalcularTodo();
        }

        function cambiarVistaTabla(vista) {
            vistaActual = vista;
            const botones = {
                COMPLETA: document.getElementById('btnVistaCompleta'),
                COSTOS: document.getElementById('btnVistaCostos'),
                PRECIOS: document.getElementById('btnVistaPrecios')
            };
            Object.keys(botones).forEach(k => {
                if (!botones[k]) return;
                if (k === vista) {
                    botones[k].className = 'min-h-[36px] text-xs font-bold px-3 py-1.5 rounded-lg bg-[#111111] text-[#D4AF37] transition cursor-pointer';
                } else {
                    botones[k].className = 'min-h-[36px] text-xs font-semibold px-3 py-1.5 rounded-lg bg-white text-gray-700 border border-gray-300 hover:border-[#D4AF37] transition cursor-pointer';
                }
            });
            aplicarVisibilidadColumnas();
        }

        function aplicarVisibilidadColumnas() {
            const colsCostos = document.querySelectorAll('.col-costos');
            const colsPrecios = document.querySelectorAll('.col-precios');

            colsCostos.forEach(el => {
                el.style.display = (vistaActual === 'PRECIOS') ? 'none' : '';
            });
            colsPrecios.forEach(el => {
                el.style.display = (vistaActual === 'COSTOS') ? 'none' : '';
            });
        }

        function abrirModalNuevoItem() {
            document.getElementById('modalNuevoItem').classList.remove('hidden');
        }

        function cerrarModalNuevoItem() {
            document.getElementById('modalNuevoItem').classList.add('hidden');
        }

        function guardarNuevoItem(e) {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            const leer = (id) => {
                const el = document.getElementById(id);
                if (!el) return NaN;
                const raw = String(el.value ?? '').trim();
                return raw === '' ? NaN : Number(raw);
            };
            const codigoIngresado = document.getElementById('nuevoCodigo').value.trim();
            const producto = document.getElementById('nuevoProducto').value.trim();
            const cantidad = leer('nuevaCantidad');
            const fob = leer('nuevoFOB');
            const flete = leer('nuevoFlete');
            const seguro = leer('nuevoSeguro');
            const arancel = leer('nuevoArancel');
            const preferencia={arancelPorcentaje:arancel,arancelPreferencialPorcentaje:document.getElementById('nuevoArancelPreferencial').value.trim()===''?null:leer('nuevoArancelPreferencial'),usarPreferenciaOrigen:document.getElementById('nuevoUsarPreferenciaOrigen').checked};
            const merma = leer('nuevoMermaPct');
            const tarifaISD = leer('nuevoTarifaISD');
            const factorICE = leer('nuevoFactorICE');
            const iceEspecifico = leer('nuevoICEEspecifico');
            const icePct = leer('nuevoICE');
            const peso = leer('nuevoPeso');
            const cbm = leer('nuevoCBMUnitario');
            const errores = [];
            if(preferencia.usarPreferenciaOrigen && !mpiTarifaPreferencialValida(preferencia))errores.push('Ingresa una tarifa preferencial entre cero y el arancel general.');
            if(preferencia.arancelPreferencialPorcentaje!==null && !mpiTarifaPreferencialValida(preferencia))errores.push('Tarifa preferencial inválida.');
            if (!producto) errores.push('Escribe la descripción del producto.');
            if (!Number.isFinite(cantidad) || cantidad <= 0) errores.push('La cantidad debe ser mayor que cero.');
            if (!Number.isFinite(fob) || fob <= 0) errores.push('El FOB unitario debe ser mayor que cero.');
            if (!Number.isFinite(flete) || flete < 0) errores.push('El flete unitario debe ser cero o mayor.');
            if (!Number.isFinite(seguro) || seguro < 0) errores.push('El seguro unitario debe ser cero o mayor.');
            if (!Number.isFinite(arancel) || arancel < 0 || arancel > 100) errores.push('El arancel debe estar entre 0 y 100%.');
            if (!Number.isFinite(merma) || merma < 0 || merma > 90) errores.push('La merma debe estar entre 0 y 90%.');
            if (![0, 2.5, 5].some(v => Number.isFinite(tarifaISD) && Math.abs(v - tarifaISD) < 1e-9)) errores.push('Selecciona una tarifa ISD disponible y confirma su vigencia para la operación.');
            if (!Number.isFinite(factorICE) || factorICE < 0) errores.push('El factor ICE debe ser cero o mayor.');
            if (!Number.isFinite(iceEspecifico) || iceEspecifico < 0) errores.push('El ICE específico debe ser cero o mayor.');
            if (!Number.isFinite(icePct) || icePct < 0 || icePct > 500) errores.push('El ICE ad valorem debe estar entre 0 y 500%.');
            if (!Number.isFinite(peso) || peso < 0) errores.push('El peso debe ser cero o mayor.');
            if (!Number.isFinite(cbm) || cbm < 0) errores.push('El volumen CBM debe ser cero o mayor.');
            const codigoExistente = codigoIngresado && itemsImportacion.some(it => String(it.codigo || '').trim().toLocaleLowerCase() === codigoIngresado.toLocaleLowerCase());
            if (codigoExistente) errores.push('Ya existe una partida con ese código SKU. Usa un código diferente.');
            if (errores.length) {
                mostrarNotificacion('Revisa los datos del producto', errores.slice(0, 4).join(' '), 'error');
                const foco = !producto ? 'nuevoProducto' : (!Number.isFinite(cantidad) || cantidad <= 0 ? 'nuevaCantidad' : (!Number.isFinite(fob) || fob <= 0 ? 'nuevoFOB' : null));
                if (foco) document.getElementById(foco)?.focus();
                return false;
            }

            const nuevo = {
                codigo: codigoIngresado || ('EC-SKU-' + Date.now()),
                producto,
                cantidad,
                mermaPct: merma,
                fobUnitario: fob,
                fleteUnitario: flete,
                seguroUnitario: seguro,
                arancelPorcentaje: arancel,
                arancelPreferencialPorcentaje: preferencia.arancelPreferencialPorcentaje,
                usarPreferenciaOrigen: preferencia.usarPreferenciaOrigen,
                tarifaISD,
                modalidadICE: document.getElementById('nuevoModalidadICE').value,
                factorICE,
                iceEspecificoUSD: iceEspecifico,
                icePorcentaje: icePct,
                pesoUnitarioKg: peso,
                cbmUnitario: cbm
            };
            itemsImportacion.push(nuevo);
            document.getElementById('nuevoArancelPreferencial').value='';document.getElementById('nuevoUsarPreferenciaOrigen').checked=false;
            cerrarModalNuevoItem();
            document.getElementById('nuevoCodigo').value = '';
            document.getElementById('nuevoProducto').value = '';
            recalcularTodo();
            mostrarNotificacion('Producto agregado', `“${nuevo.producto}” se añadió y se recalcularon los costos.`);
            return true;
        }

        function descargarPlantilla() {
            try {
                const datosPlantilla = EMBARQUE_DEMO.map(it => ({
                    'Codigo_SKU': it.codigo,
                    'Producto_Descripcion': it.producto,
                    'Cantidad': it.cantidad,
                    'Merma_Rotura_Pct': it.mermaPct,
                    'FOB_Unitario_USD': it.fobUnitario,
                    'Flete_Unitario_USD': it.fleteUnitario,
                    'Seguro_Unitario_USD': it.seguroUnitario,
                    'Arancel_AdValorem_Pct': it.arancelPorcentaje,
                    'Arancel_Preferencial_Pct': it.arancelPreferencialPorcentaje ?? '',
                    'Usar_Certificado_Origen': it.usarPreferenciaOrigen===true?'SI':'NO',
                    'Tarifa_ISD_Pct': it.tarifaISD,
                    'Modalidad_ICE': it.modalidadICE,
                    'ICE_Especifico_USD': it.iceEspecificoUSD,
                    'Factor_ICE_Unidad': it.factorICE,
                    'ICE_AdValorem_Pct': it.icePorcentaje,
                    'Peso_Unitario_Kg': it.pesoUnitarioKg,
                    'Volumen_CBM_Unitario': it.cbmUnitario
                }));

                const ws = XLSX.utils.json_to_sheet(datosPlantilla);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Plantilla_Importacion_EC');
                XLSX.writeFile(wb, 'Plantilla_Costos_Importacion_Ecuador.xlsx');
                mostrarNotificacion('Descarga Exitosa', 'Plantilla Excel generada.');
            } catch (err) {
                mostrarNotificacion('Error', 'No se pudo generar la plantilla Excel.', 'error');
            }
        }

        function verificarArchivo(input) {
            const file = input.files && input.files[0];
            if (!file) return;
            if(file.size>10*1024*1024){mostrarNotificacion('Archivo demasiado grande','El archivo Excel debe pesar como máximo 10 MB.','error');return;}
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(firstSheet, { defval: '' }).map(row=>{for(const key of Object.keys(row))if(typeof row[key]==='string'&&row[key].trim()==='')delete row[key];return row;});
                    if(rows.length>5000){mostrarNotificacion('Archivo demasiado grande','Máximo 5000 partidas por importación.','error');return;}

                    if (!rows || rows.length === 0) {
                        mostrarNotificacion('Archivo vacío', 'No se encontraron filas con datos.', 'error');
                        return;
                    }

                    const filasInvalidas = rows.map((r, i) => {
                        const cantidad = Number(r.Cantidad ?? r.Unidades ?? NaN);
                        const fob = Number(r.FOB_Unitario_USD ?? r.FOB_Unitario ?? r.FOB ?? NaN);
                        const arancel = Number(r.Arancel_AdValorem_Pct ?? r.Arancel ?? 15);
                        const isd = Number(r.Tarifa_ISD_Pct ?? r.ISD_Pct ?? 5);
                        const merma = Number(r.Merma_Rotura_Pct ?? r.Merma ?? 0);
                        const flete = Number(r.Flete_Unitario_USD ?? r.Flete_Unitario ?? r.Flete ?? 0);
                        const seguro = Number(r.Seguro_Unitario_USD ?? r.Seguro_Unitario ?? r.Seguro ?? 0);
                        const iceEsp = Number(r.ICE_Especifico_USD ?? 0);
                        const icePct = Number(r.ICE_AdValorem_Pct ?? r.ICE_Pct ?? 0);
                        const factorICE = Number(r.Factor_ICE_Unidad ?? 1);
                        const peso = Number(r.Peso_Unitario_Kg ?? r.Peso ?? 0);
                        const cbm = Number(r.Volumen_CBM_Unitario ?? r.CBM ?? 0);
                        const producto = String(r.Producto_Descripcion || r.Producto || r.Descripcion || '').trim();
                        const erroresFila = [];
                        if(!['NINGUNO','ESPECIFICO','AD_VALOREM','MIXTO'].includes(String(r.Modalidad_ICE||'NINGUNO').trim().toUpperCase()))erroresFila.push('modalidad ICE no reconocida');
                        if (!producto) erroresFila.push('descripción del producto obligatoria');
                        if (!Number.isFinite(peso) || peso < 0) erroresFila.push('peso no negativo');
                        if (!Number.isFinite(cbm) || cbm < 0) erroresFila.push('volumen CBM no negativo');
                        if (!Number.isFinite(cantidad) || cantidad <= 0) erroresFila.push('cantidad > 0');
                        if (!Number.isFinite(fob) || fob <= 0) erroresFila.push('FOB unitario > 0');
                        if (!Number.isFinite(flete) || flete < 0) erroresFila.push('flete no negativo');
                        if (!Number.isFinite(seguro) || seguro < 0) erroresFila.push('seguro no negativo');
                        if (!Number.isFinite(arancel) || arancel < 0 || arancel > 100) erroresFila.push('arancel entre 0 y 100%');
                        if (!Number.isFinite(isd) || isd < 0 || isd > 100) erroresFila.push('ISD entre 0 y 100%');
                        if (!Number.isFinite(merma) || merma < 0 || merma > 90) erroresFila.push('merma entre 0 y 90%');
                        if (!Number.isFinite(iceEsp) || iceEsp < 0) erroresFila.push('ICE específico no negativo');
                        if (!Number.isFinite(icePct) || icePct < 0 || icePct > 500) erroresFila.push('ICE ad valorem entre 0 y 500%');
                        if (!Number.isFinite(factorICE) || factorICE < 0) erroresFila.push('factor ICE no negativo');
                        return erroresFila.length ? `Fila ${i + 2}: ${erroresFila.join(', ')}` : null;
                    }).filter(Boolean);
                    if (filasInvalidas.length) {
                        mostrarNotificacion('Excel no válido', `No se importó el archivo. ${filasInvalidas.slice(0, 3).join(' | ')}`, 'error');
                        return;
                    }
                    const codigosExcel = rows.map((r, i) => String(r.Codigo_SKU || r.Codigo || r.SKU || ('SKU-' + (i + 1))).trim().toLocaleLowerCase());
                    if (new Set(codigosExcel).size !== codigosExcel.length) {
                        mostrarNotificacion('Códigos duplicados', 'No se importó el archivo: hay códigos SKU repetidos. Corrige los códigos antes de volver a cargarlo.', 'error');
                        return;
                    }

                    for(const r of rows){const act=['SI','SÍ','TRUE','1'].includes(String(r.Usar_Certificado_Origen??'NO').trim().toUpperCase());if(act&&!mpiTarifaPreferencialValida({arancelPorcentaje:r.Arancel_AdValorem_Pct??r.Arancel??15,arancelPreferencialPorcentaje:r.Arancel_Preferencial_Pct})){mostrarNotificacion('Preferencia inválida','No se importó el archivo. Revisa la tarifa preferencial de los productos con certificado.','error');return;}}
                    itemsImportacion = rows.map((r, i) => ({
                        codigo: String(r.Codigo_SKU || r.Codigo || r.SKU || ('SKU-' + (i + 1))),
                        producto: String(r.Producto_Descripcion || r.Producto || r.Descripcion || ('Ítem ' + (i + 1))),
                        cantidad: Number(r.Cantidad ?? r.Unidades ?? 1),
                        mermaPct: Math.max(0, Number(r.Merma_Rotura_Pct || r.Merma || 0)),
                        fobUnitario: Math.max(0, Number(r.FOB_Unitario_USD || r.FOB_Unitario || r.FOB || 0)),
                        fleteUnitario: Math.max(0, Number(r.Flete_Unitario_USD || r.Flete_Unitario || r.Flete || 0)),
                        seguroUnitario: Math.max(0, Number(r.Seguro_Unitario_USD || r.Seguro_Unitario || r.Seguro || 0)),
                        arancelPorcentaje: Math.max(0, Number(r.Arancel_AdValorem_Pct ?? r.Arancel ?? 15)),
                        arancelPreferencialPorcentaje: r.Arancel_Preferencial_Pct===undefined||r.Arancel_Preferencial_Pct===null||String(r.Arancel_Preferencial_Pct).trim()===''?null:Number(r.Arancel_Preferencial_Pct),
                        usarPreferenciaOrigen: ['SI','SÍ','TRUE','1'].includes(String(r.Usar_Certificado_Origen??'NO').trim().toUpperCase()),
                        tarifaISD: Number(r.Tarifa_ISD_Pct ?? r.ISD_Pct ?? 5),
                        modalidadICE: String(r.Modalidad_ICE || 'NINGUNO').trim().toUpperCase(),
                        iceEspecificoUSD: Math.max(0, Number(r.ICE_Especifico_USD || 0)),
                        factorICE: Math.max(0, Number(r.Factor_ICE_Unidad ?? 1)),
                        icePorcentaje: Math.max(0, Number(r.ICE_AdValorem_Pct || r.ICE_Pct || 0)),
                        pesoUnitarioKg: Math.max(0, Number(r.Peso_Unitario_Kg ?? r.Peso ?? 0)),
                        cbmUnitario: Math.max(0, Number(r.Volumen_CBM_Unitario ?? r.CBM ?? 0))
                    }));

                    tarifaISDActiva = 'POR_PARTIDA';
                    document.getElementById('textoArchivoCargado').textContent = `Cargado: ${file.name} (${itemsImportacion.length} partidas)`;
                    document.getElementById('badgeArchivoCargado').textContent = `${itemsImportacion.length} Ítems`;
                    document.getElementById('estadoEmbarqueTexto').textContent = `${file.name} (${itemsImportacion.length} ítems)`;
                    recalcularTodo();
                    mostrarNotificacion('Carga Completa', `Se cargaron ${itemsImportacion.length} partidas.`);
                } catch (error) {
                    mostrarNotificacion('Error', 'Verifica el formato del archivo Excel.', 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        // LITERAL 3: EXPORTACIÓN DE LIQUIDACIÓN + ASIENTO CONTABLE EN PESTAÑAS SEPARADAS
        function exportarLiquidacionExcel() {
            if (!validarAntesDeExportar("liquidación Excel")) return;
            try {
                // PESTAÑA 1: Liquidación y Precios Comerciales
                const filasLiquidacion = itemsCalculados.map(it => ({
                    'Certificado de origen aplicado': it.usarPreferenciaOrigen===true?'SI':'NO',
                    'Arancel general (%)': it.arancelPorcentaje,
                    'Tarifa preferencial (%)': it.arancelPreferencialPorcentaje ?? '',
                    'Código SKU': it.codigo,
                    'Producto': it.producto,
                    'Cantidad Facturada': it.cantidad,
                    '% Merma': it.mermaPct,
                    'Unidades Vendibles': Number(it.unidadesVendibles.toFixed(3)),
                    'FOB Unitario ($)': Number(it.fobUnitario.toFixed(4)),
                    'FOB Total ($)': Number(it.fobTotal.toFixed(4)),
                    'Flete Total ($)': Number(it.fleteTotal.toFixed(4)),
                    'Seguro ($)': Number(it.seguroTotal.toFixed(4)),
                    'CIF Total ($)': Number(it.cifTotal.toFixed(4)),
                    '% Arancel': it.arancelEfectivoPct,
                    'Ad-Valorem ($)': Number(it.adValoremTotal.toFixed(4)),
                    'FODINFA 0.5% ($)': Number(it.fodinfaTotal.toFixed(4)),
                    'Modalidad ICE': it.modalidadICE,
                    'Base ICE aplicada (USD)': Number((it.baseICEAplicada || it.baseExAduana || 0).toFixed(4)),
                    'Base ICE declarada manualmente (USD)': Number((it.iceBaseDeclarada || 0).toFixed(4)),
                    'ICE Total ($)': Number(it.iceTotalItem.toFixed(4)),
                    'Otros rubros gravados declarados ($)': Number((it.otrosRubrosGravadosItem || 0).toFixed(4)),
                    'Tarifa ISD (%)': it.tarifaISD,
                    'ISD Asignado ($)': Number(it.isdAsignado.toFixed(4)),
                    'Gastos Locales ($)': Number((it.gastoLocalAsignado - it.isdAsignado).toFixed(4)),
                    'Costo Bodega Total ($)': Number(it.costoBodegaTotal.toFixed(4)),
                    'Costo Unit. Real Vendible ($)': Number(it.costoUnitarioBodega.toFixed(4)),
                    'IVA Importación ($)': Number(it.ivaImportacionItem.toFixed(4)),
                    'IVA Recuperable': it.ivaImportacionRecuperable ? 'Sí' : 'No',
                    'Mayorista ($)': Number(it.precioMayorista.toFixed(4)),
                    'Eventual ($)': Number(it.precioEventual.toFixed(4)),
                    'PVP Sin IVA ($)': Number(it.precioPVP.toFixed(4)),
                    'PVP + IVA ($)': Number(it.pvpConIVA.toFixed(4))
                }));

                // PESTAÑA 2: ASIENTO CONTABLE AUTOMÁTICO (Estructura Contable SENAE / SRI)
                let sumaFOB = 0, sumaFlete = 0, sumaSeguro = 0, sumaAdValorem = 0;
                let sumaFODINFA = 0, sumaICE = 0, sumaISD = 0, sumaIVA = 0, sumaLocales = 0, sumaBodega = 0, sumaOtrosRubrosGravados = 0;

                itemsCalculados.forEach(it => {
                    sumaFOB += it.fobTotal;
                    sumaFlete += it.fleteTotal;
                    sumaSeguro += it.seguroTotal;
                    sumaAdValorem += it.adValoremTotal;
                    sumaFODINFA += it.fodinfaTotal;
                    sumaICE += it.iceTotalItem;
                    sumaISD += it.isdAsignado;
                    sumaIVA += it.ivaImportacionItem;
                    sumaLocales += (it.gastoLocalAsignado - it.isdAsignado);
                    sumaBodega += it.costoBodegaTotal;
                    sumaOtrosRubrosGravados += Number(it.otrosRubrosGravadosItem || 0);
                });

                const filasAsientoContable = [
                    { 'Cuenta Contable': '1.1.05.01 INVENTARIO DE MERCADERÍAS EN BODEGA', 'Concepto / Glosa': 'Ingreso Landed definitivo a bodega (FOB+Flete+Seg+Tributos+Locales)', 'Debe (USD)': Number(sumaBodega.toFixed(4)), 'Haber (USD)': 0.00 },
                    { 'Cuenta Contable': '1.1.06.01 CRÉDITO TRIBUTARIO IVA EN IMPORTACIONES', 'Concepto / Glosa': 'IVA de importación a la tasa configurada (confirmar recuperabilidad)', 'Debe (USD)': Number(((itemsCalculados.every(it => it.ivaImportacionRecuperable !== false)) ? sumaIVA : 0).toFixed(4)), 'Haber (USD)': 0.00 },
                    { 'Cuenta Contable': '2.1.02.01 CUENTAS POR PAGAR PROVEEDORES EXTERIOR', 'Concepto / Glosa': 'Factura comercial proveedor extranjero (Valor FOB mercadería)', 'Debe (USD)': 0.00, 'Haber (USD)': Number(sumaFOB.toFixed(4)) },
                    { 'Cuenta Contable': '2.1.02.02 CUENTAS POR PAGAR NAVIERA / FLETE', 'Concepto / Glosa': 'Flete marítimo / aéreo internacional de importación', 'Debe (USD)': 0.00, 'Haber (USD)': Number(sumaFlete.toFixed(4)) },
                    { 'Cuenta Contable': '2.1.02.03 CUENTAS POR PAGAR SEGURO DE TRANSPORTE', 'Concepto / Glosa': 'Prima de seguro de transporte de carga', 'Debe (USD)': 0.00, 'Haber (USD)': Number(sumaSeguro.toFixed(4)) },
                    { 'Cuenta Contable': '2.1.03.01 ACREEDORES ADUANA SENAE (TRIBUTOS + IVA)', 'Concepto / Glosa': 'Liquidación aduanera (arancel + FODINFA + ICE + otros rubros gravados + IVA)', 'Debe (USD)': 0.00, 'Haber (USD)': Number((sumaAdValorem + sumaFODINFA + sumaICE + sumaOtrosRubrosGravados + sumaIVA).toFixed(4)) },
                    { 'Cuenta Contable': '2.1.03.02 SRI / RETENCIONES ISD POR PAGAR', 'Concepto / Glosa': 'Impuesto a la Salida de Divisas generado en transferencias al exterior', 'Debe (USD)': 0.00, 'Haber (USD)': Number(sumaISD.toFixed(4)) },
                    { 'Cuenta Contable': '2.1.02.04 PROVEEDORES LOCALES (AGENTE, PUERTO, FLETE T.)', 'Concepto / Glosa': 'Gastos logísticos, operativos, bodegaje y honorarios en Ecuador', 'Debe (USD)': 0.00, 'Haber (USD)': Number(sumaLocales.toFixed(4)) },
                    { 'Cuenta Contable': '--- TOTAL CUADRADO DEL ASIENTO ---', 'Concepto / Glosa': 'Verificación de Partida Doble', 'Debe (USD)': Number((sumaBodega + (itemsCalculados.every(it => it.ivaImportacionRecuperable !== false) ? sumaIVA : 0)).toFixed(4)), 'Haber (USD)': Number((sumaFOB + sumaFlete + sumaSeguro + sumaAdValorem + sumaFODINFA + sumaICE + sumaOtrosRubrosGravados + sumaIVA + sumaISD + sumaLocales).toFixed(4)) }
                ];

                const wb = XLSX.utils.book_new();
                
                const wsLiquidacion = XLSX.utils.json_to_sheet(filasLiquidacion);
                XLSX.utils.book_append_sheet(wb, wsLiquidacion, 'Liquidacion_SENAE');

                const wsAsiento = XLSX.utils.json_to_sheet(filasAsientoContable);
                // Mostrar cuatro decimales monetarios en Excel sin convertir números en texto.
                for (const ws of [wsLiquidacion, wsAsiento]) {
                    const rango = XLSX.utils.decode_range(ws['!ref']);
                    for (let col=rango.s.c;col<=rango.e.c;col++) {
                        const encabezado=ws[XLSX.utils.encode_cell({r:rango.s.r,c:col})]?.v||'';
                        if (!/\(\$\)|\(USD\)|Debe \(USD\)|Haber \(USD\)/i.test(String(encabezado))) continue;
                        for (let fila=rango.s.r+1;fila<=rango.e.r;fila++){
                            const celda=ws[XLSX.utils.encode_cell({r:fila,c:col})];
                            if(celda?.t==='n')celda.z='#,##0.0000';
                        }
                    }
                }
                XLSX.utils.book_append_sheet(wb, wsAsiento, 'Asiento_Contable');

                XLSX.writeFile(wb, 'Liquidacion_Costos_y_Asiento_Contable_EC.xlsx');
                mostrarNotificacion('Exportación Completa', 'Excel descargado con Liquidación y Asiento Contable.');
            } catch (e) {
                mostrarNotificacion('Error', 'No se pudo exportar el archivo Excel.', 'error');
            }
        }

        function descargarManual() {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                doc.setFillColor(17, 17, 17);
                doc.rect(0, 0, 210, 32, 'F');
                doc.setTextColor(212, 175, 55);
                doc.setFontSize(14);
                doc.text('MANUAL DE LIQUIDACIÓN ADUANERA SENAE / SRI ECUADOR', 14, 15);
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(9);
                doc.text('Guía Integral: Merma, Asiento Contable, Regla W/M, ISD, ICE y Rentabilidad', 14, 24);

                doc.setTextColor(30, 30, 30);
                doc.setFontSize(10);
                const lineas = [
                    '1. VALOR CIF Y SEGURO AUTOMÁTICO:',
                    '   - CIF = FOB + Flete + Seguro (tasa editable: comprobar póliza y base declarada).',
                    '2. TRIBUTOS SENAE:',
                    '   - Arancel Ad-Valorem (%) sobre CIF.',
                    '   - FODINFA: 0.5% sobre CIF.',
                    '3. COSTO REAL POR MERMA / ROTURA:',
                    '   - El costo total se redistribuye entre las unidades útiles vendibles.',
                    '4. REGLA LOGÍSTICA W/M (WEIGHT / MEASURE):',
                    '   - Determina el flete de carga suelta comparando Metros Cúbicos vs. Toneladas.',
                    '5. CONTABILIZACIÓN SENAE / SRI:',
                    '   - Asiento cuadrado: costo en bodega + IVA recuperable (si aplica) vs. acreedores.'
                ];
                let y = 45;
                lineas.forEach(l => {
                    doc.text(l, 14, y);
                    y += 9;
                });

                doc.save('Manual_Liquidacion_Importaciones_Ecuador.pdf');
                mostrarNotificacion('Manual Generado', 'Documento PDF descargado.');
            } catch (e) {
                mostrarNotificacion('Error', 'No se pudo generar el manual PDF.', 'error');
            }
        }

        function exportarReportePDF() {
            if (!validarAntesDeExportar('reporte PDF')) return;
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({orientation:'landscape',unit:'mm',format:'a4'});
                const widths=[30,89,36,36,36,42];
                const labels=['Código','Producto','Costo real','Mayorista','Eventual','PVP + IVA'];
                const money=v=>'$'+Number(v).toLocaleString('en-US',{minimumFractionDigits:4,maximumFractionDigits:4});
                let y=40;
                function pageHeader(){
                    doc.setFillColor(17,27,44);doc.rect(0,0,297,29,'F');
                    doc.setTextColor(237,205,105);doc.setFont('helvetica','bold');doc.setFontSize(14);
                    doc.text('MEGA PRIME IMPORT · COSTOS Y PRECIOS',14,13);
                    doc.setTextColor(255,255,255);doc.setFontSize(9);
                    doc.text('Partidas: '+itemsCalculados.length+' | Costo bodega: '+money(itemsCalculados.reduce((a,i)=>a+i.costoBodegaTotal,0)),14,22);
                    doc.setFontSize(9);doc.setTextColor(30,30,30);let x=14;
                    labels.forEach((label,i)=>{doc.text(label,x+2,36);x+=widths[i];});
                    doc.setDrawColor(185,154,65);doc.line(14,39,283,39);y=41;
                    doc.setFont('helvetica','normal');doc.setFontSize(8);
                }
                pageHeader();
                for(const it of itemsCalculados){
                    const values=[String(it.codigo),String(it.producto),money(it.costoUnitarioBodega),money(it.precioMayorista),money(it.precioEventual),money(it.pvpConIVA)];
                    const lines=values.map((v,i)=>doc.splitTextToSize(v,widths[i]-4));
                    // Fragmentar filas excepcionalmente largas sin cortar texto fuera de página.
                    let offset=0;const count=Math.max(...lines.map(a=>a.length));
                    while(offset<count){
                        if(y>186) {doc.addPage();pageHeader();}
                        const take=Math.min(count-offset,Math.max(1,Math.floor((190-y-4)/4)));
                        const height=take*4+4;let x=14;
                        lines.forEach((ls,i)=>{const chunk=ls.slice(offset,offset+take);if(chunk.length)doc.text(chunk,x+2,y+4);x+=widths[i];});
                        doc.setDrawColor(215,220,228);doc.line(14,y+height,283,y+height);y+=height;offset+=take;
                        if(offset<count){doc.addPage();pageHeader();}
                    }
                }
                const pages=doc.getNumberOfPages();
                for(let page=1;page<=pages;page++){doc.setPage(page);doc.setFontSize(8);doc.setTextColor(90,90,90);doc.text('Valores estimados según parámetros ingresados. Página '+page+' de '+pages,14,202);}
                doc.save('Mega_Prime_Import_Costos_y_Precios.pdf');
                mostrarNotificacion('Reporte generado','Lista de precios en PDF descargada.');
            } catch (e) {mostrarNotificacion('Error','No se pudo generar el PDF.','error');}
        }


        // === CAPA PRO: proveedores, cotizaciones, alertas y respaldos ===
        const PREMIUM_KEYS = { bitacora:'costos_importacion_bitacora_premium_v1' };
        function premiumLeerBitacora(){try{const x=JSON.parse(mpiUserStore.getItem(PREMIUM_KEYS.bitacora)||'[]');return Array.isArray(x)?x:[]}catch(e){return[]}}
        function premiumRegistrar(accion){try{const arr=premiumLeerBitacora();arr.unshift({fecha:new Date().toISOString(),accion:String(accion||'Actividad').slice(0,220)});mpiUserStore.setItem(PREMIUM_KEYS.bitacora,JSON.stringify(arr.slice(0,500)));premiumRenderBitacora();}catch(e){}}
        function premiumRegistrarManual(e){e.preventDefault();const el=document.getElementById('premiumNotaActividad');const v=el?.value.trim();if(!v)return;premiumRegistrar(v);el.value='';proMostrarNotificacion('Actividad registrada','Se añadió la nota a la bitácora local.');}
        function premiumRenderBitacora(){const arr=premiumLeerBitacora(),el=document.getElementById('premiumBitacoraLista'),c=document.getElementById('premiumConteoBitacora');if(c)c.textContent=arr.length+' eventos';if(!el)return;if(!arr.length){el.innerHTML='<div class="p-4 pro-muted">Todavía no hay eventos.</div>';return;}el.innerHTML='<table class="pro-table"><thead><tr><th>Fecha y hora</th><th>Actividad</th></tr></thead><tbody>'+arr.slice(0,30).map(x=>'<tr><td>'+proEsc(new Date(x.fecha).toLocaleString())+'</td><td>'+proEsc(x.accion)+'</td></tr>').join('')+'</tbody></table>'+(arr.length>30?'<div class="p-2 pro-muted">Se muestran los 30 eventos más recientes de '+arr.length+'.</div>':'');}
        function premiumLimpiarBitacora(){if(!confirm('¿Vaciar la bitácora local? Esta acción no se puede deshacer salvo que tengas un respaldo.'))return;mpiUserStore.removeItem(PREMIUM_KEYS.bitacora);premiumRenderBitacora();premiumRegistrar('Bitácora vaciada por el usuario');}
        function premiumExportarBitacora(){const arr=premiumLeerBitacora();if(!arr.length){proMostrarNotificacion('Bitácora vacía','Registra al menos una actividad antes de exportar.','error');return;}const esc=v=>'"'+String(v??'').replace(/"/g,'""')+'"';const rows=[['Fecha ISO','Actividad'].map(esc).join(','),...arr.map(x=>[x.fecha,x.accion].map(esc).join(','))];proDescargarBlob('bitacora_importaciones_'+new Date().toISOString().slice(0,10)+'.csv','\ufeff'+rows.join('\r\n'),'text/csv;charset=utf-8');premiumRegistrar('Bitácora exportada a CSV');}
        function premiumCalcularEscenario(){const n=Math.max(1,Number(document.getElementById('premiumExecUnidades')?.value)||1),m=Math.min(95,Math.max(0,Number(document.getElementById('premiumExecMargen')?.value)||0))/100,g=Math.max(0,Number(document.getElementById('premiumExecGastosVenta')?.value)||0),manual=document.getElementById('premiumExecPrecioManual')?.value;const t=document.getElementById('kpiCostoBodega')?.textContent||'0';const costoTotal=Math.max(0,Number(t.replace(/[^0-9,.-]/g,'').replace(/,/g,''))||0),cu=costoTotal/n+g/n,precio=m<1?cu/(1-m):cu,venta=manual!==''&&manual!=null?Math.max(0,Number(manual)||0):precio,utilidad=(venta-cu)*n,real=venta>0?((venta-cu)/venta)*100:0;const set=(id,v)=>{const el=document.getElementById(id);if(el)el.textContent=v};set('premiumExecCostoUnidad',fmtUSD(cu));set('premiumExecPrecioSugerido',fmtUSD(precio));set('premiumExecUtilidad',fmtUSD(utilidad));set('premiumExecMargenReal',real.toFixed(1)+'%');set('premiumExecNotaEscenario',`Costo base del embarque: ${fmtUSD(costoTotal)}. Precio usado para estimar utilidad: ${fmtUSD(venta)} por unidad. No incluye impuestos sobre la venta ni gastos no ingresados.`);}

        const PRO_KEYS = { proveedores:'costos_importacion_proveedores_v1', usuarios:'costos_importacion_usuarios_v1', escenarios:'costos_importacion_escenarios_premium_v1', nube:'costos_importacion_nube_config_v1' };
        function proLeerLista(key) { try { const v=JSON.parse(mpiUserStore.getItem(key)||'[]'); return Array.isArray(v)?v:[]; } catch(e) { return []; } }
        function proEsc(s) { return String(s??'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
        function proMostrarNotificacion(t,m,tipo) { if(typeof mostrarNotificacion==='function') mostrarNotificacion(t,m,tipo||'success'); else alert(t+'\n'+m); }
        function guardarProveedorPRO(e) {
            e.preventDefault();
            const nombre=document.getElementById('proProvNombre').value.trim(), contacto=document.getElementById('proProvContacto').value.trim();
            const producto=document.getElementById('proCotProducto').value.trim(), precio=Number(document.getElementById('proCotPrecio').value), cantidad=Number(document.getElementById('proCotCantidad').value), nota=document.getElementById('proCotNota').value.trim();
            if(!nombre||!producto||!Number.isFinite(precio)||precio<0||!Number.isFinite(cantidad)||cantidad<1){proMostrarNotificacion('Revisa los datos','Completa proveedor, producto, precio y cantidad.','error');return;}
            const arr=proLeerLista(PRO_KEYS.proveedores); arr.unshift({id:Date.now(),nombre,contacto,producto,precio,cantidad,nota,fecha:new Date().toISOString()});
            try { mpiUserStore.setItem(PRO_KEYS.proveedores,JSON.stringify(arr)); renderizarProveedoresPRO(); e.target.reset(); document.getElementById('proCotCantidad').value='1'; premiumRegistrar('Cotización guardada: '+producto+' / '+nombre); proMostrarNotificacion('Cotización guardada','Se guardó en este navegador.'); }
            catch(err){proMostrarNotificacion('No se pudo guardar','El almacenamiento local puede estar lleno.','error');}
        }
        function limpiarFormularioProveedorPRO(){document.getElementById('proProveedorForm').reset();document.getElementById('proCotCantidad').value='1';}
        function eliminarProveedorPRO(id){if(!confirm('¿Eliminar esta cotización de proveedor?'))return;try{const arr=proLeerLista(PRO_KEYS.proveedores).filter(x=>x.id!==id);mpiUserStore.setItem(PRO_KEYS.proveedores,JSON.stringify(arr));premiumRegistrar('Cotización/proveedor eliminado (ID '+id+')');renderizarProveedoresPRO();proMostrarNotificacion('Cotización eliminada','Se actualizó el listado local.');}catch(err){proMostrarNotificacion('No se pudo eliminar','El navegador no permitió actualizar el almacenamiento local.','error');}}
        function renderizarProveedoresPRO(){const el=document.getElementById('proTablaProveedores');if(!el)return;const arr=proLeerLista(PRO_KEYS.proveedores);if(!arr.length){el.innerHTML='<div class="p-4 pro-muted">Todavía no hay proveedores registrados.</div>';return;}el.innerHTML='<table class="pro-table"><thead><tr><th>Proveedor / producto</th><th>Precio</th><th>Cantidad</th><th>Total</th><th></th></tr></thead><tbody>'+arr.map(x=>`<tr><td><strong>${proEsc(x.nombre)}</strong><div class="pro-muted">${proEsc(x.producto)}</div><div class="pro-muted">${proEsc(x.contacto||'Sin contacto')} · ${proEsc(x.nota||'Sin condiciones')}</div></td><td>${fmtUSD(x.precio)}</td><td>${Number(x.cantidad)}</td><td><strong>${fmtUSD(Number(x.precio)*Number(x.cantidad))}</strong></td><td><button class="pro-btn" onclick="eliminarProveedorPRO(${Number(x.id)})" aria-label="Eliminar cotización">Eliminar</button></td></tr>`).join('')+'</tbody></table>';}
        function actualizarAlertasPRO(){
            const max=Number(document.getElementById('proUmbralCosto')?.value)||0, minMargen=Number(document.getElementById('proUmbralMargen')?.value)||0;
            const lista=Array.isArray(itemsCalculados)?itemsCalculados:[], alertas=[];
            lista.forEach(it=>{const costo=Number(it.costoUnitarioBodega)||0;const precio=Number(it.precioMayorista)||0;const margen=precio>0?((precio-costo)/precio)*100:0;if(max>0&&costo>max)alertas.push({tipo:'Costo alto',texto:`${it.codigo||''} ${it.producto||'Producto'}: costo ${fmtUSD(costo)} supera ${fmtUSD(max)}.`});if(precio>0&&margen<minMargen)alertas.push({tipo:'Margen bajo',texto:`${it.codigo||''} ${it.producto||'Producto'}: margen mayorista ${margen.toFixed(1)}%, mínimo ${minMargen}%.`});});
            const box=document.getElementById('proListaAlertas'); if(box){box.innerHTML=alertas.length?alertas.map(a=>`<div class="rounded-xl border border-amber-400/30 bg-amber-400/5 p-3"><div class="text-xs font-black text-amber-200">⚠ ${proEsc(a.tipo)}</div><div class="pro-muted mt-1">${proEsc(a.texto)}</div></div>`).join(''):'<div class="rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-3 text-sm text-emerald-200">Sin alertas con los umbrales actuales.</div>';}
            const k=document.getElementById('proKpiAlertas');if(k)k.textContent=String(alertas.length); const n=document.getElementById('proKpiProductos');if(n)n.textContent=String(lista.length);
            const costoTotal=document.getElementById('kpiCostoBodega');const kc=document.getElementById('proKpiCosto');if(kc)kc.textContent=costoTotal?costoTotal.textContent:'$0.00';
            if(typeof premiumCalcularEscenario==='function') premiumCalcularEscenario();
            return alertas;
        }
        function guardarUsuarioPRO(e){e.preventDefault();const nombre=document.getElementById('proUsuarioNombre').value.trim(),rol=document.getElementById('proUsuarioRol').value;if(!nombre||!rol){proMostrarNotificacion('Completa los datos','Escribe un nombre o correo y selecciona un rol.','error');return;}const arr=proLeerLista(PRO_KEYS.usuarios);if(arr.some(x=>String(x.nombre||'').trim().toLocaleLowerCase()===nombre.toLocaleLowerCase())){proMostrarNotificacion('Usuario ya registrado','Ya existe una referencia con ese nombre o correo.','error');return;}try{arr.push({id:Date.now(),nombre,rol,fecha:new Date().toISOString()});mpiUserStore.setItem(PRO_KEYS.usuarios,JSON.stringify(arr));premiumRegistrar('Usuario de referencia añadido: '+nombre+' ('+rol+')');document.getElementById('proUsuarioForm').reset();renderizarUsuariosPRO();proMostrarNotificacion('Referencia añadida','Se guardó el usuario de referencia en este navegador. No crea una cuenta real.');}catch(err){proMostrarNotificacion('No se pudo guardar','El almacenamiento local puede estar lleno o bloqueado. Descarga un respaldo y vuelve a intentarlo.','error');}}
        function eliminarUsuarioPRO(id){if(!confirm('¿Eliminar este usuario de referencia?'))return;try{mpiUserStore.setItem(PRO_KEYS.usuarios,JSON.stringify(proLeerLista(PRO_KEYS.usuarios).filter(x=>x.id!==id)));premiumRegistrar('Usuario de referencia eliminado (ID '+id+')');renderizarUsuariosPRO();proMostrarNotificacion('Referencia eliminada','Se actualizó el listado local.');}catch(err){proMostrarNotificacion('No se pudo eliminar','El navegador no permitió actualizar el almacenamiento local.','error');}}
        function renderizarUsuariosPRO(){const el=document.getElementById('proTablaUsuarios');if(!el)return;const arr=proLeerLista(PRO_KEYS.usuarios);if(!arr.length){el.innerHTML='<div class="p-4 pro-muted">No hay usuarios de referencia.</div>';return;}el.innerHTML='<table class="pro-table"><thead><tr><th>Usuario</th><th>Rol de referencia</th><th></th></tr></thead><tbody>'+arr.map(x=>`<tr><td>${proEsc(x.nombre)}</td><td><span class="pro-pill">${proEsc(x.rol)}</span></td><td><button class="pro-btn" onclick="eliminarUsuarioPRO(${Number(x.id)})">Eliminar</button></td></tr>`).join('')+'</tbody></table>';}
        function premiumDinero(n){return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(Number(n)||0);}
        function actualizarOpcionesProductoPremium(){
            const sel=document.getElementById('premiumProducto'); if(!sel)return;
            const anterior=sel.value; const lista=Array.isArray(itemsCalculados)?itemsCalculados:[];
            sel.innerHTML=lista.length?lista.map((it,i)=>`<option value="${i}">${proEsc(it.codigo||('Producto '+(i+1)))} · ${proEsc(it.producto||it.nombre||'Sin nombre')} · ${premiumDinero(it.costoUnitarioBodega||0)}</option>`).join(''):'<option value="">Calcula o agrega productos primero</option>';
            if(anterior!=='' && Number(anterior)<lista.length)sel.value=anterior;
            if(!lista.length){const cu=document.getElementById('premiumCostoUnit');if(cu)cu.textContent=premiumDinero(0);}
            actualizarSimuladorPremium();
        }
        function premiumObtenerCalculo(){
            const lista=Array.isArray(itemsCalculados)?itemsCalculados:[];const idx=Number(document.getElementById('premiumProducto')?.value);
            const it=Number.isInteger(idx)&&idx>=0?lista[idx]:null;const cantidad=Math.max(0,Number(document.getElementById('premiumCantidad')?.value)||0);
            const extra=Math.max(0,Number(document.getElementById('premiumCostoExtra')?.value)||0);const precio=Math.max(0,Number(document.getElementById('premiumPrecioVenta')?.value)||0);
            const costoBase=Number(it?.costoUnitarioBodega)||0;const costo=costoBase+extra;const venta=precio*cantidad;const inversion=costo*cantidad;const utilidad=venta-inversion;const margen=venta>0?utilidad/venta*100:0;
            return {it,idx,cantidad,extra,precio,costoBase,costo,venta,inversion,utilidad,margen,puntoEquilibrio:costo};
        }
        function actualizarSimuladorPremium(){
            const c=premiumObtenerCalculo();const put=(id,v)=>{const el=document.getElementById(id);if(el)el.textContent=v;};
            put('premiumCostoUnit',premiumDinero(c.costo));put('premiumVentaTotal',premiumDinero(c.venta));put('premiumUtilidad',premiumDinero(c.utilidad));put('premiumMargen',c.venta>0?c.margen.toFixed(1)+'%':'—');put('premiumPuntoEquilibrio','Punto de equilibrio: '+premiumDinero(c.puntoEquilibrio)+' por unidad');
            const utilidad=document.getElementById('premiumUtilidad');if(utilidad)utilidad.style.color=c.utilidad<0?'#fca5a5':'#86efac';
        }
        function guardarEscenarioPremium(){
            const c=premiumObtenerCalculo();if(!c.it){proMostrarNotificacion('Selecciona un producto','Agrega o calcula al menos un producto para simular.','error');return;}
            if(c.cantidad<1||c.precio<=0){proMostrarNotificacion('Revisa la simulación','Ingresa al menos una unidad y un precio de venta mayor que cero.','error');return;}
            const arr=proLeerLista(PRO_KEYS.escenarios);arr.unshift({id:Date.now(),fecha:new Date().toISOString(),codigo:c.it.codigo||'',producto:c.it.producto||c.it.nombre||'Producto',cantidad:c.cantidad,precioVenta:c.precio,costoUnitario:c.costo,ventaTotal:c.venta,costoTotal:c.inversion,utilidad:c.utilidad,margen:c.margen});
            try{mpiUserStore.setItem(PRO_KEYS.escenarios,JSON.stringify(arr.slice(0,200)));renderizarEscenariosPremium();proMostrarNotificacion('Escenario guardado','Se guardó una copia de esta simulación en el navegador.');}catch(e){proMostrarNotificacion('No se pudo guardar','El almacenamiento local puede estar lleno. Descarga un respaldo y elimina datos antiguos.','error');}
        }
        function eliminarEscenarioPremium(id){mpiUserStore.setItem(PRO_KEYS.escenarios,JSON.stringify(proLeerLista(PRO_KEYS.escenarios).filter(x=>x.id!==id)));renderizarEscenariosPremium();}
        function limpiarEscenariosPremium(){if(!confirm('¿Eliminar todos los escenarios guardados en este navegador?'))return;mpiUserStore.removeItem(PRO_KEYS.escenarios);renderizarEscenariosPremium();}
        function renderizarEscenariosPremium(){
            const el=document.getElementById('premiumTablaEscenarios');if(!el)return;const arr=proLeerLista(PRO_KEYS.escenarios);
            if(!arr.length){el.innerHTML='<div class="p-4 pro-muted">Aún no hay escenarios guardados.</div>';return;}
            el.innerHTML='<table class="pro-table"><thead><tr><th>Fecha</th><th>Producto</th><th>Unidades</th><th>Venta total</th><th>Utilidad</th><th>Margen</th><th></th></tr></thead><tbody>'+arr.map(x=>`<tr><td>${proEsc(new Date(x.fecha).toLocaleDateString())}</td><td><strong>${proEsc(x.codigo)} ${proEsc(x.producto)}</strong></td><td>${Number(x.cantidad)||0}</td><td>${premiumDinero(x.ventaTotal)}</td><td class="${Number(x.utilidad)<0?'text-red-300':'text-emerald-300'}">${premiumDinero(x.utilidad)}</td><td>${Number(x.margen).toFixed(1)}%</td><td><button class="pro-btn" type="button" onclick="eliminarEscenarioPremium(${Number(x.id)})">Eliminar</button></td></tr>`).join('')+'</tbody></table>';
        }
        function exportarEscenariosPremiumCSV(){
            const arr=proLeerLista(PRO_KEYS.escenarios);if(!arr.length){proMostrarNotificacion('Sin escenarios','Guarda al menos un escenario antes de exportar.','error');return;}
            const cols=['Fecha','Código','Producto','Unidades','Precio venta unitario USD','Costo unitario USD','Venta total USD','Costo total USD','Utilidad USD','Margen %'];const esc=v=>'"'+String(v??'').replace(/"/g,'""')+'"';
            const rows=arr.map(x=>[x.fecha,x.codigo,x.producto,x.cantidad,x.precioVenta,x.costoUnitario,x.ventaTotal,x.costoTotal,x.utilidad,x.margen].map(esc).join(','));
            proDescargarBlob('escenarios_rentabilidad_'+new Date().toISOString().slice(0,10)+'.csv','\ufeff'+[cols.map(esc).join(','),...rows].join('\r\n'),'text/csv;charset=utf-8');proMostrarNotificacion('Exportación lista','Se descargó el archivo CSV de escenarios.');
        }

        function proCapturarCamposFormularioPRO(){
            const campos={};
            document.querySelectorAll('input[id],select[id],textarea[id]').forEach(el=>{
                if(!el.id || el.type==='file' || el.type==='password' || el.id.startsWith('proUsuario') || el.id.startsWith('proCloud') || el.closest('#mpiDialogoArancel, #premiumCopias, #premiumVistaRestauracion')) return;
                campos[el.id] = el.type==='checkbox' || el.type==='radio' ? {tipo:el.type,checked:!!el.checked,value:el.value} : {tipo:el.tagName.toLowerCase(),value:el.value};
            });
            return campos;
        }
        function proDescargarBlob(nombre, contenido, tipo){
            const blob=new Blob([contenido],{type:tipo}); const url=URL.createObjectURL(blob); const a=document.createElement('a');
            a.href=url; a.download=nombre; document.body.appendChild(a); a.click(); a.remove(); setTimeout(()=>URL.revokeObjectURL(url),1500);
        }
        function proCapturarAlmacenamientoApp(){
            const datos={};
            for(let i=0;i<mpiUserStore.length;i++){
                const key=mpiUserStore.key(i);
                if(key && (key.startsWith('costos_importacion_') || key==='historial_embarques_ec')) datos[key]=mpiUserStore.getItem(key);
            }
            return datos;
        }
        function verificarIntegridadLocalPRO(){
            const el=document.getElementById('proEstadoIntegridad');
            try{
                const prueba='__pro_test_'+Date.now(); mpiUserStore.setItem(prueba,'ok'); const ok=mpiUserStore.getItem(prueba)==='ok'; mpiUserStore.removeItem(prueba);
                const historial=JSON.parse(mpiUserStore.getItem('historial_embarques_ec')||'{}');
                const proveedores=proLeerLista(PRO_KEYS.proveedores), usuarios=proLeerLista(PRO_KEYS.usuarios), escenarios=proLeerLista(PRO_KEYS.escenarios), bitacora=premiumLeerBitacora();
                const claves=proCapturarAlmacenamientoApp();
                const mensaje=`Almacenamiento ${ok?'disponible':'no confirmado'}. ${itemsImportacion.length} partidas en memoria; ${Object.keys(historial||{}).length} embarques guardados; ${proveedores.length} cotizaciones; ${usuarios.length} usuarios de referencia; ${escenarios.length} escenarios; ${bitacora.length} eventos de bitácora. ${Object.keys(claves).length} claves locales incluidas en el respaldo.`;
                if(el)el.textContent=mensaje;
                proMostrarNotificacion('Revisión local completada',mensaje,ok?'success':'error');
                return {ok,partidas:itemsImportacion.length,embarques:Object.keys(historial||{}).length,claves:Object.keys(claves).length};
            }catch(err){
                if(el)el.textContent='No se pudo leer o escribir en el almacenamiento local. Descarga cualquier respaldo que aún funcione y revisa la configuración del navegador.';
                proMostrarNotificacion('Almacenamiento no disponible','El navegador bloqueó el acceso o los datos locales no son válidos.','error');
                return {ok:false,error:String(err)};
            }
        }
        function exportarRespaldoPRO(confirmado = false){
            if (!confirmado) { abrirCopiasPremium(); return; }
            const campoNombre = document.getElementById('premiumNombreCopia');
            const nombreCopia = campoNombre.value.trim();
            if (!nombreCopia || nombreCopia.length > 120) {
                campoNombre.setCustomValidity('Escribe un nombre de entre 1 y 120 caracteres.');
                campoNombre.reportValidity(); campoNombre.focus(); return;
            }
            campoNombre.setCustomValidity('');
            try {
                const data={formato:'costos-importacion-ecuador-pro',version:3,nombreCopia,fecha:new Date().toISOString(),
                    itemsImportacion:Array.isArray(itemsImportacion)?itemsImportacion:[],
                    historial:mpiUserStore.getItem('historial_embarques_ec')||'{}',
                    proveedores:proLeerLista(PRO_KEYS.proveedores),usuariosReferencia:proLeerLista(PRO_KEYS.usuarios),bitacoraPremium:premiumLeerBitacora(),escenariosPremium:proLeerLista(PRO_KEYS.escenarios),
                    camposFormulario:proCapturarCamposFormularioPRO(),
                    borrador:mpiUserStore.getItem('costos_importacion_borrador_pro_v1')||'',
                    almacenamientoApp:proCapturarAlmacenamientoApp()};
                proDescargarBlob(nombreArchivoCopiaPremium(nombreCopia)+'.json',JSON.stringify(data,null,2),'application/json;charset=utf-8');
                const k=document.getElementById('proKpiNube'); if(k)k.textContent='Descarga solicitada';
                document.getElementById('premiumEstadoCopia').textContent='Descarga solicitada: '+nombreCopia;
                premiumRegistrar('Respaldo JSON v3 preparado: '+nombreCopia); premiumMarcarCopiaPreparada(); proMostrarNotificacion('Copia preparada','Verifica que el archivo aparezca en Descargas. Incluye productos, formularios, historial, proveedores y escenarios.');
            } catch(err){proMostrarNotificacion('Error al crear respaldo','No se pudo preparar el archivo de respaldo. Comprueba el espacio disponible y vuelve a intentarlo.','error');}
        }
        function importarRespaldoPRO(e){
            const input=e.target, file=input.files?.[0];if(!file)return;
            if(file.size>25*1024*1024){proMostrarNotificacion('Archivo demasiado grande','El límite es 25 MB. No se modificaron los datos.','error');input.value='';return;}
            const reader=new FileReader();
            reader.onload=()=>{try{premiumPrevisualizar(JSON.parse(reader.result),file.name);}catch(err){proMostrarNotificacion('Archivo no válido',err.message+' No se modificaron los datos.','error');}finally{input.value='';}};
            reader.onerror=()=>{proMostrarNotificacion('Error de lectura','No se pudo leer el archivo. No se modificaron los datos.','error');input.value='';};
            reader.readAsText(file);
        }
        function exportarProveedoresCSVPRO(){
            const arr=proLeerLista(PRO_KEYS.proveedores); if(!arr.length){proMostrarNotificacion('Sin cotizaciones','Registra al menos una cotización antes de exportar.','error');return;}
            const cols=['Proveedor','Contacto','Producto','Precio unitario USD','Cantidad','Total USD','Condiciones','Fecha'];
            const esc=v=>'"'+String(v??'').replace(/"/g,'""')+'"';
            const filas=arr.map(x=>[x.nombre,x.contacto,x.producto,x.precio,x.cantidad,Number(x.precio)*Number(x.cantidad),x.nota,x.fecha].map(esc).join(','));
            proDescargarBlob('proveedores_cotizaciones_'+new Date().toISOString().slice(0,10)+'.csv','\ufeff'+[cols.map(esc).join(','),...filas].join('\r\n'),'text/csv;charset=utf-8');
            proMostrarNotificacion('CSV exportado','Se descargó el listado de proveedores y cotizaciones.');
        }
        function mostrarEstadoNubePRO(){
            proMostrarNotificacion('Almacenamiento por archivos','Usa Descargar respaldo JSON para guardar y Restaurar desde archivo para recuperar tus datos. Esta versión no sincroniza con la nube.','success');
        }
        function guardarConfiguracionNubePRO(){mostrarEstadoNubePRO();}
        function cargarConfigNubePRO(){const k=document.getElementById('proKpiNube');if(k)k.textContent='Listo para exportar';}



        // Refresca los indicadores PRO después de cada recálculo sin alterar fórmulas existentes.
        const recalcularTodoOriginalPRO = recalcularTodo;
        let proRestaurandoBorrador = false;
        recalcularTodo = function(...args) { const resultado = recalcularTodoOriginalPRO.apply(this,args); try { actualizarOpcionesProductoPremium(); actualizarAlertasPRO(); if(!proRestaurandoBorrador && Array.isArray(itemsImportacion)) mpiUserStore.setItem('costos_importacion_borrador_pro_v1', JSON.stringify({fecha:new Date().toISOString(),itemsImportacion})); } catch(e) {} return resultado; };

        window.addEventListener('DOMContentLoaded', () => {
            seleccionarModoDispositivo(window.innerWidth < 1024 ? 'TARJETAS' : 'TABLA', false);
            let proCargoBorrador=false;
            try { const borrador=JSON.parse(mpiUserStore.getItem('costos_importacion_borrador_pro_v1')||'null'); if(borrador && Array.isArray(borrador.itemsImportacion)) { itemsImportacion=borrador.itemsImportacion; proRestaurandoBorrador=true; recalcularTodo(); proRestaurandoBorrador=false; const estado=document.getElementById('estadoEmbarqueTexto'); if(estado)estado.textContent=`Borrador local recuperado (${itemsImportacion.length} partidas)`; const badge=document.getElementById('badgeArchivoCargado'); if(badge)badge.textContent='Borrador recuperado'; proCargoBorrador=true; } } catch(e) {}
            if(!proCargoBorrador) cargarDatosDemo();
            actualizarSelectHistorial();
            renderizarProveedoresPRO();
            renderizarUsuariosPRO();
            premiumRenderBitacora();
            premiumCalcularEscenario();
            renderizarEscenariosPremium();
            actualizarOpcionesProductoPremium();
            cargarConfigNubePRO();
            actualizarAlertasPRO();
            try { const el=document.getElementById('proEstadoIntegridad'); if(el){ const claves=proCapturarAlmacenamientoApp(); el.textContent=`Almacenamiento local preparado. ${itemsImportacion.length} partidas cargadas; ${Object.keys(claves).length} claves locales detectadas. Usa “Revisar estado” para un diagnóstico detallado.`; } } catch(e) {}
        });
    </script>

<script>
/* MEJORAS DE SEGURIDAD Y CONTROL — capa adicional, sin modificar fórmulas de cálculo */
(function () {
    // Evita borrar accidentalmente las partidas del embarque.
    const borrarDatosOriginal = window.borrarDatos;
    if (typeof borrarDatosOriginal === 'function') {
        window.borrarDatos = function () {
            const ok = window.confirm(
                '¿Deseas borrar los productos del embarque actual?\n\n' +
                'Esta acción no se puede deshacer desde esta pantalla. ' +
                'Si necesitas conservarlos, cancela y crea primero un respaldo JSON.'
            );
            if (!ok) return;
            borrarDatosOriginal();
        };
    }

    // Revisión preventiva de campos numéricos antes de exportar o continuar.
    window.validarDatosDashboard = function () {
        recalcularTodo();
        const problemas = validarDatosCalculo();
        if (problemas.length) {
            actualizarBannerValidacion(problemas);
            document.getElementById('validacionCalculoBanner')?.scrollIntoView({behavior:'smooth',block:'start'});
            document.querySelector('[data-error-calculo]')?.focus({preventScroll:true});
            return false;
        }
        const campos = Array.from(document.querySelectorAll('input[type="number"], input[type="range"]'));
        const errores = [];
        const esControlAuxiliar = el => Boolean(el.closest('#mpiDialogoArancel, #modalNuevoItem, #modalCatalogoICE, #modalInfoISD, #panelHerramientasPRO, #panelPremiumComercial, #panelPremium, #panelProveedoresUX, #panelUsuariosUX, #panelReportesUX, #mpi-panel-proveedores, #mpi-panel-administracion, #mpi-panel-reportes, #mpi-panel-rentabilidad'));
        campos.forEach((el) => {
            el.setCustomValidity('');
            if (el.disabled || el.type === 'hidden' || esControlAuxiliar(el)) return;
            const raw = String(el.value ?? '').trim();
            if (raw === '' && el.required) {
                errores.push((etiquetaCampo(el) || el.id || 'Campo sin nombre') + ': es obligatorio.');
                return;
            }
            if (raw !== '' && !Number.isFinite(Number(raw))) {
                errores.push((etiquetaCampo(el) || el.id || 'Campo sin nombre') + ': ingresa un número válido.');
                return;
            }
            if (raw !== '' && el.min !== '' && Number(raw) < Number(el.min)) {
                errores.push((etiquetaCampo(el) || el.id || 'Campo sin nombre') + ': no puede ser menor que ' + el.min + '.');
            }
            if (raw !== '' && el.max !== '' && Number(raw) > Number(el.max)) {
                errores.push((etiquetaCampo(el) || el.id || 'Campo sin nombre') + ': no puede superar ' + el.max + '.');
            }
        });
        const partidas = (typeof itemsImportacion !== 'undefined' && Array.isArray(itemsImportacion)) ? itemsImportacion : [];
        if (!partidas.length) errores.push('Embarque: agrega al menos una partida antes de interpretar los resultados.');
        partidas.forEach((it, i) => {
            const n = i + 1;
            if (!String(it.producto || '').trim()) errores.push('Partida ' + n + ': falta la descripción del producto.');
            if (!(Number(it.cantidad) > 0)) errores.push('Partida ' + n + ': la cantidad debe ser mayor que cero.');
            if (!(Number(it.fobUnitario) > 0)) errores.push('Partida ' + n + ': el FOB unitario debe ser mayor que cero.');
            if (Number(it.mermaPct) < 0 || Number(it.mermaPct) >= 100) errores.push('Partida ' + n + ': revisa el porcentaje de merma (debe estar entre 0 y menos de 100).');
        });
        const calculados = (typeof itemsCalculados !== 'undefined' && Array.isArray(itemsCalculados)) ? itemsCalculados : [];
        calculados.forEach((it, i) => {
            ['cifTotal', 'costoBodegaTotal', 'costoUnitarioBodega', 'ivaImportacionItem', 'fobTotal', 'fleteTotal', 'seguroTotal'].forEach((campo) => {
                const v = Number(it[campo]);
                if (!Number.isFinite(v) || v < -0.0001) errores.push('Partida calculada ' + (i + 1) + ': resultado inválido en ' + campo + '.');
            });
            const tol = 0.02;
            const cifEsperado = Number(it.fobTotal) + Number(it.fleteTotal) + Number(it.seguroTotal);
            if ([it.fobTotal, it.fleteTotal, it.seguroTotal, it.cifTotal].every(v => Number.isFinite(Number(v))) && Math.abs(cifEsperado - Number(it.cifTotal)) > tol) {
                errores.push('Partida calculada ' + (i + 1) + ': el CIF no coincide con FOB + flete + seguro.');
            }
            const costoEsperado = Number(it.cifTotal) + Number(it.arancelesTotalesItem) + Number(it.otrosRubrosGravadosItem) + Number(it.gastoLocalAsignado) + (it.ivaImportacionRecuperable ? 0 : Number(it.ivaImportacionItem));
            if (Number.isFinite(costoEsperado) && Number.isFinite(Number(it.costoBodegaTotal)) && Math.abs(costoEsperado - Number(it.costoBodegaTotal)) > tol) {
                errores.push('Partida calculada ' + (i + 1) + ': el costo de bodega no coincide con sus componentes.');
            }
        });
        if (calculados.length) {
            const sumaCostos = calculados.reduce((sum, it) => sum + (Number(it.costoBodegaTotal) || 0), 0);
            const textoKpi = document.getElementById('kpiCostoBodega')?.textContent || '';
            const valorKpi = Number(textoKpi.replace(/[^0-9,.-]/g, '').replace(/,(?=\d{3}(?:\D|$))/g, ''));
            if (Number.isFinite(valorKpi) && Math.abs(sumaCostos - valorKpi) > 0.03) {
                errores.push('Consistencia: la suma del costo de bodega por partida no coincide con el KPI general. Recalcula antes de exportar.');
            }
        }
        if (errores.length) {
            const resumen = errores.slice(0, 12).join('\n');
            window.alert('Se encontraron ' + errores.length + ' posible(s) problema(s):\n\n' +
                resumen + (errores.length > 12 ? '\n\n…y ' + (errores.length - 12) + ' más.' : '') +
                '\n\nCorrige estos campos y vuelve a validar.');
            const primerId = campos.find(el => el.required && !el.value)?.id;
            if (primerId) document.getElementById(primerId)?.focus();
            return false;
        }
        window.alert('Validación preventiva completada: no se detectaron campos numéricos fuera de rango ni problemas básicos en las partidas.\n\nImportante: esto no certifica las fórmulas tributarias, subpartidas, exenciones ni tarifas. Verifica la liquidación con SENAE/SRI y con tu agente de aduana.');
        return true;
    };

    function etiquetaCampo(el) {
        if (el.id) {
            const label = document.querySelector('label[for="' + CSS.escape(el.id) + '"]');
            if (label) return label.innerText.trim().replace(/\s+/g, ' ');
        }
        return el.getAttribute('aria-label') || el.name || el.id || '';
    }
})();
</script>


<script id="precisionMonetariaCuatroDecimales">
/* Precisión de entrada: los importes monetarios se muestran con cuatro decimales.
   El cálculo interno no se redondea a cuatro decimales; solo se formatea el campo visible. */
(function () {
  'use strict';
  const idsMonetarios = new Set(['proCotPrecio', 'proUmbralCosto', 'premiumPrecioVenta', 'premiumCostoExtra', 'premiumExecGastosVenta', 'premiumExecPrecioManual', 'gastosAgenteAduana', 'tasasPortuarias', 'gastosNaviera', 'gastosContenedor', 'transporteDescarga', 'gastosViaticos', 'extrasRuta', 'comisionBancos', 'impuestoISD', 'seguroInternacionalGlobal', 'fleteInternacionalGlobal', 'otrosRubrosGravadosDeclaracion', 'impuestoICE', 'globalICEEspecificoUSD', 'modalICEEspecificoUSD', 'modalICEBaseDeclarada', 'montoMonedaExtranjera', 'nuevoFOB', 'nuevoFlete', 'nuevoSeguro', 'nuevoFactorICE', 'nuevoICEEspecifico']);
  function formatearImporte(input) {
    if (!input || !idsMonetarios.has(input.id) || input.value.trim() === '') return;
    const valor = Number(String(input.value).trim().replace(',', '.'));
    if (Number.isFinite(valor)) input.value = valor.toFixed(4);
  }
  function instalarFormato() {
    document.querySelectorAll('input[type="number"]').forEach(function (input) {
      if (idsMonetarios.has(input.id)) {
        input.setAttribute('step', '0.0001');
        input.setAttribute('inputmode', 'decimal');
        input.addEventListener('blur', function () { formatearImporte(input); });
        input.addEventListener('change', function () { formatearImporte(input); });
        formatearImporte(input);
      }
    });
    document.querySelectorAll('input[type="number"][onchange*="fobUnitario"]').forEach(function (input) {
      input.setAttribute('step', '0.0001');
      input.addEventListener('blur', function () {
        if (input.value.trim() !== '' && Number.isFinite(Number(input.value))) {
          input.value = Number(input.value).toFixed(4);
        }
      });
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', instalarFormato, { once: true });
  } else {
    instalarFormato();
  }
})();
</script>


<script id="auditoriaMatematicaUI">
(function () {
    'use strict';
    function pintarEstado(id, texto, ok) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = texto;
        el.classList.remove('text-emerald-300', 'text-red-300', 'text-[#D4AF37]');
        el.classList.add(ok ? 'text-emerald-300' : 'text-red-300');
    }
    function actualizarPanelAuditoria() {
        const estadoEntradas = document.getElementById('auditoriaEstadoEntradas');
        if (!estadoEntradas || typeof validarDatosCalculo !== 'function') return;
        const errores = validarDatosCalculo();
        if (errores.length) {
            const vacio = !itemsImportacion.length;
            ['auditoriaEstadoEntradas','auditoriaEstadoCIF','auditoriaEstadoCosto'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.textContent = vacio ? 'Pendiente: agrega productos' : 'Pendiente: corrige las entradas'; el.style.color = vacio ? '#7dd3fc' : '#fca5a5'; }
            });
            return;
        }
        ['auditoriaEstadoEntradas','auditoriaEstadoCIF','auditoriaEstadoCosto'].forEach(id => document.getElementById(id)?.style.removeProperty('color'));
        pintarEstado('auditoriaEstadoEntradas', errores.length ? `${errores.length} observación(es)` : 'Sin errores detectados', errores.length === 0);
        const calculados = (typeof itemsCalculados !== 'undefined' && Array.isArray(itemsCalculados)) ? itemsCalculados : [];
        const tol = 0.02;
        const cifOk = calculados.length > 0 && calculados.every(it => {
            const vals = [it.fobTotal, it.fleteTotal, it.seguroTotal, it.cifTotal].map(Number);
            return vals.every(Number.isFinite) && Math.abs(vals[0] + vals[1] + vals[2] - vals[3]) <= tol;
        });
        const costoOk = calculados.length > 0 && calculados.every(it => {
            const esperado = Number(it.cifTotal) + Number(it.arancelesTotalesItem) + Number(it.otrosRubrosGravadosItem) + Number(it.gastoLocalAsignado) + (it.ivaImportacionRecuperable ? 0 : Number(it.ivaImportacionItem));
            return Number.isFinite(esperado) && Number.isFinite(Number(it.costoBodegaTotal)) && Math.abs(esperado - Number(it.costoBodegaTotal)) <= tol;
        });
        pintarEstado('auditoriaEstadoCIF', cifOk ? 'Conciliado' : 'Pendiente de revisión', cifOk);
        pintarEstado('auditoriaEstadoCosto', costoOk ? 'Conciliado' : 'Pendiente de revisión', costoOk);
    }
    const recalcularAnterior = window.recalcularTodo;
    if (typeof recalcularAnterior === 'function') {
        window.recalcularTodo = function (...args) {
            const resultado = recalcularAnterior.apply(this, args);
            try { actualizarPanelAuditoria(); } catch (e) { /* El panel no debe interrumpir el cálculo principal. */ }
            return resultado;
        };
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', actualizarPanelAuditoria, { once: true });
    else actualizarPanelAuditoria();
})();
</script>


<style id="premiumCopiasEstilo">
#premiumCopias {position:fixed;right:16px;bottom:16px;z-index:40;max-width:calc(100vw - 32px);color:#fff;background:#101722;border:1px solid #b99a3f;border-radius:16px;box-shadow:0 10px 35px #0008;font:14px/1.5 system-ui,sans-serif;}
#premiumCopias header {display:flex;align-items:center;justify-content:space-between;gap:20px;padding:10px 14px;}
#premiumCopias strong,#premiumCopias label {color:#edcd69;}
#premiumCopias button,#premiumCopias .premiumRestaurar {border:1px solid #536173;border-radius:9px;padding:9px 12px;color:#fff;background:#192536;cursor:pointer;font:inherit;}
#premiumCopias button:focus-visible,#premiumCopias input:focus-visible {outline:2px solid #edcd69;outline-offset:3px;}
#premiumCopiasContenido {width:350px;max-width:calc(100vw - 32px);max-height:65vh;overflow:auto;padding:0 16px 16px;box-sizing:border-box;}
#premiumCopias [hidden] {display:none!important;}
#premiumCopias input[type=text] {display:block;box-sizing:border-box;width:100%;margin:8px 0;padding:11px;background:#080e18;color:#fff;border:1px solid #69778b;border-radius:9px;font:inherit;}
#premiumCopias .premiumAyuda {font-size:12px;color:#b9c6d7;margin:8px 0 14px;}
#premiumCopias .premiumGuardar {background:#e4bf53;color:#101722;border-color:#e4bf53;width:100%;font-weight:700;}
#premiumCopias .premiumAcciones {display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;}
#premiumCopias label.premiumRestaurar {color:#fff;}
body {padding-bottom:85px;}
@media print {#premiumCopias {display:none!important;}}
</style>
<aside id="premiumCopias" aria-label="Copias de seguridad">
<header><strong>Guardado y respaldos</strong><button id="premiumMostrarCopias" type="button" aria-expanded="false" aria-controls="premiumCopiasContenido" onclick="alternarCopiasPremium()">Mostrar</button></header>
<div id="premiumCopiasContenido" hidden>
<p class="premiumAyuda">Área administrativa · Los datos se guardan en este navegador y dispositivo. Descarga una copia JSON para respaldarlos o trasladarlos a otro equipo. Si borras los datos del navegador, puedes perder la información local.</p>
<form onsubmit="event.preventDefault();exportarRespaldoPRO(true)">
<label for="premiumNombreCopia">Nombre de la copia / archivo</label>
<input id="premiumNombreCopia" type="text" maxlength="120" required placeholder="Importación octubre 2026" oninput="this.setCustomValidity('')">
<p class="premiumAyuda">El nombre y la fecha se incluyen dentro del respaldo. Los caracteres no válidos se sustituyen en el nombre del archivo.</p>
<button class="premiumGuardar" type="submit">Descargar copia completa (.json)</button>
</form>
<p class="premiumAyuda">Las referencias de usuarios no crean cuentas ni conceden permisos. El acceso privado se configura por separado en el servidor.</p><div class="premiumAcciones"><label class="premiumRestaurar" for="premiumRestaurarArchivo">Restaurar copia</label><input id="premiumRestaurarArchivo" type="file" accept=".json,application/json" onchange="importarRespaldoPRO(event)"><button type="button" onclick="verUsuariosDesdeCopiasPremium()">Referencias de usuarios (sin acceso)</button></div>
<button type="button" onclick="premiumAbrirRecuperacion()" style="margin-top:12px">Recuperar último punto de seguridad</button><p class="premiumAyuda">Se crea antes de restaurar una copia, cargar un embarque o reemplazar uno existente. No es un historial de todos los cambios.</p><p class="premiumAyuda">Guardar embarque conserva sus productos en este navegador. La copia completa incluye también configuración, historial y proveedores. No se sincronizan datos entre dispositivos ni se guardan en una base de datos del servidor.</p><p id="premiumProteccionEstado" class="premiumAyuda" role="status"></p>
<p id="premiumEstadoCopia" class="premiumAyuda" role="status" aria-live="polite">Las copias anteriores siguen siendo compatibles.</p>
</div></aside>
<script id="premiumCopiasScript">
function nombreArchivoCopiaPremium(nombre) {
    let limpio=nombre.replace(/\.json$/i,'').replace(/[<>:"/\\|?*\x00-\x1f]/g,'_').replace(/[. ]+$/g,'').trim();
    if (!limpio) limpio='respaldo_importacion';
    if (/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/i.test(limpio)) limpio='copia_'+limpio;
    return limpio;
}
function alternarCopiasPremium(abrir) {
    const contenido=document.getElementById('premiumCopiasContenido');
    const mostrar=typeof abrir==='boolean'?abrir:contenido.hidden;
    if(mostrar && typeof mpiAbrirPestana==='function')mpiAbrirPestana('administracion',true);
    contenido.hidden=!mostrar;
    const boton=document.getElementById('premiumMostrarCopias');
    boton.textContent=mostrar?'Ocultar':'Mostrar';boton.setAttribute('aria-expanded',String(mostrar));
}
function abrirCopiasPremium() {alternarCopiasPremium(true);document.getElementById('premiumNombreCopia').focus();}
function verUsuariosDesdeCopiasPremium() {
    alternarCopiasPremium(false);
    if(typeof mpiAbrirPestana==='function')mpiAbrirPestana('administracion',true);
    const panel=document.getElementById('panelHerramientasPRO');
    if(panel)panel.classList.remove('hidden');
    document.getElementById('proUsuarioForm').scrollIntoView({behavior:'smooth',block:'center'});
    document.getElementById('proUsuarioNombre').focus({preventScroll:true});
}
(function(){
    const panel=document.getElementById('premiumCopias');
    const modales=Array.from(document.querySelectorAll('[id^="modal"]')).filter(el=>el.classList.contains('fixed'));
    function sincronizar(){
        const abierto=modales.some(el=>!el.classList.contains('hidden')&&getComputedStyle(el).display!=='none');
        panel.hidden=abierto;
        if(abierto)alternarCopiasPremium(false);
    }
    modales.forEach(el=>new MutationObserver(sincronizar).observe(el,{attributes:true,attributeFilter:['class','style']}));
    panel.addEventListener('keydown',e=>{if(e.key==='Escape'){alternarCopiasPremium(false);document.getElementById('premiumMostrarCopias').focus();}});
    sincronizar();
})();
</script>

<style>
#premiumVistaRestauracion{width:min(560px,calc(100vw - 32px));max-height:85vh;overflow:auto;margin:auto;padding:24px;border:1px solid #c7a74b;border-radius:18px;background:#101722;color:#f4f6fa;font:14px/1.6 system-ui,sans-serif;box-sizing:border-box;box-shadow:0 20px 80px #0009}
#premiumVistaRestauracion::backdrop{background:#000b}
#premiumVistaRestauracion h2{font-size:21px;font-weight:700;color:#edcd69;margin:0 0 12px}
#premiumVistaRestauracion dl{display:grid;grid-template-columns:110px 1fr;gap:8px;margin:16px 0}
#premiumVistaRestauracion dt{color:#b9c6d7}#premiumVistaRestauracion dd{margin:0;overflow-wrap:anywhere}
#premiumVistaRestauracion button{padding:10px 14px;margin:8px 6px 0 0;border:1px solid #69778b;border-radius:9px;background:#192536;color:white;cursor:pointer;font:inherit}
#premiumVistaRestauracion button:disabled{opacity:.4;cursor:not-allowed}#premiumVistaRestauracion button.premiumConfirmar{background:#edcd69;color:#101722;font-weight:700}
#premiumVistaRestauracion .aviso{padding:12px;border:1px solid #885c33;background:#2b2118;border-radius:9px;color:#ffce96}
@media print{#premiumVistaRestauracion{display:none!important}}
</style>
<dialog id="premiumVistaRestauracion" aria-labelledby="premiumVistaTitulo">
<h2 id="premiumVistaTitulo">Revisar copia antes de restaurar</h2>
<dl><dt>Nombre</dt><dd id="premiumVistaNombre"></dd><dt>Fecha</dt><dd id="premiumVistaFecha"></dd><dt>Contenido</dt><dd id="premiumVistaContenido"></dd></dl>
<p class="aviso">Se reemplazarán las partidas y los datos incluidos en esta copia. Antes de aplicar los cambios se guardará un punto de recuperación en este navegador. Se conserva un único punto: el más reciente.</p>
<p id="premiumVistaPendientes"></p>
<label style="display:block;margin-top:16px"><input id="premiumAceptaRestaurar" type="checkbox" onchange="document.getElementById('premiumAplicarRestaurar').disabled=!this.checked"> He revisado la copia y quiero reemplazar los datos actuales.</label>
<div><button type="button" onclick="premiumCancelarRestauracion()">Cancelar</button><button type="button" onclick="premiumDescargarAntesRestaurar()">Descargar estado actual</button><button id="premiumAplicarRestaurar" type="button" class="premiumConfirmar" disabled onclick="premiumAplicarRestauracion()">Restaurar copia</button></div>
<p id="premiumVistaError" role="alert" style="color:#ffb4a6"></p>
</dialog>
<script id="premiumProteccionScript">
const PREMIUM_RECUPERACION_KEY='premium_recuperacion_segura_v1';
let premiumCopiaPendiente=null, premiumBaseProteccion=null;
function premiumObjeto(v){return !!v&&typeof v==='object'&&!Array.isArray(v);}
function premiumValidarPartidas(lista){
    if(Array.isArray(lista))for(const item of lista){if(item && item.usarPreferenciaOrigen!==undefined && typeof item.usarPreferenciaOrigen!=='boolean')throw Error('Indicador de certificado inválido');if(item?.usarPreferenciaOrigen && !mpiTarifaPreferencialValida(item))throw Error('Tarifa preferencial inválida en el respaldo');if(item?.modalidadICE && !['NINGUNO','ESPECIFICO','AD_VALOREM','MIXTO'].includes(item.modalidadICE))throw Error('Modalidad ICE inválida en el respaldo');}
    if(!Array.isArray(lista)||lista.length>5000||!lista.every(it=>premiumObjeto(it)&&typeof it.codigo==='string'&&typeof it.producto==='string'&&it.cantidad!==''&&Number.isFinite(Number(it.cantidad))&&Number(it.cantidad)>0&&it.fobUnitario!==''&&Number.isFinite(Number(it.fobUnitario))&&Number(it.fobUnitario)>0))throw Error('Las partidas del respaldo no son válidas.');
    for(const it of lista){
        const rangos={arancelPorcentaje:[0,100],tarifaISD:[0,100],mermaPct:[0,90],fleteUnitario:[0,Infinity],seguroUnitario:[0,Infinity],iceEspecificoUSD:[0,Infinity],icePorcentaje:[0,500],factorICE:[0,Infinity],pesoUnitarioKg:[0,Infinity],cbmUnitario:[0,Infinity],iceBaseDeclarada:[0,Infinity]};
        for(const [key,[min,max]] of Object.entries(rangos)){if(it[key]!==undefined&&(!Number.isFinite(Number(it[key]))||Number(it[key])<min||Number(it[key])>max))throw Error('Valor inválido en respaldo: '+key);}
        if(it.usarPreferenciaOrigen===true&&!mpiTarifaPreferencialValida(it))throw Error('Tarifa preferencial inválida en respaldo.');
        if(it.modalidadICE!==undefined&&!['NINGUNO','ESPECIFICO','AD_VALOREM','MIXTO'].includes(it.modalidadICE))throw Error('Modalidad ICE inválida en respaldo.');
    }
}
function premiumValidarHistorial(v){if(!premiumObjeto(v))throw Error('El historial no es válido.');Object.values(v).forEach(premiumValidarPartidas);}
function premiumValidarCopia(d){
    if(!premiumObjeto(d)||d.formato!=='costos-importacion-ecuador-pro'||![2,3].includes(Number(d.version)))throw Error('El formato de respaldo no es compatible.');
    premiumValidarPartidas(d.itemsImportacion);
    if(d.historial!==undefined)premiumValidarHistorial(typeof d.historial==='string'?JSON.parse(d.historial):d.historial);
    for(const key of ['proveedores','usuariosReferencia','bitacoraPremium','escenariosPremium'])if(d[key]!==undefined&&(!Array.isArray(d[key])||!d[key].every(premiumObjeto)))throw Error('La lista '+key+' no es válida.');
    if(d.camposFormulario!==undefined&&(!premiumObjeto(d.camposFormulario)||!Object.values(d.camposFormulario).every(premiumObjeto)))throw Error('Los campos del formulario no son válidos.');
    if(d.almacenamientoApp!==undefined){
        if(!premiumObjeto(d.almacenamientoApp))throw Error('El almacenamiento no es válido.');
        for(const [key,value] of Object.entries(d.almacenamientoApp)){
            if(!(key.startsWith('costos_importacion_')||key==='historial_embarques_ec')||typeof value!=='string')throw Error('El respaldo contiene claves incompatibles.');
            const parsed=JSON.parse(value);
            if(key==='historial_embarques_ec')premiumValidarHistorial(parsed);
            if([PRO_KEYS.proveedores,PRO_KEYS.usuarios,PRO_KEYS.escenarios,PREMIUM_KEYS.bitacora].includes(key)&&(!Array.isArray(parsed)||!parsed.every(premiumObjeto)))throw Error('Una lista almacenada no es válida.');
            if(key==='costos_importacion_importaciones_v1'){
                if(!premiumObjeto(parsed))throw Error('Historial de importaciones inválido');
                for(const d of Object.values(parsed)){
                    if(!premiumObjeto(d)||d.version!==1||!premiumObjeto(d.camposFormulario)||!Object.values(d.camposFormulario).every(premiumObjeto))throw Error('Importación guardada inválida');
                    premiumValidarPartidas(d.itemsImportacion);
                }
            }
            if(key==='costos_importacion_borrador_pro_v1'){if(!premiumObjeto(parsed))throw Error('El borrador no es válido.');premiumValidarPartidas(parsed.itemsImportacion);}
        }
    }
    return d;
}
function premiumCapturarCopia(nombre){
    return {formato:'costos-importacion-ecuador-pro',version:3,nombreCopia:nombre,fecha:new Date().toISOString(),itemsImportacion:JSON.parse(JSON.stringify(itemsImportacion)),historial:mpiUserStore.getItem('historial_embarques_ec')||'{}',proveedores:proLeerLista(PRO_KEYS.proveedores),usuariosReferencia:proLeerLista(PRO_KEYS.usuarios),escenariosPremium:proLeerLista(PRO_KEYS.escenarios),bitacoraPremium:premiumLeerBitacora(),camposFormulario:proCapturarCamposFormularioPRO(),almacenamientoApp:proCapturarAlmacenamientoApp()};
}
function premiumGuardarRecuperacion(){
    const copia=premiumCapturarCopia('Recuperación antes de reemplazar datos');
    const bytes=JSON.stringify(copia);
    mpiUserStore.setItem(PREMIUM_RECUPERACION_KEY,bytes);
    if(mpiUserStore.getItem(PREMIUM_RECUPERACION_KEY)!==bytes)throw Error('No se pudo verificar el punto de recuperación.');
    return copia;
}
function premiumFirmaActual(){
    const campos=proCapturarCamposFormularioPRO();
    // Los filtros y selectores de navegación no representan cambios de negocio.
    for(const id of Object.keys(campos))if(/buscar|filtro|selectCotizacionesGuardadas|nombreCotizacionInput/i.test(id))delete campos[id];
    const almacen=proCapturarAlmacenamientoApp();delete almacen[PREMIUM_KEYS.bitacora];delete almacen.costos_importacion_borrador_pro_v1;
    return JSON.stringify({partidas:itemsImportacion,campos,almacen});
}
function premiumHayPendientes(){try{return premiumBaseProteccion!==null&&premiumFirmaActual()!==premiumBaseProteccion;}catch{return true;}}
function premiumActualizarProteccion(){const el=document.getElementById('premiumProteccionEstado');if(el)el.textContent=premiumHayPendientes()?'Hay cambios desde el inicio o la última copia preparada. Descarga una copia completa.':'Sin cambios nuevos desde el inicio o la última copia preparada.';}
function premiumMarcarCopiaPreparada(){try{premiumBaseProteccion=premiumFirmaActual();}catch{}premiumActualizarProteccion();}
function premiumPrevisualizar(d,nombreArchivo){
    premiumValidarCopia(d);
    const hist=d.almacenamientoApp?JSON.parse(d.almacenamientoApp.historial_embarques_ec||'{}'):(typeof d.historial==='string'?JSON.parse(d.historial):d.historial||{});
    const proveedores=d.almacenamientoApp?JSON.parse(d.almacenamientoApp[PRO_KEYS.proveedores]||'[]'):(d.proveedores||[]);
    premiumCopiaPendiente=d;
    document.getElementById('premiumVistaNombre').textContent=typeof d.nombreCopia==='string'&&d.nombreCopia.trim()?d.nombreCopia:nombreArchivo+' (sin nombre interno)';
    const fecha=typeof d.fecha==='string'?new Date(d.fecha):null;
    document.getElementById('premiumVistaFecha').textContent=fecha&&Number.isFinite(fecha.getTime())?fecha.toLocaleString('es-EC'):'Fecha no disponible';
    const completas=d.almacenamientoApp?JSON.parse(d.almacenamientoApp.costos_importacion_importaciones_v1||'{}'):{};
    const totalGuardadas=new Set([...Object.keys(hist),...Object.keys(completas)]).size;
    document.getElementById('premiumVistaContenido').textContent=d.itemsImportacion.length+' partidas · '+totalGuardadas+' importaciones guardadas · '+proveedores.length+' proveedores/cotizaciones';
    document.getElementById('premiumVistaPendientes').textContent=premiumHayPendientes()?'Tienes cambios nuevos. Puedes descargar el estado actual antes de continuar.':'Puedes descargar el estado actual antes de continuar.';
    document.getElementById('premiumAceptaRestaurar').checked=false;document.getElementById('premiumAplicarRestaurar').disabled=true;document.getElementById('premiumVistaError').textContent='';
    alternarCopiasPremium(false);document.getElementById('premiumVistaRestauracion').showModal();
}
function premiumCancelarRestauracion(){premiumCopiaPendiente=null;document.getElementById('premiumVistaRestauracion').close();}
function premiumDescargarAntesRestaurar(){
    try{const d=premiumCapturarCopia('Estado antes de restaurar');proDescargarBlob('antes_de_restaurar_'+new Date().toISOString().replace(/[:.]/g,'-')+'.json',JSON.stringify(d,null,2),'application/json;charset=utf-8');document.getElementById('premiumVistaPendientes').textContent='Descarga solicitada. Verifica que el archivo esté en Descargas antes de continuar.';}catch{document.getElementById('premiumVistaError').textContent='No se pudo preparar la descarga. No se modificaron los datos.';}
}
function premiumAplicarCampos(campos){
    const permitidos=proCapturarCamposFormularioPRO();
    Object.entries(campos||{}).forEach(([id,v])=>{if(!Object.hasOwn(permitidos,id)||!premiumObjeto(v))return;const el=document.getElementById(id);if(el.type==='checkbox'||el.type==='radio')el.checked=!!v.checked;else if(Object.hasOwn(v,'value'))el.value=String(v.value??'');});
}
function premiumEscribirAlmacen(datos){
    const actuales=proCapturarAlmacenamientoApp();
    Object.keys(actuales).forEach(k=>{if(!Object.hasOwn(datos,k))mpiUserStore.removeItem(k);});
    Object.entries(datos).forEach(([k,v])=>mpiUserStore.setItem(k,v));
    if(JSON.stringify(Object.entries(proCapturarAlmacenamientoApp()).sort())!==JSON.stringify(Object.entries(datos).sort()))throw Error('No se pudo verificar el almacenamiento.');
}
function premiumRefrescar(){recalcularTodo();actualizarSelectHistorial();renderizarProveedoresPRO();renderizarUsuariosPRO();renderizarEscenariosPremium();actualizarOpcionesProductoPremium();actualizarAlertasPRO();premiumRenderBitacora();}
function premiumAplicarRestauracion(){
    if(!premiumCopiaPendiente||!document.getElementById('premiumAceptaRestaurar').checked)return;
    const boton=document.getElementById('premiumAplicarRestaurar');boton.disabled=true;
    let anterior;
    try{premiumValidarCopia(premiumCopiaPendiente);anterior=premiumGuardarRecuperacion();}
    catch(err){document.getElementById('premiumVistaError').textContent='Operación cancelada: no fue posible validar la copia o conservar el estado actual. '+err.message;boton.disabled=false;return;}
    try{
        const d=premiumCopiaPendiente;
        if(d.almacenamientoApp)premiumEscribirAlmacen(d.almacenamientoApp);
        else{
            const datos={...anterior.almacenamientoApp};
            if(d.historial!==undefined)datos.historial_embarques_ec=typeof d.historial==='string'?d.historial:JSON.stringify(d.historial);
            for(const [campo,key] of [['proveedores',PRO_KEYS.proveedores],['usuariosReferencia',PRO_KEYS.usuarios],['escenariosPremium',PRO_KEYS.escenarios],['bitacoraPremium',PREMIUM_KEYS.bitacora]])if(d[campo]!==undefined)datos[key]=JSON.stringify(d[campo]);
            premiumEscribirAlmacen(datos);
        }
        itemsImportacion=JSON.parse(JSON.stringify(d.itemsImportacion));premiumAplicarCampos(d.camposFormulario);premiumRefrescar();
        mpiUserStore.setItem('costos_importacion_borrador_pro_v1',JSON.stringify({fecha:new Date().toISOString(),itemsImportacion}));
        document.getElementById('estadoEmbarqueTexto').textContent='Copia restaurada ('+itemsImportacion.length+' partidas)';
        document.getElementById('badgeArchivoCargado').textContent='Respaldo importado';
        premiumCancelarRestauracion();premiumActualizarProteccion();
        proMostrarNotificacion('Copia restaurada','El estado anterior sigue disponible en Guardado y respaldos → Recuperar último punto de seguridad.');
    }catch(err){
        let recuperado=false;
        try{itemsImportacion=anterior.itemsImportacion;premiumAplicarCampos(anterior.camposFormulario);premiumEscribirAlmacen(anterior.almacenamientoApp);try{premiumRefrescar();}finally{premiumEscribirAlmacen(anterior.almacenamientoApp);}recuperado=true;}catch(rollbackError){}
        document.getElementById('premiumVistaError').textContent=recuperado?'No se pudo restaurar. Se recuperaron los datos anteriores.':'No se pudo completar la restauración ni verificar la recuperación. Conserva esta página abierta y descarga el punto de recuperación.';
        if(!recuperado){try{proDescargarBlob('recuperacion_emergencia.json',JSON.stringify(anterior,null,2),'application/json;charset=utf-8');}catch{}}
        boton.disabled=false;
    }
}
function premiumAbrirRecuperacion(){try{const raw=mpiUserStore.getItem(PREMIUM_RECUPERACION_KEY);if(!raw){proMostrarNotificacion('Sin punto de recuperación','Se crea antes de restaurar una copia, cargar un embarque o reemplazar uno existente.');return;}premiumPrevisualizar(JSON.parse(raw),'Punto de recuperación');}catch(err){proMostrarNotificacion('Recuperación no disponible',err.message,'error');}}
window.addEventListener('beforeunload',e=>{if(premiumHayPendientes()){e.preventDefault();e.returnValue='';}});
document.getElementById('premiumVistaRestauracion').addEventListener('cancel',()=>{premiumCopiaPendiente=null;});
window.addEventListener('load',()=>{premiumMarcarCopiaPreparada();setInterval(premiumActualizarProteccion,2000);},{once:true});
</script>


<style id="adaptacionDispositivos">
:root {--barra-movil-alto:0px}
*,*::before,*::after {box-sizing:border-box}
body {padding-bottom:calc(var(--barra-movil-alto) + 100px + env(safe-area-inset-bottom,0px))!important}
.grid > *, .flex > * {min-width:0}
img,canvas {max-width:100%}
.pro-table-wrap,.overflow-x-auto {max-width:100%;overscroll-behavior-x:contain;-webkit-overflow-scrolling:touch}
#barraMovil {padding-bottom:calc(10px + env(safe-area-inset-bottom,0px))}
#premiumCopias {bottom:calc(var(--barra-movil-alto) + 12px);right:max(12px,env(safe-area-inset-right,0px))}
#premiumCopiasContenido {max-height:calc(100vh - var(--barra-movil-alto) - 100px);max-height:calc(100dvh - var(--barra-movil-alto) - 100px);overscroll-behavior:contain}
[id^="modal"].fixed > div {max-height:92vh;max-height:92dvh;overscroll-behavior:contain;padding-bottom:env(safe-area-inset-bottom,0px)}
#premiumVistaRestauracion {max-height:90vh;max-height:90dvh}
#avisoAlmacenamiento {margin:0 auto 12px;max-width:80rem;padding:12px 16px;border:1px solid #405671;border-radius:12px;color:#dbeafe;background:#102033;font-size:13px;line-height:1.5}
@media (max-width:1023px), (pointer:coarse) {
 button,.pro-btn,.ux-nav a,.premiumRestaurar {min-height:44px;touch-action:manipulation}
 input:not([type=checkbox]):not([type=radio]):not([type=range]),select,textarea {font-size:16px!important;min-height:44px}
 input[type=range] {min-height:32px}
 .ux-nav {scroll-padding-inline:12px}
}
@media (max-width:639px) {
 #premiumCopias header {gap:10px;padding:8px 12px}
 #premiumCopiasContenido {width:min(350px,calc(100vw - 24px));max-width:calc(100vw - 24px)}
 #barraMovil > div {flex-wrap:wrap}
 #barraMovil .grid {flex-basis:180px}
 #toast {max-width:calc(100vw - 24px);right:12px;left:auto}
 #premiumVistaRestauracion {padding:16px}
 #validacionCalculoBanner {overflow-wrap:anywhere}
}
@media (max-height:500px) and (orientation:landscape) {
 .ux-nav {position:relative}
 #premiumCopias {position:relative;margin:12px auto;bottom:auto;right:auto;width:fit-content}
}
@media print {#avisoAlmacenamiento,#barraMovil {display:none!important}body{padding-bottom:0!important}}
</style>

<script id="ajustesDispositivos">
(function(){
 const barra=document.getElementById('barraMovil');
 function medirBarra(){document.documentElement.style.setProperty('--barra-movil-alto',barra ? barra.getBoundingClientRect().height+'px' : '0px');}
 if(window.ResizeObserver && barra)new ResizeObserver(medirBarra).observe(barra);
 window.addEventListener('resize',medirBarra);medirBarra();
 // Modal accessibility, keyboard navigation and background scroll locking.
 const modales=Array.from(document.querySelectorAll('[id^="modal"].fixed'));
 const focos=new WeakMap();let bloqueado=false,anteriorOverflow='';
 function sincronizar(){
   let alguno=false;
   modales.forEach(modal=>{
     const abierto=!modal.classList.contains('hidden'); alguno=alguno||abierto;
     if(abierto && modal.dataset.abierto!=='si'){
       focos.set(modal,document.activeElement);modal.dataset.abierto='si';
       modal.setAttribute('role','dialog');modal.setAttribute('aria-modal','true');
       if(!modal.hasAttribute('aria-label'))modal.setAttribute('aria-label',modal.querySelector('h2,h3')?.textContent||'Formulario');
       modal.querySelector('button,input,select')?.focus({preventScroll:true});
     }else if(!abierto && modal.dataset.abierto==='si'){
       modal.dataset.abierto='no';focos.get(modal)?.focus({preventScroll:true});
     }
   });
   if(alguno&&!bloqueado){anteriorOverflow=document.body.style.overflow;document.body.style.overflow='hidden';bloqueado=true;}
   else if(!alguno&&bloqueado){document.body.style.overflow=anteriorOverflow;bloqueado=false;}
 }
 modales.forEach(m=>new MutationObserver(sincronizar).observe(m,{attributes:true,attributeFilter:['class']}));
 document.addEventListener('keydown',e=>{
   const modal=modales.find(m=>!m.classList.contains('hidden'));if(!modal)return;
   if(e.key==='Escape'){e.preventDefault();modal.classList.add('hidden');return;}
   if(e.key!=='Tab')return;
   const controles=Array.from(modal.querySelectorAll('button,input,select,textarea,a[href],[tabindex="0"]')).filter(el=>!el.disabled&&el.getClientRects().length);
   const primero=controles[0],ultimo=controles[controles.length-1];
   if(e.shiftKey&&document.activeElement===primero){e.preventDefault();ultimo?.focus();}
   else if(!e.shiftKey&&document.activeElement===ultimo){e.preventDefault();primero?.focus();}
 });
 sincronizar();
})();
</script>
<script id="mpiGuardadoEtapa1">
// Etapa 1: guardado completo por nombre, compatible con el historial anterior.
const MPI_IMPORTACIONES_KEY = 'costos_importacion_importaciones_v1';
let mpiGuardadoActual = null;
let mpiErrorGuardado = false;
function mpiLeerImportaciones(){
    const d=JSON.parse(mpiUserStore.getItem(MPI_IMPORTACIONES_KEY)||'{}');
    if(!d || typeof d!=='object' || Array.isArray(d))throw Error('Registro de importaciones inválido');
    return d;
}
function mpiCamposImportacion(){
    const d=proCapturarCamposFormularioPRO();
    for(const id of Object.keys(d))if(/buscar|filtro|selectCotizacionesGuardadas|nombreCotizacionInput/i.test(id))delete d[id];
    return d;
}
function mpiFirmaImportacion(){return JSON.stringify({itemsImportacion,camposFormulario:mpiCamposImportacion()});}
function mpiEstadoGuardado(){
    const el=document.getElementById('mpiEstadoGuardado');if(!el)return;
    let texto='Sin guardado confirmado en esta sesión. Guarda la importación con un nombre.';
    let estado='pendiente';
    if(mpiErrorGuardado){el.textContent='No se pudo confirmar el guardado. Mantén esta página abierta y descarga una copia JSON.';el.dataset.estado='error';return;}
    if(mpiGuardadoActual){
        if(mpiGuardadoActual.firma===mpiFirmaImportacion()){
            texto='Guardado en este navegador: '+mpiGuardadoActual.nombre+' · '+new Date(mpiGuardadoActual.fecha).toLocaleString('es-EC');estado='guardado';
        }else texto='Cambios pendientes en «'+mpiGuardadoActual.nombre+'». Pulsa Guardar importación.';
    }
    if(el.textContent!==texto)el.textContent=texto;
    el.dataset.estado=estado;
}
function guardarEmbarqueLocalStorage(){
    const input=document.getElementById('nombreCotizacionInput');
    let nombre=input.value.trim();
    if(!nombre || nombre.length>120){if(typeof mpiAbrirPestana==='function')mpiAbrirPestana('historial',true);input.focus();input.scrollIntoView({block:'center',behavior:'smooth'});mostrarNotificacion('Nombre de importación','Escribe un nombre de 1 a 120 caracteres.','error');return;}
    if(!itemsImportacion.length){mostrarNotificacion('Sin productos','Agrega al menos un producto antes de guardar.','error');return;}
    if(validarDatosCalculo().length){actualizarBannerValidacion(validarDatosCalculo());mostrarNotificacion('Revisión pendiente','Corrige los errores de cálculo antes de guardar esta importación.','error');return;}
    try{
        premiumValidarPartidas(itemsImportacion);
        const registros=mpiLeerImportaciones();
        const antiguo=JSON.parse(mpiUserStore.getItem('historial_embarques_ec')||'{}');premiumValidarHistorial(antiguo);
        const normalizar=s=>s.normalize('NFC').trim().toLocaleLowerCase();
        const existente=[...Object.keys(registros),...Object.keys(antiguo)].find(k=>normalizar(k)===normalizar(nombre));
        if(existente!==undefined){
            if(!confirm('Ya existe «'+existente+'». ¿Reemplazarla? Se conservará un punto de recuperación antes del cambio.'))return;
            nombre=existente;premiumGuardarRecuperacion();
        }
        const fecha=new Date().toISOString();
        const copia={version:1,fecha,itemsImportacion:JSON.parse(JSON.stringify(itemsImportacion)),camposFormulario:mpiCamposImportacion()};
        Object.defineProperty(registros,nombre,{value:copia,enumerable:true,writable:true,configurable:true});
        const bytes=JSON.stringify(registros);
        mpiUserStore.setItem(MPI_IMPORTACIONES_KEY,bytes);
        if(mpiUserStore.getItem(MPI_IMPORTACIONES_KEY)!==bytes)throw Error('Guardado no confirmado');
        input.value=nombre;
        actualizarSelectHistorial();document.getElementById('selectCotizacionesGuardadas').value=nombre;
        mpiErrorGuardado=false;mpiGuardadoActual={nombre,fecha,firma:mpiFirmaImportacion()};mpiEstadoGuardado();
        mostrarNotificacion('Importación guardada','«'+nombre+'»: productos y campos del formulario guardados en este navegador. Descarga también un respaldo JSON.');
    }catch(err){
        mpiErrorGuardado=true;mpiGuardadoActual=null;mpiEstadoGuardado();
        document.getElementById('mpiEstadoGuardado').textContent='No se pudo confirmar el guardado. Mantén esta página abierta y descarga una copia JSON.';
        mostrarNotificacion('Guardado no confirmado','El almacenamiento puede estar lleno, bloqueado o dañado. Tus datos en pantalla siguen disponibles para descargar un respaldo.','error');
    }
}
function actualizarSelectHistorial(){
    const select=document.getElementById('selectCotizacionesGuardadas');if(!select)return;
    const anterior=select.value;select.replaceChildren();
    const inicial=document.createElement('option');inicial.value='';inicial.textContent='— Seleccionar importación guardada —';select.appendChild(inicial);
    try{
        const registros=mpiLeerImportaciones();
        const antiguo=JSON.parse(mpiUserStore.getItem('historial_embarques_ec')||'{}');premiumValidarHistorial(antiguo);
        const nombres=[...new Set([...Object.keys(registros),...Object.keys(antiguo)])].sort((a,b)=>a.localeCompare(b,'es'));
        nombres.forEach(nombre=>{
            const op=document.createElement('option');op.value=nombre;
            op.textContent=nombre+(Object.hasOwn(registros,nombre)?' · Completa':' · Anterior: solo productos');select.appendChild(op);
        });
        select.value=anterior;
    }catch(err){mostrarNotificacion('Historial no disponible','No se pudo leer el historial. Conserva tus respaldos; no se ha eliminado ningún dato.','error');}
}
function cargarEmbarqueLocalStorage(){
    const nombre=document.getElementById('selectCotizacionesGuardadas').value;if(!nombre)return;
    try{
        const registros=mpiLeerImportaciones();let d,completa=false;
        if(Object.hasOwn(registros,nombre)){d=registros[nombre];completa=true;}
        else{
            const antiguo=JSON.parse(mpiUserStore.getItem('historial_embarques_ec')||'{}');premiumValidarHistorial(antiguo);
            if(!Object.hasOwn(antiguo,nombre))throw Error('Importación no encontrada');
            d={itemsImportacion:antiguo[nombre]};
        }
        premiumValidarPartidas(d.itemsImportacion);
        if(completa && (d.version!==1 || !premiumObjeto(d.camposFormulario) || !Object.values(d.camposFormulario).every(premiumObjeto)))throw Error('Campos inválidos');
        const aviso=completa?'Se restaurarán productos y campos del formulario.':'Esta importación antigua solo incluye productos; revisa los gastos e impuestos actuales antes de calcular.';
        if(!confirm('Cargar «'+nombre+'». '+aviso+' Se conservará un punto de recuperación. ¿Continuar?'))return;
        const anterior=premiumGuardarRecuperacion();
        try{
            itemsImportacion=JSON.parse(JSON.stringify(d.itemsImportacion));
            if(completa)premiumAplicarCampos(d.camposFormulario);
            recalcularTodo();
        }catch(err){
            itemsImportacion=anterior.itemsImportacion;premiumAplicarCampos(anterior.camposFormulario);recalcularTodo();throw err;
        }
        document.getElementById('nombreCotizacionInput').value=nombre;
        document.getElementById('estadoEmbarqueTexto').textContent='Importación cargada: '+nombre+' ('+itemsImportacion.length+' productos)';
        mpiErrorGuardado=false;mpiGuardadoActual=completa?{nombre,fecha:d.fecha,firma:mpiFirmaImportacion()}:null;mpiEstadoGuardado();
        mostrarNotificacion('Importación cargada',aviso);
    }catch(err){mostrarNotificacion('No se pudo cargar','Revisa el archivo o utiliza el último punto de recuperación. '+err.message,'error');}
}
window.addEventListener('load',()=>{actualizarSelectHistorial();mpiEstadoGuardado();setInterval(mpiEstadoGuardado,1500);},{once:true});

</script>
<style id="mpiEtapa2Estilos">
#mpiNavegacion{display:flex;align-items:center;gap:8px;position:relative;padding:8px;background:#101722;border:1px solid #566070;max-width:100%}
.mpi-tabs{display:flex;overflow-x:auto;gap:6px;flex:1;scrollbar-width:thin;padding:3px}
#mpiNavegacion button{white-space:nowrap;min-height:44px;padding:10px 14px;border:1px solid #465267;border-radius:8px;background:#182334;color:#e4eaf2;font:600 14px system-ui;cursor:pointer;flex-shrink:0}
#mpiNavegacion button[aria-selected=true],#mpiVerTodo[aria-pressed=true]{background:#e4bf53;color:#101722;border-color:#e4bf53}
#mpiNavegacion button:focus-visible{outline:3px solid #fff;outline-offset:2px}
.mpi-tab-panel{min-width:0;scroll-margin-top:16px}.mpi-tab-panel[hidden]{display:none!important}.mpi-tab-panel>*+*{margin-top:18px}
.mpi-panel-title{font-size:24px;font-weight:800;color:#edcd69;margin:0 0 16px}.mpi-panel-help{color:#b9c6d7;font-size:14px}
#mpi-panel-administracion #premiumCopias{position:static!important;max-width:100%;width:100%;margin:0;box-shadow:none}
#mpi-panel-administracion #premiumCopiasContenido{width:100%;max-width:100%;max-height:none;padding:0 18px 18px;overflow:visible}
#mpi-panel-administracion #premiumCopias input[type=file]{max-width:100%}
#contenedorVistaTabla>.overflow-x-auto{max-height:65vh;max-height:65dvh;overflow:auto;overscroll-behavior:contain}
#contenedorVistaTabla table{border-collapse:separate;border-spacing:0}
#contenedorVistaTabla thead th{position:sticky;top:0;z-index:5;background:#111827!important;color:#f2d477}
#contenedorVistaTabla tr>:first-child{position:sticky;left:0;z-index:2;background:#152033!important;min-width:110px;max-width:150px;overflow-wrap:anywhere}
#contenedorVistaTabla thead tr>:first-child{z-index:7}
#contenedorVistaTabla tbody tr:hover td{background-color:#1e3149!important}
#contenedorVistaTabla th,#contenedorVistaTabla td{padding:12px 10px}
[id^="modal"].fixed{padding:12px!important;overflow-y:auto}
[id^="modal"].fixed>div{width:100%;max-height:calc(100vh - 24px)!important;max-height:calc(100dvh - 24px)!important;overflow-y:auto;overscroll-behavior:contain}
#premiumVistaRestauracion{width:min(640px,calc(100vw - 24px));max-height:calc(100dvh - 24px);overflow:auto}
@media(max-width:640px){#mpiNavegacion{flex-wrap:wrap}.mpi-tabs{flex-basis:100%}#mpiNavegacion button{padding:9px 12px}.mpi-panel-title{font-size:21px}.mpi-tab-panel>*+*{margin-top:14px}#contenedorVistaTabla>.overflow-x-auto{max-height:60dvh}#mpiBarraGuardado{padding:12px}#mpiBarraGuardado>div{flex-basis:100%}}
@media print{#mpiNavegacion{display:none!important}.mpi-tab-panel[hidden]{display:block!important}#contenedorVistaTabla>.overflow-x-auto{max-height:none;overflow:visible}#contenedorVistaTabla th,#contenedorVistaTabla tr>:first-child{position:static!important}#mpi-panel-administracion{display:none!important}}

</style>
<script id="mpiEtapa2Navegacion">
(function(){
 const labels={resumen:'Resumen',importacion:'Importación',productos:'Productos',rentabilidad:'Rentabilidad',proveedores:'Proveedores',historial:'Historial',reportes:'Reportes',administracion:'Administración'};
 let active='resumen';
 function activate(key,focus=false){
  if(key!=='todo'&&!Object.hasOwn(labels,key))return;
  active=key;
  document.querySelectorAll('.mpi-tab-panel').forEach(p=>{p.hidden=key!=='todo'&&p.dataset.tab!==key;});
  document.querySelectorAll('[data-mpi-tab]').forEach(b=>{const selected=b.dataset.mpiTab===key;b.setAttribute('aria-selected',String(selected));b.tabIndex=selected||(key==='todo'&&b.dataset.mpiTab==='resumen')?0:-1;});
  document.getElementById('mpiVerTodo').setAttribute('aria-pressed',String(key==='todo'));
  const target=document.getElementById('mpi-panel-'+key);
  if(focus&&target){target.focus({preventScroll:true});target.scrollIntoView({block:'start',behavior:'smooth'});}
  requestAnimationFrame(()=>{if(window.Chart)Object.values(Chart.instances||{}).forEach(c=>c.resize());window.dispatchEvent(new Event('resize'));});
 }
 window.mpiAbrirPestana=activate;
 function start(){
  const nav=document.querySelector('.ux-nav');if(!nav)return;
  const main=nav.parentElement;
  nav.replaceChildren();nav.id='mpiNavegacion';nav.setAttribute('aria-label','Secciones de la calculadora');
  const tabs=document.createElement('div');tabs.className='mpi-tabs';tabs.setAttribute('role','tablist');tabs.setAttribute('aria-label','Secciones');nav.appendChild(tabs);
  const panels={};
  for(const [key,label] of Object.entries(labels)){
   const b=document.createElement('button');b.type='button';b.textContent=label;b.id='mpi-tab-'+key;b.dataset.mpiTab=key;b.setAttribute('role','tab');b.setAttribute('aria-controls','mpi-panel-'+key);b.addEventListener('click',()=>activate(key));tabs.appendChild(b);
   const p=document.createElement('section');p.className='mpi-tab-panel';p.id='mpi-panel-'+key;p.dataset.tab=key;p.tabIndex=-1;p.setAttribute('role','tabpanel');p.setAttribute('aria-labelledby',b.id);
   const h=document.createElement('h2');h.className='mpi-panel-title';h.textContent=label;p.appendChild(h);main.appendChild(p);panels[key]=p;
  }
  const all=document.createElement('button');all.type='button';all.id='mpiVerTodo';all.textContent='Ver todo';all.addEventListener('click',()=>activate('todo'));nav.appendChild(all);
  tabs.addEventListener('keydown',e=>{
   if(!['ArrowRight','ArrowLeft','Home','End'].includes(e.key))return;
   const list=Array.from(tabs.children);let i=list.indexOf(document.activeElement);if(i<0)return;e.preventDefault();
   i=e.key==='Home'?0:e.key==='End'?list.length-1:(i+(e.key==='ArrowRight'?1:-1)+list.length)%list.length;list[i].click();list[i].focus();
  });
  function move(id,key){const el=document.getElementById(id);if(el)panels[key].appendChild(el);}
  // Move the existing nodes; their event listeners, values and IDs are preserved.
  move('panelProveedoresUX','proveedores');move('panelUsuariosUX','administracion');move('panelReportesUX','reportes');
  const alerts=document.getElementById('proListaAlertas')?.closest('.pro-module');if(alerts)panels.resumen.appendChild(alerts);
  const history=document.getElementById('nombreCotizacionInput')?.closest('.lg\\:col-span-4');if(history)panels.historial.appendChild(history);
  move('panelPremiumComercial','rentabilidad');move('panelPremium','rentabilidad');
  move('mpiGastos','importacion');move('mpiLogistica','importacion');move('mpiSimulacion','importacion');move('mpiMargenes','rentabilidad');move('mpiCarga','productos');
  move('mpiFiltroProductos','productos');move('contenedorVistaTarjetas','productos');move('contenedorVistaTabla','productos');
  const table=document.getElementById('contenedorVistaTabla')?.querySelector('.overflow-x-auto');
  if(table){table.tabIndex=0;table.setAttribute('role','region');table.setAttribute('aria-label','Tabla de productos: desplazamiento horizontal y vertical');}
  move('dashboardImportacion','resumen');move('panelHerramientasPRO','resumen');move('panelAuditoriaCalculo','resumen');move('premiumCopias','administracion');
  const bitacora=document.getElementById('premiumBitacoraLista')?.closest('.pro-module');if(bitacora)panels.historial.appendChild(bitacora);
  const historyHelp=document.createElement('p');historyHelp.className='mpi-panel-help';historyHelp.textContent='Selecciona una importación para recuperar sus datos. Las anteriores marcadas «solo productos» no incluyen gastos.';panels.historial.insertBefore(historyHelp,panels.historial.children[1]);
  // Remove empty layout rows left after moving modules.
  document.querySelectorAll('#panelHerramientasPRO > .grid').forEach(el=>{if(!el.children.length)el.remove();});
  const toolHeader=document.querySelector('#panelHerramientasPRO > div:first-child');if(toolHeader)toolHeader.hidden=true;
  document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',()=>{const t=document.getElementById(a.getAttribute('href').slice(1));const p=t?.closest('.mpi-tab-panel');if(p)activate(p.dataset.tab);}));
  activate(active);
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();

</script>
<style>.mpi-arancel{min-width:155px;display:grid;gap:6px;font-size:12px}.mpi-arancel label{display:grid;gap:3px}.mpi-arancel input[type=number]{width:100%;min-height:40px;background:#101722;color:#fff;border:1px solid #758196;border-radius:6px;padding:5px}.mpi-check{display:flex!important;gap:7px;align-items:center;font-size:12px;padding:6px 0}.mpi-check input{width:18px;height:18px}.mpi-arancel strong{color:#edcd69}</style>
<style id="mpiArancelCompacto">
.mpi-arancel-resumen{display:grid;gap:4px;min-width:112px;justify-items:center;font-size:12px;white-space:nowrap}
.mpi-arancel-resumen strong{color:#ffdf83}
.mpi-arancel-resumen button{border:1px solid #b6984f;border-radius:6px;padding:4px 9px;min-height:32px;background:#15243a;color:#ffe6a0;cursor:pointer;font-size:11px}
#contenedorVistaTabla tbody td{padding-top:7px;padding-bottom:7px}
#mpiDialogoArancel{position:fixed;inset:0;margin:auto;width:min(460px,calc(100vw - 24px));max-height:calc(100dvh - 24px);overflow:auto;padding:22px;border:1px solid #c6a451;border-radius:14px;background:#111c2e;color:#f7f9fc;box-sizing:border-box}
#mpiDialogoArancel::backdrop{background:rgba(0,0,0,.7)}
#mpiDialogoArancel h2{font-size:20px;color:#ffdf83;margin:0 0 8px}
#mpiArancelProducto{overflow-wrap:anywhere;color:#cfdae9;margin:0 0 18px}
#mpiDialogoArancel .mpi-arancel-campo{display:grid;gap:6px;margin:14px 0}
#mpiDialogoArancel input[type=number]{width:100%;min-height:44px;padding:8px;border-radius:7px;box-sizing:border-box}
#mpiDialogoArancel .mpi-check{font-size:14px;min-height:44px}
#mpiDialogoArancel p{font-size:13px;line-height:1.5}
#mpiArancelError{color:#ffb5ad;min-height:20px}
.mpi-arancel-acciones{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px;margin-top:14px}
.mpi-arancel-acciones button{min-height:44px;padding:8px 14px;border:1px solid #ab914e;border-radius:8px;background:#182b43;color:#fff}
.mpi-arancel-acciones button[type=submit]{background:#efc95e;color:#101722;font-weight:700}
@media print{#mpiDialogoArancel,.mpi-arancel-resumen button{display:none!important}}
</style>
<dialog id="mpiDialogoArancel" aria-labelledby="mpiArancelTitulo" aria-describedby="mpiArancelProducto" onclose="mpiCerrarArancel()">
 <form onsubmit="mpiGuardarArancel(event)">
  <h2 id="mpiArancelTitulo">Arancel y certificado de origen</h2>
  <p id="mpiArancelProducto"></p>
  <label class="mpi-arancel-campo" for="mpiArancelGeneral">Arancel general (%)<input id="mpiArancelGeneral" type="number" min="0" max="100" step="0.0001" required autofocus></label>
  <label class="mpi-arancel-campo" for="mpiArancelPreferencial">Tarifa final preferencial (%)<input id="mpiArancelPreferencial" type="number" min="0" max="100" step="0.0001" placeholder="Sin definir" aria-describedby="mpiArancelAyuda"></label>
  <p id="mpiArancelAyuda">Ingresa la tarifa final, no el descuento. Por ejemplo: general 20 %, preferencial 17 %. El 0 % es válido.</p>
  <label class="mpi-check"><input id="mpiArancelCertificado" type="checkbox">Aplicar certificado de origen</label>
  <p>Los cambios se aplican únicamente a este producto al guardar. La tabla muestra el arancel aplicado, incluido el simulador si está activo.</p>
  <p id="mpiArancelError" role="alert"></p>
  <div class="mpi-arancel-acciones"><button type="button" onclick="document.getElementById('mpiDialogoArancel').close()">Cancelar</button><button type="submit">Guardar cambios</button></div>
 </form>
</dialog>
<script id="mpiPreferenciaOrigen">
function mpiTarifaPreferencialValida(item){
 const v=item.arancelPreferencialPorcentaje;
 return v!==null && v!==undefined && String(v).trim()!=='' && Number.isFinite(Number(v)) && Number(v)>=0 && Number(v)<=100 && Number(v)<=Number(item.arancelPorcentaje);
}
function mpiTarifaAplicable(item){
 if(item.usarPreferenciaOrigen===true){
  if(!mpiTarifaPreferencialValida(item))throw Error('Tarifa preferencial inválida: debe estar entre 0 y el arancel general.');
  return Number(item.arancelPreferencialPorcentaje);
 }
 return Math.max(0,Number(item.arancelPorcentaje)||0);
}
function mpiEditarPreferencia(index,campo,valor){
 const actual=itemsImportacion[index];if(!actual)return;
 const nuevo={...actual};
 if(campo==='usarPreferenciaOrigen')nuevo[campo]=!!valor;
 else nuevo.arancelPreferencialPorcentaje=String(valor).trim()===''?null:Number(valor);
 if((campo!=='usarPreferenciaOrigen' && nuevo.arancelPreferencialPorcentaje!==null && !mpiTarifaPreferencialValida(nuevo)) || (nuevo.usarPreferenciaOrigen && !mpiTarifaPreferencialValida(nuevo))){
  mostrarNotificacion('Revisa la preferencia','Ingresa primero la tarifa final preferencial, entre 0 y el arancel general. El 0 % es válido; dejar vacío no equivale a cero.','error');recalcularTodo();return;
 }
 itemsImportacion[index]=nuevo;recalcularTodo();
}
function mpiControlArancel(index,it){
 const item=itemsImportacion[index];
 return `<div class="mpi-arancel-resumen"><strong>${Number(it.arancelEfectivoPct).toFixed(4)} %</strong><button type="button" data-mpi-arancel="${index}" onclick="mpiAbrirArancel(${index})" aria-label="Configurar arancel del producto ${index+1}">${item.usarPreferenciaOrigen===true?'Certificado activo':'Configurar'}</button></div>`;
}
let mpiEdicionArancel=null;
function mpiAbrirArancel(index){
 const item=itemsImportacion[index];if(!item)return;
 const d=document.getElementById('mpiDialogoArancel');
 const tabla=document.querySelector('#contenedorVistaTabla>.overflow-x-auto');
 mpiEdicionArancel={index,item,tabla,top:tabla?.scrollTop||0,left:tabla?.scrollLeft||0,trigger:document.activeElement};
 document.getElementById('mpiArancelProducto').textContent=`${item.codigo||''} · ${item.producto||''}`;
 document.getElementById('mpiArancelGeneral').value=Number(item.arancelPorcentaje)||0;
 document.getElementById('mpiArancelPreferencial').value=item.arancelPreferencialPorcentaje??'';
 document.getElementById('mpiArancelCertificado').checked=item.usarPreferenciaOrigen===true;
 document.getElementById('mpiArancelError').textContent='';
 d.showModal();
}
function mpiGuardarArancel(event){
 event.preventDefault();
 const ed=mpiEdicionArancel;if(!ed)return;
 const general=document.getElementById('mpiArancelGeneral').value;
 const preferencial=document.getElementById('mpiArancelPreferencial').value;
 const nuevo={...ed.item,arancelPorcentaje:Number(general),arancelPreferencialPorcentaje:preferencial.trim()===''?null:Number(preferencial),usarPreferenciaOrigen:document.getElementById('mpiArancelCertificado').checked};
 const error=document.getElementById('mpiArancelError');
 if(itemsImportacion[ed.index]!==ed.item){error.textContent='El listado cambió. Cierra esta ventana y selecciona nuevamente el producto.';return;}
 if(general.trim()===''||!Number.isFinite(nuevo.arancelPorcentaje)||nuevo.arancelPorcentaje<0||nuevo.arancelPorcentaje>100){error.textContent='El arancel general debe estar entre 0 y 100 %.';return;}
 if((nuevo.arancelPreferencialPorcentaje!==null||nuevo.usarPreferenciaOrigen)&&!mpiTarifaPreferencialValida(nuevo)){error.textContent='Ingresa una tarifa preferencial entre 0 y el arancel general. Para aplicar el certificado es obligatoria; 0 % es válido.';return;}
 itemsImportacion[ed.index]=nuevo;
 recalcularTodo();
 document.getElementById('mpiDialogoArancel').close();
}
function mpiCerrarArancel(){
 const ed=mpiEdicionArancel;mpiEdicionArancel=null;if(!ed)return;
 const trigger=ed.trigger?.isConnected?ed.trigger:document.querySelector(`[data-mpi-arancel="${ed.index}"]`);
 trigger?.focus({preventScroll:true});
 if(ed.tabla){ed.tabla.scrollTop=ed.top;ed.tabla.scrollLeft=ed.left;}
}

</script>

<style id="mpiPremiumContrasteFinal">
/* Contraste final: texto claro en superficies oscuras y tinta oscura sobre botones luminosos. */
:root{--mpi-texto:#f7f9fc;--mpi-secundario:#cbd7e5;--mpi-dorado:#ffdf83;--mpi-naranja:#ffa754;--mpi-fondo:#090e18;--mpi-borde:#64748b}
body,body .mpi-tab-panel{color:var(--mpi-texto)}
body .pro-muted,body .mpi-panel-help,body .ux-help,body .pro-kpi .k{color:var(--mpi-secundario)!important}
body input:not([type=checkbox]):not([type=radio]),body select,body textarea{color:#f6f8ff!important;background:#0d1626!important;border:1px solid #7e8ba2!important}
body input::placeholder,body textarea::placeholder{color:#adbed3!important;opacity:1}
body input:disabled,body select:disabled,body textarea:disabled{opacity:.75;color:#b9c4d4!important}
body input[type=checkbox],body input[type=radio]{accent-color:#e4bf53}
body label,body legend{color:#eed6a1}
body h1,body h2,body h3,body h4{color:#ffdf83!important}
body .pro-btn-primary,body #mpiNavegacion button[aria-selected=true],body #mpiVerTodo[aria-pressed=true],body #mpiBarraGuardado button{color:#101722!important;background:#efc95e!important;border-color:#efc95e!important}
/* Con naranja vivo se necesita texto oscuro para contraste WCAG AA. */
body [class~="bg-[#FF6B00]"],body button[class~="bg-[#FF6B00]"]{color:#161616!important}
body [class~="bg-[#FF6B00]"] span,body [class~="bg-[#FF6B00]"] svg{color:#161616!important}
body [class~="text-[#FF6B00]"]{color:#ffad70!important}
body .bg-red-950,body #validacionCalculoBanner{color:#fff2f1!important}
body #validacionCalculoBanner[data-estado=vacio]{color:#e4f6ff!important}
body [role=dialog],body dialog{color:#f6f8ff}
body .pro-btn:not(.pro-btn-primary),body #mpiNavegacion button:not([aria-selected=true]):not([aria-pressed=true]){color:#f0f3fa!important}
#contenedorVistaTabla tbody tr:hover td,#contenedorVistaTabla tbody tr:hover th{background:#20344c!important;color:#fff!important}
#contenedorVistaTabla thead th{color:#ffdf83!important}
#mpiEstadoGuardado[data-estado=error]{color:#ffada7!important}
#mpiEstadoGuardado[data-estado=guardado]{color:#a4f4c7!important}
#mpiNavegacion{scroll-margin-top:12px}
#mpiNavegacion button{min-height:44px}
:focus-visible{outline:3px solid #ffdf83!important;outline-offset:2px}
#mpiNavegacion button{transition:none!important}
#toastNotification{position:fixed!important;top:auto!important;left:auto!important;right:16px!important;bottom:16px!important;max-width:min(430px,calc(100vw - 24px))!important;width:calc(100vw - 24px);z-index:80}
@media(max-width:640px){#toastNotification{right:12px!important;bottom:calc(var(--barra-movil-alto,0px) + 12px)!important}}
@media(max-width:640px){body .pro-btn{white-space:normal;overflow-wrap:anywhere}#mpi-panel-administracion .pro-table-wrap{overflow-x:auto}}
@media print{body,body *{color:#151515!important;background-color:white!important;text-shadow:none!important}#mpiNavegacion,#premiumCopias,#mpiBarraGuardado{display:none!important}}
</style>


<script id="mpiMonitoreoSesion">
(function(){
 'use strict';
 const token=<?= json_encode($_SESSION['csrf'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
 const MAX_IDLE=30*60*1000;
 let ultimaActividad=Date.now(), bloqueada=false, consultando=false;
 const canal=typeof BroadcastChannel==='function'?new BroadcastChannel('mpi-control-sesion'):null;
 function bloquear(){
   if(bloqueada)return;bloqueada=true;
   document.documentElement.replaceChildren();
   // replace detaches every control and hides any private data without reloading the cached page.
   window.location.replace('admin.php');
 }
 if(canal)canal.onmessage=(e)=>{if(e.data==='cerrar')bloquear();};
 window.addEventListener('storage',e=>{if(e.key==='__mpi_fin_sesion')bloquear();});
 for(const evento of ['pointerdown','keydown','touchstart','scroll']){
   window.addEventListener(evento,()=>{ultimaActividad=Date.now();},{passive:true});
 }
 document.querySelectorAll('form[action="admin.php"] button[name="action"][value="logout"]').forEach(b=>b.addEventListener('click',()=>{
   try{canal?.postMessage('cerrar');mpiUserStore.setItem('__mpi_fin_sesion',Date.now().toString());}catch(e){}
 }));
 async function verificar(){
   if(bloqueada||consultando)return;
   if(Date.now()-ultimaActividad>=MAX_IDLE){bloquear();return;}
   consultando=true;
   try{
     const form=new URLSearchParams({_csrf:token,actividad:Date.now()-ultimaActividad<=2*60*1000?'1':'0'});
     const res=await fetch('sesion.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},body:form.toString()});
     if(res.status===401||res.status===403)bloquear();
   }catch(err){/* Una desconexión no confirma el cierre. La siguiente verificación lo comprobará. */}
   finally{consultando=false;}
 }
 setInterval(verificar,60*1000);
 window.addEventListener('pageshow',e=>{if(e.persisted)verificar();});
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)verificar();});
})();
</script>
<section id="mpiBackupsDB" class="mpi-db-panel" aria-label="Copias guardadas en servidor">
<h2>Copias de seguridad en MariaDB</h2>
<p>Este módulo guarda una copia de la calculadora en el servidor. El guardado local anterior permanece disponible. La restauración siempre requiere vista previa y confirmación.</p>
<div class="mpi-db-row"><label>Nombre de copia <input id="mpiBackupLabel" maxlength="120" placeholder="Ej. Importación noviembre 2026"></label><button type="button" onclick="mpiGuardarEnServidor()">Guardar en servidor</button><button type="button" onclick="mpiListarCopiasServidor()">Actualizar lista</button></div><div id="mpiBackupsResult" aria-live="polite"></div>
</section><style>.mpi-business-nav{display:flex;gap:12px;flex-wrap:wrap;padding:10px 18px;background:#152236;border:1px solid #776127}.mpi-business-nav a{color:#ffdd72;font-weight:800}.mpi-db-panel{max-width:1050px;margin:25px auto;padding:20px;border:1px solid #776127;border-radius:16px;background:#101927;color:#e7eaf0}.mpi-db-panel h2{color:#ffdf83}.mpi-db-panel input{display:block;min-height:44px;width:100%;max-width:350px;background:#080f20;color:white;border:1px solid #8a9aaf;border-radius:8px;padding:8px}.mpi-db-row{display:flex;gap:12px;flex-wrap:wrap;align-items:end}.mpi-db-row button,.mpi-db-panel button{min-height:44px;background:#e7c45a;border:0;color:#141b29;padding:9px 12px;border-radius:9px;cursor:pointer;font-weight:800}.mpi-db-panel li{margin:8px 0;line-height:1.8}.mpi-db-panel li button{margin-left:8px;background:#21324b;color:white;border:1px solid #718096}@media(max-width:600px){.mpi-db-row{display:grid}.mpi-db-panel{margin:15px 8px;padding:14px}}</style>
<script src="assets/server-backups.js" defer></script>
</body>
</html>