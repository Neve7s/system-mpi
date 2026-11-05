# Lectura local de planos (sin API de pago)

En la V93.11 los planos se procesan dentro del navegador. No hay endpoint `plan_ai.php` ni clave de OpenAI que configurar.

## Cómo utilizar
1. Abre la tienda en HTTPS y entra en la calculadora de cubiertas.
2. Busca `Plano de referencia` o `Lectura local de planos`.
3. Adjunta JPG, PNG, WEBP o PDF **de hasta 8 MB**. Para PDF se analizan como máximo las primeras **3 páginas** por intento.
4. Pulsa `Leer plano en mi navegador` y consulta el diagnóstico de compatibilidad.
5. Revisa cada faldón: forma, alero, recorrido horizontal, pendiente, unidad y traslapes. Completa las medidas ausentes y **confirma explícitamente** cada una antes de incorporarlas.
6. Calcula el material y contrasta con las dimensiones de la obra y el fabricante. La IA no puede deducir medidas que no aparecen en el plano ni sustituye un profesional.

## Compatibilidad y requisitos
- La IA visual con `LanguageModel` requiere Chrome de escritorio reciente y equipo compatible. La guía oficial de Chrome indica Prompt API multimodal en Chrome 148+ de escritorio y condiciones de memoria/espacio; **no está disponible universalmente en Android, iPhone, Safari o Firefox**.
- Si no hay IA visual, el sistema intenta extracción de texto PDF y OCR local con bibliotecas libres. El reconocimiento es más limitado y puede no asociar una cota con el borde correcto.
- Las bibliotecas PDF.js y Tesseract.js, y el modelo de idioma OCR, se descargan la primera vez desde CDN externas. **No se envía el documento como solicitud de análisis a un servicio de IA de pago**, pero se necesita internet inicialmente para descargar esas bibliotecas.
- Si el navegador o la red no lo permiten, usa la introducción manual. Esta ruta nunca queda bloqueada por la IA.

## Activación de IA de Chrome
1. Usa Chrome de escritorio actualizado en Windows/macOS/Linux compatible.
2. Comprueba en Chrome DevTools: `await LanguageModel.availability({expectedInputs:[{type:'text',languages:['es']},{type:'image'}],expectedOutputs:[{type:'text',languages:['es']}]})`.
3. Si devuelve `downloadable` o `downloading`, el navegador debe descargar su modelo tras una interacción del usuario (puede requerir tiempo y espacio). Si devuelve `unavailable`, el equipo no admite esa modalidad y se debe usar OCR o ingreso manual.
4. Revisa DevTools → Network para comprobar qué bibliotecas descarga el navegador y probar un plano con dimensiones conocidas.

Fuente: https://developer.chrome.com/docs/ai/prompt-api y https://developer.chrome.com/docs/ai/get-started . La compatibilidad puede cambiar con nuevas versiones de Chrome.
