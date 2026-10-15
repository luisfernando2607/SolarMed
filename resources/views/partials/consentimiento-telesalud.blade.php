{{--
    Texto de la autorización y el consentimiento para la teleconsulta (Norma Técnica de Telesalud, MSP 2025, num. 7.2.2:
    primero la autorización para el uso de medios telemáticos; luego el consentimiento libre, específico, informado e
    inequívoco para el tratamiento de datos personales y sensibles, previo al uso del servicio).
    Uso: @include('partials.consentimiento-telesalud', ['medico' => ..., 'clinica' => ...])
--}}
<div class="space-y-3 text-sm leading-relaxed text-gray-700">
    <div>
        <p class="font-semibold text-gray-900">1. Autorización para la atención por medios telemáticos</p>
        <p>Autorizo ser atendido(a) por {{ $medico }} de {{ $clinica }} mediante videoconsulta. Entiendo que la atención se realiza a distancia por una plataforma de video externa, que el profesional puede indicarme acudir a una consulta presencial si lo considera necesario, y que puedo pedir la atención presencial en cualquier momento.</p>
    </div>
    <div>
        <p class="font-semibold text-gray-900">2. Consentimiento para el tratamiento de mis datos</p>
        <p>Consiento de forma libre, específica e informada que mis datos personales y de salud compartidos en esta teleconsulta se usen para mi atención y se registren en mi historia clínica, con la confidencialidad que exige la Ley Orgánica de Protección de Datos Personales. La clínica no graba la videoconsulta. Puedo retirar este consentimiento cuando lo desee.</p>
    </div>
    <p class="text-xs text-gray-500">Si tiene una emergencia, no espere la videoconsulta: acuda al servicio de emergencia más cercano o llame al 911.</p>
</div>
