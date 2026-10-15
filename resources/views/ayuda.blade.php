<x-app-layout>
@php
    $u = auth()->user();
    // [ancla, título, ¿se muestra a este usuario?]
    $secciones = array_values(array_filter([
        ['inicio', 'Primeros pasos', true],
        ['recepcion', 'Recepción: turnos, agenda y pacientes', $u->can('turnos.ver')],
        ['facturacion', 'Facturación electrónica', $u->can('facturas.ver')],
        ['enfermeria', 'Enfermería: preparación', $u->can('preparacion.registrar')],
        ['medicos', 'Médicos: consulta y documentos', $u->can('expediente.crear')],
        ['inventario', 'Inventario', $u->can('inventario.ver')],
        ['auditoria', 'Auditoría y cumplimiento', $u->can('auditoria.ver')],
        ['administracion', 'Administración', $u->can('configuracion.editar')],
        ['preguntas', 'Preguntas frecuentes', true],
    ], fn ($s) => $s[2]));
    $h2 = 'text-lg font-semibold text-gray-900 scroll-mt-6';
    $h3 = 'text-sm font-semibold text-gray-900 mt-5 mb-1.5';
    $lista = 'list-decimal pl-5 space-y-1';
    $nota = 'mt-3 rounded-xl bg-blue-50 px-4 py-3 text-blue-900';
@endphp
<style>
    @media print {
        aside, .no-imprimir, [x-data] > div.lg\:hidden { display: none !important; }
        main { padding: 0 !important; }
        .lg\:pl-64, .lg\:\!pl-\[72px\] { padding-left: 0 !important; }
        .ayuda { max-width: none !important; }
        .ayuda section { break-inside: avoid-page; border: 0 !important; }
    }
</style>
<div class="ayuda max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5 text-sm leading-relaxed text-gray-700">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ayuda y guías de uso</h1>
            <p class="text-gray-500 mt-1">Solo se muestran las secciones de lo que su usuario puede hacer en {{ $marca->nombre() }}.</p>
        </div>
        <button type="button" onclick="window.print()" class="no-imprimir self-start inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Imprimir guía
        </button>
    </div>

    <nav class="no-imprimir rounded-2xl border border-gray-200 bg-white p-4" aria-label="Contenido">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Contenido</p>
        <ol class="grid sm:grid-cols-2 gap-x-6 gap-y-1">
            @foreach ($secciones as $i => [$ancla, $titulo])
                <li><a href="#{{ $ancla }}" class="text-blue-700 hover:underline">{{ $i + 1 }}. {{ $titulo }}</a></li>
            @endforeach
        </ol>
    </nav>

    {{-- 1. Primeros pasos --}}
    <section id="inicio" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Primeros pasos</h2>
        <h3 class="{{ $h3 }}">Ingresar por primera vez</h3>
        <ol class="{{ $lista }}">
            <li>Entre con su correo y contraseña. Si su clave fue asignada por el administrador, el sistema le pedirá definir una propia (mínimo 10 caracteres, con letras y números).</li>
            <li>Lea y acepte el <strong>acuerdo de confidencialidad</strong>. Se hace una sola vez y queda registrado.</li>
            <li>Si es administrador o médico, active la <strong>verificación en dos pasos</strong> desde su perfil: escanee el código QR con Google Authenticator (u otra app similar) y guarde los códigos de recuperación en un lugar seguro.</li>
        </ol>
        <h3 class="{{ $h3 }}">Moverse por la app</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li>El menú de la izquierda muestra solo lo que su rol puede usar. En el celular se abre con el botón ☰.</li>
            <li><strong>Buscador:</strong> presione <kbd class="px-1 border rounded text-xs">Ctrl</kbd> + <kbd class="px-1 border rounded text-xs">K</kbd> (o la tecla <kbd class="px-1 border rounded text-xs">/</kbd>) y escriba nombre, cédula o teléfono del paciente, número de factura o el nombre de una pantalla.</li>
            <li>La sesión se cierra sola tras 30 minutos sin actividad. Cierre sesión al dejar el equipo.</li>
        </ul>
        <p class="{{ $nota }}">Todo lo que se ve, descarga o modifica de un paciente queda registrado a nombre de su usuario. No comparta su clave.</p>
    </section>

    @can('turnos.ver')
    <section id="recepcion" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Recepción: turnos, agenda y pacientes</h2>
        <h3 class="{{ $h3 }}">Registrar un turno del día</h3>
        <ol class="{{ $lista }}">
            <li>Menú <strong>Registrar turno</strong>: busque al paciente por cédula; si no existe, registre sus datos.</li>
            <li>Elija especialidad y motivo, y pulse <strong>Asignar turno</strong>. El turno aparece en la <strong>Sala de espera</strong>.</li>
            <li>Los pacientes también pueden tomar su turno desde la portada pública con el código QR de la clínica.</li>
        </ol>
        <h3 class="{{ $h3 }}">Sala de espera</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Llamar</strong> pasa al paciente a "en atención"; la ✕ cancela el turno (pide confirmación).</li>
            <li>El distintivo <strong>Signos</strong> indica que enfermería ya tomó los signos vitales (en ámbar si hay valores fuera del rango habitual).</li>
        </ul>
        <h3 class="{{ $h3 }}">Agenda de citas</h3>
        <ol class="{{ $lista }}">
            <li>Menú <strong>Agenda</strong>: haga clic en un horario libre, busque al paciente (<strong>Buscar existente</strong> o <strong>+ Paciente nuevo</strong>) y pulse <strong>Agendar</strong>.</li>
            <li>Arrastre una cita para cambiarla de hora. Al abrirla puede <strong>Confirmar</strong> o <strong>Cancelar cita</strong>; el día de la cita, <strong>Registrar llegada</strong> envía al paciente a la sala de espera con su turno.</li>
            <li>Las citas <strong>"En línea"</strong> las reservan los pacientes desde la página pública: quedan pendientes hasta que usted las confirme. El panel de inicio le avisa cuántas hay.</li>
            <li>El botón <strong>WhatsApp</strong> de una cita abre el mensaje de recordatorio ya escrito para enviarlo desde su WhatsApp. Si el administrador activó el <strong>WhatsApp automático</strong>, el botón dice <strong>Enviar recordatorio por WhatsApp</strong>: el mensaje sale del número de la clínica, y además se envía solo la tarde anterior a cada cita.</li>
            <li>En <strong>WhatsApp</strong> (menú Atención) verá cada mensaje enviado, si fue entregado o leído y las respuestas: cuando el paciente pulsa <strong>Confirmar</strong> o <strong>Cancelar</strong> la cita se actualiza sola; si responde <strong>BAJA</strong> deja de recibir mensajes. Marque como <strong>Revisada</strong> cada respuesta que atienda.</li>
            <li><strong>Teleconsultas:</strong> si la telemedicina está activa, al agendar elija la modalidad <strong>Telemedicina (video)</strong>. En <strong>Telemedicina</strong> (menú Atención) abra la teleconsulta y envíe al paciente su enlace (<strong>Copiar enlace</strong> o <strong>Enviar por WhatsApp</strong>): ahí acepta el consentimiento y entra a la videollamada a su hora.</li>
        </ol>
        @can('pacientes.ver')
        <h3 class="{{ $h3 }}">Pacientes</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li>En <strong>Pacientes</strong> abra la ficha con el ícono del ojo o el nombre. Desde la ficha se edita con <strong>Editar</strong>.</li>
            <li>Marque <strong>identidad reservada</strong> (al editar) para pacientes con VIH, tuberculosis, cáncer u otras enfermedades catastróficas: en sala de espera, agenda y mensajes se mostrará la cédula en lugar del nombre.</li>
        </ul>
        @endcan
    </section>
    @endcan

    @can('facturas.ver')
    <section id="facturacion" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Facturación electrónica</h2>
        <ol class="{{ $lista }}">
            <li><strong>Facturas → Nueva factura</strong>: elija paciente, médico y servicios del tarifario. Guarde.</li>
            <li>La factura se envía sola al SRI y se autoriza en segundo plano. Su estado se ve en la lista: <em>Autorizada, En proceso, Reintentando, Devuelta, No autorizada</em>.</li>
            <li>Si quedó devuelta, abra la factura para ver el mensaje del SRI y use <strong>Reintentar envío al SRI</strong> tras corregir.</li>
            <li>Para anular una factura ya autorizada se emite una <strong>nota de crédito</strong> desde la misma factura.</li>
            <li>Al final del día, en <strong>Cierre de caja</strong> ingrese el fondo inicial y el efectivo contado (puede usar el contador de billetes y monedas); la app calcula el sobrante o faltante. Pulse <strong>Cerrar caja</strong> e imprima el <strong>Acta PDF</strong> para firmar.</li>
        </ol>
        <p class="{{ $nota }}">Para emitir, cada médico debe tener su RUC y su certificado de firma electrónica (.p12) cargados en Usuarios.</p>
    </section>
    @endcan

    @can('preparacion.registrar')
    <section id="enfermeria" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Enfermería: preparación del paciente</h2>
        <ol class="{{ $lista }}">
            <li>En la <strong>Sala de espera</strong>, pulse <strong>Tomar signos</strong> en el turno del paciente.</li>
            <li>Registre presión, pulso, frecuencia respiratoria, temperatura, saturación, peso, talla (en metros, ej. 1.62), perímetro abdominal, glucosa y hemoglobina capilar si corresponde. El IMC se calcula solo.</li>
            <li>Los valores fuera del rango habitual de un adulto se resaltan en ámbar: es solo una ayuda visual, la interpretación es del médico.</li>
            <li><strong>Guardar signos vitales</strong>. El médico los verá al atender y se cargarán en el examen físico.</li>
        </ol>
        <p class="mt-2">Su página de inicio muestra cuántos pacientes en espera aún no tienen signos.</p>
    </section>
    @endcan

    @can('expediente.crear')
    <section id="medicos" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Médicos: consulta y documentos</h2>
        <h3 class="{{ $h3 }}">Atender un turno</h3>
        <ol class="{{ $lista }}">
            <li>Desde la <strong>Sala de espera</strong> o su inicio, abra el turno. Verá alertas de alergias y la tarjeta de <strong>Preparación de enfermería</strong>.</li>
            <li>Registre la consulta siguiendo el formulario 002 del MSP: motivo, <strong>primera vez o subsecuente</strong>, enfermedad actual, revisión de órganos y sistemas, examen físico (los signos ya vienen cargados), diagnóstico CIE-10 <strong>presuntivo o definitivo</strong> y tratamiento.</li>
            <li>Escriba la consulta durante o al final de la atención: la calidad de la historia clínica se evalúa, incluido el registro oportuno.</li>
            <li>Una consulta solo la corrige quien la registró; cada corrección guarda la versión anterior.</li>
        </ol>
        <h3 class="{{ $h3 }}">Documentos desde la consulta o la ficha (menú "Nuevo documento")</h3>
        <ul class="list-disc pl-5 space-y-1.5">
            <li><strong>Receta</strong> (Resolución ACESS-2023-0030): por cada medicamento, nombre genérico (DCI) sin siglas, forma farmacéutica, concentración, vía, cantidad, dosis, frecuencia y duración. La cantidad en letras, la vigencia de 3 días y las alergias se completan solas. La app le sugiere los medicamentos que usted ya recetó. Marque si es antimicrobiano.</li>
            <li><strong>Pedido de exámenes</strong> y <strong>certificado médico</strong> (con código QR de verificación).</li>
            <li><strong>Consentimiento informado (formulario 024)</strong>: use una plantilla del procedimiento o escriba el texto; imprímalo, hágalo firmar y registre <strong>aceptación</strong>, <strong>negativa</strong> (con testigo si no quiere firmar) o luego <strong>revocatoria</strong>. Adjunte el papel firmado. Después de firmado no se puede modificar.</li>
            <li><strong>Referencia o derivación (formulario 053)</strong>: si marcó "requiere derivación" en la consulta, sale prellenada. Cuando llegue la respuesta del otro establecimiento regístrela en <strong>Referencias → Respuesta</strong>.</li>
            <li><strong>Control prenatal</strong> y <strong>archivos del paciente</strong> (laboratorios, imágenes, referencias) desde la ficha.</li>
        </ul>
        <h3 class="{{ $h3 }}">Teleconsultas (videoconsulta)</h3>
        <ol class="{{ $lista }}">
            <li>En <strong>Telemedicina</strong> abra la teleconsulta. Antes de conectarse, el paciente debe autorizar la atención por video y aceptar el tratamiento de sus datos (Norma Técnica de Telesalud, num. 7.2.2). Si no puede hacerlo en su enlace, léale el texto y use <strong>Registrar consentimiento verbal</strong>.</li>
            <li><strong>Iniciar videoconsulta</strong> abre la sala en otra pestaña. Con Jitsi público (meet.jit.si) entre antes que el paciente: le pedirá iniciar sesión para abrir la sala.</li>
            <li>Durante o después de la llamada use <strong>Registrar la consulta</strong>: queda en la historia clínica con la modalidad <em>Telemedicina</em>. Las recetas y certificados se emiten igual que en la consulta presencial.</li>
            <li>Al terminar, <strong>Finalizar teleconsulta</strong> indicando si la conexión fue efectiva, parcial o fallida (indicador del num. 7.3.4); si el paciente no entró, <strong>No se conectó</strong>.</li>
        </ol>
        <h3 class="{{ $h3 }}">Encuesta de satisfacción</h3>
        <p>Cada consulta genera una encuesta corta. En la consulta use <strong>Responder aquí</strong> para que el paciente la llene en una tableta de la clínica, o <strong>WhatsApp</strong> para enviarle el enlace. Usted ve su promedio en su inicio.</p>
        <p class="{{ $nota }}">En <strong>Mi perfil</strong> cargue la imagen de su firma. Su cédula, registro profesional ACESS y teléfono de contacto los registra el administrador en Usuarios. Todo eso aparece en recetas y certificados.</p>
    </section>
    @endcan

    @can('inventario.ver')
    <section id="inventario" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Inventario de insumos y medicamentos</h2>
        <ol class="{{ $lista }}">
            <li><strong>Inventario → Nuevo producto</strong>: nombre, tipo, presentación, unidad y stock mínimo.</li>
            <li>Al recibir mercadería, en el producto pulse <strong>+ Entrada</strong> con lote, fecha de vencimiento, cantidad y proveedor.</li>
            <li><strong>Registrar salida</strong> descuenta primero el lote que vence antes y nunca entrega un lote vencido.</li>
            <li>Tras un conteo físico use <strong>Ajustar</strong>; para productos vencidos o dañados, <strong>Dar de baja</strong>.</li>
        </ol>
        <p class="mt-2">Los movimientos no se pueden borrar ni editar (kardex). Las alertas de stock bajo y por vencer aparecen en el inicio.</p>
    </section>
    @endcan

    @can('auditoria.ver')
    <section id="auditoria" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Auditoría y cumplimiento</h2>
        <ul class="list-disc pl-5 space-y-1.5">
            <li><strong>Registro de accesos</strong>: quién vio, descargó o modificó datos, e ingresos al sistema. Cada registro está sellado; <strong>Verificar ahora</strong> confirma que nadie los alteró. <strong>Exportar CSV</strong> entrega los registros con su sello al auditor.</li>
            <li><strong>Calidad de HC</strong>: cumplimiento del formulario 002 por bloque y por médico. <strong>Generar muestra</strong> elige consultas al azar para auditarlas; en cada una confirme o corrija los bloques, agregue planes de acción y <strong>Cerrar evaluación</strong> (luego no se modifica). <strong>Informe PDF</strong> para el expediente de auditoría.</li>
            <li><strong>Planes de acción</strong>: responsable, fecha límite y evidencia para cerrarlos.</li>
            <li><strong>Solicitudes</strong>: copias de historia clínica (plazo 48 horas) y derechos del paciente sobre sus datos (15 días). La pantalla avisa las que vencen.</li>
            <li><strong>Incidentes</strong>: registre cualquier pérdida o acceso indebido a datos; la ley exige notificar a la autoridad en 5 días.</li>
            <li><strong>Estadística</strong>: producción y morbilidad (CIE-10, edad, sexo) y satisfacción de los pacientes.</li>
        </ul>
    </section>
    @endcan

    @can('configuracion.editar')
    <section id="administracion" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Administración</h2>
        <ul class="list-disc pl-5 space-y-1.5">
            <li><strong>Usuarios</strong>: cree cuentas con su rol. Si asigna una clave, la persona deberá cambiarla al entrar. Para médicos, cargue cédula, registro ACESS, RUC y certificado .p12.</li>
            <li><strong>Roles y permisos</strong>: qué puede hacer cada rol. El rol Administrador no se puede recortar. Para la empresa auditora cree un usuario con rol <strong>Auditor</strong> (solo lectura).</li>
            <li><strong>Configuración</strong>: datos y logo de la clínica, <strong>establecimiento de salud</strong> (unicódigo y ubicación para los formularios MSP), agenda y reserva en línea, encuestas, catálogo de exámenes, alertas prenatales y horarios de tareas automáticas. En la sección <strong>Telemedicina</strong> se activan las videoconsultas (una vez que la clínica tenga el permiso de ACESS para telesalud) y se elige la plataforma: Jitsi Meet o un enlace propio.</li>
            <li><strong>WhatsApp automático</strong> (Configuración → WhatsApp): necesita una cuenta de WhatsApp Business en Meta. Cree allí las plantillas que muestra la pantalla (categoría Utilidad), copie el identificador del número, el token y el app secret, guarde, use <strong>Enviar prueba</strong> y luego active el envío. Los avisos de entrega y las respuestas requieren la dirección pública HTTPS.</li>
            <li><strong>Estado del sistema</strong>: respaldos, tareas, cola, auditoría, SRI, certificados, inventario y disco. Si algo sale en rojo, el inicio del administrador lo avisa. <strong>Respaldar ahora</strong> crea una copia al instante.</li>
        </ul>
    </section>
    @endcan

    <section id="preguntas" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="{{ $h2 }}">Preguntas frecuentes</h2>
        <dl class="space-y-3 mt-3">
            <div><dt class="font-medium text-gray-900">Olvidé mi contraseña.</dt><dd>Pida al administrador que le asigne una temporal en Usuarios; al entrar definirá la suya.</dd></div>
            <div><dt class="font-medium text-gray-900">Perdí el celular con el código de verificación.</dt><dd>Use uno de los códigos de recuperación que guardó al activarla. Si no los tiene, avise al administrador.</dd></div>
            <div><dt class="font-medium text-gray-900">El paciente no puede abrir el enlace de la encuesta o de la agenda en línea desde su casa.</dt><dd>Mientras la app funcione solo en la red de la clínica, los enlaces abren únicamente aquí. Use <strong>Responder aquí</strong> en una tableta de recepción.</dd></div>
            <div><dt class="font-medium text-gray-900">Un paciente pide copia de su historia clínica.</dt><dd>Regístrelo en <strong>Solicitudes</strong> con los datos de quien la pide; debe entregarse en máximo 48 horas.</dd></div>
            <div><dt class="font-medium text-gray-900">¿Puedo borrar una consulta, un consentimiento firmado o un movimiento de inventario?</dt><dd>No. Son parte de la historia clínica o del control y quedan como constancia. Las consultas se corrigen y guardan su versión anterior.</dd></div>
        </dl>
    </section>
</div>
</x-app-layout>
