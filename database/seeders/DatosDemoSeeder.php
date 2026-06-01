<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\ServicioTarifario;
use Carbon\Carbon;

class DatosDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        FacturaItem::truncate();
        Factura::truncate();
        ExpedienteConsulta::truncate();
        Turno::truncate();
        Paciente::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $generalId = DB::table('especialidades')->where('codigo', 'general')->value('id');
        $ginecoId = DB::table('especialidades')->where('codigo', 'ginecologia')->value('id');
        $medicos = Medico::all();

        $medicoGeneral = $medicos->firstWhere('especialidad_id', $generalId);
        $medicoGineco = $medicos->firstWhere('especialidad_id', $ginecoId);

        $secretariaId = DB::table('users')->where('email', 'recepcion@clinica.ec')->value('id');

        $now = Carbon::now();

        $nombresMasc = ['José', 'Luis', 'Jorge', 'Carlos', 'Juan', 'Diego', 'Andrés', 'David', 'Miguel', 'Fernando', 'Ricardo', 'Manuel', 'Javier', 'Pablo', 'Santiago'];
        $nombresFem = ['María', 'Carmen', 'Ana', 'Sofía', 'Valentina', 'Isabel', 'Gabriela', 'Verónica', 'Paola', 'Silvia', 'Adriana', 'Daniela', 'Patricia', 'Liliana', 'Teresa'];
        $apellidos = ['González', 'Rodríguez', 'López', 'Martínez', 'García', 'Pérez', 'Sánchez', 'Cedeño', 'Macías', 'Alvarado', 'Vera', 'Mendoza', 'Paredes', 'Reyes', 'Flores', 'Torres', 'Salazar', 'Guerrero', 'Cruz', 'Moreno', 'Morán', 'Rivas', 'Delgado', 'Quintero', 'Solís', 'Cabrera', 'Campos', 'Cárdenas', 'Montoya', 'Cordero'];

        $ciudades = ['Guayaquil', 'Quito', 'Cuenca', 'Manta', 'Machala', 'Samborondón', 'Durán', 'Milagro'];
        $ocupaciones = ['Ingeniero', 'Médico', 'Docente', 'Abogado', 'Comerciante', 'Arquitecto', 'Contador', 'Estudiante', 'Jubilado', 'Ama de casa', 'Empresario', 'Chofer', 'Secretaria', 'Vendedor'];

        $diagnosticos = [
            ['texto' => 'Infección respiratoria aguda no especificada', 'cie10' => 'J06.9', 'tratamiento' => 'Paracetamol 500mg c/6h por 5 días. Reposo. Líquidos abundantes.'],
            ['texto' => 'Faringitis aguda', 'cie10' => 'J02.9', 'tratamiento' => 'Amoxicilina 500mg c/8h por 7 días. Antiinflamatorios PRN.'],
            ['texto' => 'Infección de vías urinarias no especificada', 'cie10' => 'N39.0', 'tratamiento' => 'Trimetoprim/sulfametoxazol 800/160mg c/12h por 5 días. Líquidos abundantes.'],
            ['texto' => 'Gastritis aguda', 'cie10' => 'K29.7', 'tratamiento' => 'Omeprazol 20mg/día en ayunas por 2 semanas. Dieta blanda. Evitar irritantes.'],
            ['texto' => 'Lumbago no especificado', 'cie10' => 'M54.5', 'tratamiento' => 'Ibuprofeno 600mg c/8h por 5 días. Reposo relativo. Fisioterapia.'],
            ['texto' => 'Cefalea tensional', 'cie10' => 'G44.2', 'tratamiento' => 'Paracetamol 500mg PRN. Evaluar factores estresantes.'],
            ['texto' => 'Dermatitis de contacto alérgica', 'cie10' => 'L23.9', 'tratamiento' => 'Hidrocortisona tópica 1% c/12h. Evitar alérgeno.'],
            ['texto' => 'Conjuntivitis aguda', 'cie10' => 'H10.3', 'tratamiento' => 'Gotas antibióticas c/6h por 7 días. Higiene ocular.'],
            ['texto' => 'Hipertensión arterial esencial', 'cie10' => 'I10', 'tratamiento' => 'Enalapril 10mg/día. Dieta hiposódica. Control en 1 mes.'],
            ['texto' => 'Diabetes mellitus tipo 2', 'cie10' => 'E11.9', 'tratamiento' => 'Metformina 850mg c/12h. Dieta. Ejercicio. Control en 2 meses.'],
            ['texto' => 'Asma bronquial', 'cie10' => 'J45', 'tratamiento' => 'Salbutamol inhalador PRN. Evaluar corticoide inhalado.'],
            ['texto' => 'Infección intestinal bacteriana', 'cie10' => 'A04.9', 'tratamiento' => 'Ciprofloxacino 500mg c/12h por 5 días. Sueroterapia.'],
            ['texto' => 'Amigdalitis aguda', 'cie10' => 'J03.9', 'tratamiento' => 'Amoxicilina 500mg c/8h por 7 días. Antiinflamatorios.'],
            ['texto' => 'Rinitis alérgica', 'cie10' => 'J30.1', 'tratamiento' => 'Loratadina 10mg/día. Lavados nasales. Evitar alérgenos.'],
            ['texto' => 'Anemia ferropénica', 'cie10' => 'D50.9', 'tratamiento' => 'Sulfato ferroso 300mg/día por 3 meses. Dieta rica en hierro.'],
        ];
        $motivos = [
            'Consulta por dolor de cabeza persistente',
            'Control médico general',
            'Dolor abdominal recurrente',
            'Fiebre y malestar general',
            'Control de presión arterial',
            'Dolor de garganta y tos',
            'Exámenes de rutina',
            'Dolor lumbar crónico',
            'Molestias digestivas',
            'Control de diabetes',
            'Revisión de resultados de exámenes',
            'Infección urinaria recurrente',
            'Dolor articular generalizado',
            'Mareos y visión borrosa',
            'Erupción cutánea con picazón',
        ];

        // ==================== PACIENTES ====================
        $pacientes = [];

        $pacientesData = [
            ['María Elena', 'Villacís Moreira', '0912345678', '1985-03-15', 'femenino', '0998765432', 'Av. Principal 123, Guayaquil', 'Guayaquil', 'Docente', 'Penicilina', 'Asma leve', 'Salbutamol PRN', null, null, null, null],
            ['Pedro José', 'Zambrano Ortiz', '0923456789', '1978-11-22', 'masculino', '0987654321', 'Calle Los Olivos 456, Guayaquil', 'Guayaquil', 'Ingeniero', null, 'Hipertensión arterial', 'Enalapril 10mg/día', null, null, null, null],
            ['Ana Cecilia', 'Mendoza Rivera', '0934567890', '1992-07-08', 'femenino', '0976543210', 'Urb. Las Palmeras Mz 5 Villa 12, Guayaquil', 'Guayaquil', 'Abogada', 'Sulfa', null, null, null, null, null, null],
            ['Luis Alberto', 'Castro Delgado', '0945678901', '1965-01-30', 'masculino', '0965432109', 'Km 5.5 Vía Daule, Guayaquil', 'Guayaquil', 'Comerciante', null, 'Diabetes tipo 2', 'Metformina 850mg c/12h', null, null, null, null],
            ['Carmen Inés', 'Sánchez Paredes', '0956789012', '2000-12-05', 'femenino', '0954321098', 'Cdla. Kennedy 789, Guayaquil', 'Guayaquil', 'Estudiante', null, null, null, null, null, null, null],
            ['Roberto', 'Jiménez Ponce', '0967890123', '1988-09-18', 'masculino', '0943210987', 'Av. Las Américas 321, Guayaquil', 'Guayaquil', 'Arquitecto', null, 'Lumbalgia crónica', 'Ibuprofeno 400mg PRN', null, null, null, null],
        ];

        foreach ($pacientesData as $d) {
            $pacientes[] = Paciente::create([
                'nombres' => $d[0], 'apellidos' => $d[1], 'cedula' => $d[2],
                'fecha_nacimiento' => $d[3], 'sexo' => $d[4], 'telefono' => $d[5],
                'direccion' => $d[6], 'ciudad' => $d[7], 'ocupacion' => $d[8],
                'alergias' => $d[9], 'antecedentes' => $d[10], 'medicamentos' => $d[11],
                'grupo_sanguineo' => $d[12], 'cirugias' => $d[13], 'enfermedades_familiares' => $d[14], 'referido_por' => $d[15],
            ]);
        }

        for ($i = 0; $i < 20; $i++) {
            $sexo = $i % 2 === 0 ? 'masculino' : 'femenino';
            $listaNombres = $sexo === 'masculino' ? $nombresMasc : $nombresFem;
            shuffle($apellidos);
            $nombres = $listaNombres[array_rand($listaNombres)];
            $apellido1 = $apellidos[0];
            $apellido2 = $apellidos[1];
            $cedula = '09' . str_pad((string)(9000000 + $i), 7, '0', STR_PAD_LEFT);
            $fechaNac = Carbon::create(1950 + rand(10, 45), rand(1, 12), rand(1, 28));
            $telefono = '099' . str_pad((string)(1000000 + $i), 7, '0', STR_PAD_LEFT);

            $pacientes[] = Paciente::create([
                'nombres' => $nombres,
                'apellidos' => "$apellido1 $apellido2",
                'cedula' => $cedula,
                'fecha_nacimiento' => $fechaNac,
                'sexo' => $sexo,
                'telefono' => $telefono,
                'direccion' => $ciudades[array_rand($ciudades)] . ' - Dirección #' . ($i + 1),
                'ciudad' => $ciudades[array_rand($ciudades)],
                'ocupacion' => $ocupaciones[array_rand($ocupaciones)],
            ]);
        }

        // ==================== TURNOS (últimos 3 meses) ====================
        $turnoModels = [];
        $facturaData = [];
        $num = 0;

        // Por cada paciente, crear 1-3 turnos en los últimos 90 días
        $diasAtras = [90, 75, 60, 50, 45, 40, 35, 30, 28, 25, 22, 20, 18, 15, 14, 12, 10, 8, 7, 5, 4, 3, 2, 1, 0];

        foreach ($pacientes as $pidx => $paciente) {
            $cantTurnos = min(rand(1, 3), count($diasAtras));

            for ($t = 0; $t < $cantTurnos; $t++) {
                $dias = array_shift($diasAtras);
                if ($dias === null) break;

                $esGineco = $paciente->sexo === 'femenino' && rand(0, 3) === 0;
                $especialidad = $esGineco ? $ginecoId : $generalId;
                $medico = $esGineco ? $medicoGineco : $medicoGeneral;
                $prefijo = $esGineco ? 'O' : 'G';

                $esHoy = $dias === 0;
                $estados = $esHoy ? ['esperando', 'esperando', 'esperando', 'completado'] : ['completado', 'completado', 'completado', 'cancelado'];
                $estado = $estados[array_rand($estados)];

                $num++;
                $idxMotivo = array_rand($motivos);
                $fecha = $now->copy()->subDays($dias)->setTime(8 + ($num % 8), ($num * 7) % 60);

                $turno = Turno::create([
                    'numero_turno' => $num,
                    'prefijo' => $prefijo,
                    'especialidad_id' => $especialidad,
                    'medico_id' => $medico->id ?? null,
                    'paciente_id' => $paciente->id,
                    'estado' => $estado,
                    'fecha' => $fecha,
                    'motivo' => $motivos[$idxMotivo],
                    'hora_registro' => $fecha->copy()->subMinutes(rand(10, 60)),
                    'hora_llamado' => $estado === 'completado' ? $fecha->copy()->addMinutes(rand(5, 20)) : null,
                    'hora_fin' => $estado === 'completado' ? $fecha->copy()->addMinutes(rand(15, 30)) : null,
                    'cedula' => $paciente->cedula,
                    'telefono' => $paciente->telefono,
                ]);

                $turnoModels[] = $turno;

                if ($estado === 'completado') {
                    $facturaData[] = ['turno_model' => $turno, 'pidx' => $pidx, 'especialidad' => $especialidad, 'medico' => $medico];
                }
            }
        }

        // Batch adicional: ~30 turnos extras para Febrero-Abril (más densidad en heatmap)
        $extrasFebAbr = [
            // Febrero (días 100-119 atrás)
            [119, 9], [117, 10], [115, 11], [113, 9], [111, 14],
            [109, 10], [107, 11], [105, 15], [103, 14], [101, 9],
            // Marzo (días 90-99 atrás)
            [99, 16], [97, 10], [95, 11], [93, 15], [91, 10],
            [89, 11], [87, 14], [85, 9], [83, 16], [81, 10],
            // Abril (días 60-80 atrás)
            [79, 15], [77, 10], [75, 11], [73, 14], [71, 9],
            [69, 10], [67, 15], [65, 11], [63, 10], [61, 14],
            [59, 9], [57, 11], [55, 10], [53, 15], [51, 16],
        ];
        foreach ($extrasFebAbr as [$dias, $hora]) {
            $num++;
            $pidx = array_rand($pacientes);
            $paciente = $pacientes[$pidx];
            $esGineco = $paciente->sexo === 'femenino' && rand(0, 3) === 0;
            $especialidad = $esGineco ? $ginecoId : $generalId;
            $medico = $esGineco ? $medicoGineco : $medicoGeneral;
            $prefijo = $esGineco ? 'O' : 'G';
            $idxMotivo = array_rand($motivos);
            $fecha = $now->copy()->subDays($dias)->setTime($hora, rand(0, 59));

            $turno = Turno::create([
                'numero_turno' => $num,
                'prefijo' => $prefijo,
                'especialidad_id' => $especialidad,
                'medico_id' => $medico->id ?? null,
                'paciente_id' => $paciente->id,
                'estado' => 'completado',
                'fecha' => $fecha,
                'motivo' => $motivos[$idxMotivo],
                'hora_registro' => $fecha->copy()->subMinutes(rand(10, 60)),
                'hora_llamado' => $fecha->copy()->addMinutes(rand(5, 20)),
                'hora_fin' => $fecha->copy()->addMinutes(rand(15, 30)),
                'cedula' => $paciente->cedula,
                'telefono' => $paciente->telefono,
            ]);

            $turnoModels[] = $turno;
            $facturaData[] = ['turno_model' => $turno, 'pidx' => $pidx, 'especialidad' => $especialidad, 'medico' => $medico];
        }

        // ==================== CONSULTAS (solo completados) ====================
        foreach ($facturaData as $idx => $fd) {
            $diag = $diagnosticos[array_rand($diagnosticos)];
            $turno = $fd['turno_model'];
            $paciente = $pacientes[$fd['pidx']];

            $anamnesisOpciones = [
                'Paciente refiere dolor de intensidad moderada desde hace varios días. Niega fiebre.',
                'Paciente acude a control periódico. Asintomático. Signos vitales normales.',
                'Cuadro de 3 días de evolución con fiebre, tos y malestar general.',
                'Dolor punzante en región lumbar que irradia a miembro inferior izquierdo.',
                'Cefalea holocraneana de 2 semanas de evolución, empeora con la luz.',
                'Paciente refiere ardor epigástrico postprandial y regurgitación frecuente.',
                'Molestias urinarias: disuria, poliaquiuria y tenesmo vesical.',
                'Paciente refiere dolor abdominal tipo cólico en hipogastrio.',
            ];

            $pa = (120 + rand(-15, 15)) . '/' . (80 + rand(-10, 10));

            ExpedienteConsulta::create([
                'paciente_id' => $paciente->id,
                'medico_id' => $fd['medico']->id ?? null,
                'especialidad_id' => $fd['especialidad'],
                'tipo_consulta' => $fd['especialidad'] === $generalId ? 'general' : 'ginecologica',
                'turno_id' => $turno->id,
                'fecha' => $turno->fecha,
                'motivo_consulta' => $turno->motivo,
                'anamnesis' => $anamnesisOpciones[array_rand($anamnesisOpciones)],
                'examen_fisico' => [
                    'pa' => $pa,
                    'fc' => (string)(70 + rand(-10, 10)),
                    'fr' => (string)(16 + rand(-3, 3)),
                    'temp' => '36.' . rand(0, 9),
                    'peso' => round(60 + rand(-15, 25), 1),
                    'talla' => round(1.60 + rand(-10, 15) / 100, 2),
                    'imc' => round((60 + rand(-15, 25)) / pow(1.60 + rand(-10, 15) / 100, 2), 1),
                ],
                'diagnostico' => $diag['texto'],
                'codigo_cie10' => $diag['cie10'],
                'tratamiento' => $diag['tratamiento'],
                'indicaciones' => 'Acudir a control según indicación. Signos de alarma: fiebre persistente, empeoramiento del cuadro.',
            ]);
        }

        // ==================== FACTURAS (3-5 por turno completado + extras) ====================
        $serviciosGeneral = ServicioTarifario::where('especialidad_id', $generalId)->get();
        $serviciosGineco = ServicioTarifario::where('especialidad_id', $ginecoId)->get();

        $serviciosExtra = [
            ['descripcion' => 'Hemograma completo', 'precio' => 12.00],
            ['descripcion' => 'Examen de orina', 'precio' => 8.00],
            ['descripcion' => 'Glucosa en ayunas', 'precio' => 5.50],
            ['descripcion' => 'Perfil lipídico', 'precio' => 18.00],
            ['descripcion' => 'Creatinina sérica', 'precio' => 7.00],
            ['descripcion' => 'Ácido úrico', 'precio' => 6.50],
            ['descripcion' => 'Radiografía de tórax', 'precio' => 25.00],
            ['descripcion' => 'Electrocardiograma', 'precio' => 20.00],
            ['descripcion' => 'Ecografía abdominal', 'precio' => 45.00],
            ['descripcion' => 'Medicamentos surtidos', 'precio' => 35.00],
            ['descripcion' => 'Inyección intramuscular', 'precio' => 5.00],
            ['descripcion' => 'Nebulización', 'precio' => 10.00],
            ['descripcion' => 'Sutura de herida', 'precio' => 30.00],
            ['descripcion' => 'Yeso/férula', 'precio' => 40.00],
        ];
        $formasPago = ['efectivo', 'transferencia', 'tarjeta'];
        $estadosFact = ['pagada', 'pagada', 'pagada', 'pagada', 'anulada'];

        $factNum = 0;
        foreach ($facturaData as $fd) {
            $servicios = $fd['especialidad'] === $generalId ? $serviciosGeneral : $serviciosGineco;
            $turno = $fd['turno_model'];
            $pacIdx = $fd['pidx'];

            $cantFacturas = rand(1, 2);
            for ($f = 0; $f < $cantFacturas; $f++) {
                $factNum++;
                $fechaFact = $turno->fecha->copy()->addDays($f === 0 ? 0 : rand(1, 7));

                // 1-4 items por factura
                $itemsCount = rand(1, 4);
                $usedServices = [];
                $subtotal = 0;

                $servicioBase = $servicios->random();
                $usedServices[] = ['servicio' => $servicioBase, 'cantidad' => 1, 'precio' => $servicioBase->precio > 0 ? $servicioBase->precio : 25.00];

                for ($i = 1; $i < $itemsCount; $i++) {
                    $extra = $serviciosExtra[array_rand($serviciosExtra)];
                    $cant = rand(1, 3);
                    $usedServices[] = ['servicio' => null, 'descripcion' => $extra['descripcion'], 'cantidad' => $cant, 'precio' => $extra['precio']];
                }

                foreach ($usedServices as $us) {
                    $subtotal += $us['precio'] * $us['cantidad'];
                }

                $descuento = rand(0, 4) === 0 ? round($subtotal * (rand(5, 15) / 100), 2) : 0;
                $total = round($subtotal - $descuento, 2);

                $estado = $estadosFact[array_rand($estadosFact)];

                $factura = Factura::create([
                    'numero_factura' => '001-001-' . str_pad((string)$factNum, 9, '0', STR_PAD_LEFT),
                    'clave_acceso' => null,
                    'numero_autorizacion' => null,
                    'ambiente_sri' => 1,
                    'paciente_id' => $pacientes[$pacIdx]->id,
                    'medico_id' => $fd['medico']->id ?? null,
                    'especialidad_id' => $fd['especialidad'],
                    'turno_id' => $f === 0 ? $turno->id : null,
                    'user_id' => $secretariaId,
                    'fecha' => $fechaFact,
                    'subtotal' => $subtotal,
                    'descuento' => $descuento,
                    'total' => $total,
                    'forma_pago' => $formasPago[array_rand($formasPago)],
                    'estado' => $estado,
                    'estado_sri' => 'pendiente',
                ]);

                foreach ($usedServices as $us) {
                    if ($us['servicio']) {
                        FacturaItem::create([
                            'factura_id' => $factura->id,
                            'servicio_id' => $us['servicio']->id,
                            'cantidad' => $us['cantidad'],
                            'precio_unitario' => $us['precio'],
                            'subtotal' => $us['precio'] * $us['cantidad'],
                            'descripcion' => $us['servicio']->nombre,
                        ]);
                    } else {
                        FacturaItem::create([
                            'factura_id' => $factura->id,
                            'servicio_id' => null,
                            'cantidad' => $us['cantidad'],
                            'precio_unitario' => $us['precio'],
                            'subtotal' => $us['precio'] * $us['cantidad'],
                            'descripcion' => $us['descripcion'],
                        ]);
                    }
                }
            }
        }

        // Facturas extras sin turno (servicios directos)
        $extraCount = rand(5, 10);
        for ($e = 0; $e < $extraCount; $e++) {
            $factNum++;
            $pacIdx = array_rand($pacientes);
            $especialidad = $pacientes[$pacIdx]->sexo === 'femenino' && rand(0, 2) === 0 ? $ginecoId : $generalId;
            $medico = $especialidad === $generalId ? $medicoGeneral : $medicoGineco;
            $servicios = $especialidad === $generalId ? $serviciosGeneral : $serviciosGineco;

            $diasAtras = rand(1, 90);
            $fecha = $now->copy()->subDays($diasAtras);

            $itemsCount = rand(1, 3);
            $subtotal = 0;
            $usedServices = [];
            for ($i = 0; $i < $itemsCount; $i++) {
                if ($i === 0) {
                    $sv = $servicios->random();
                    $usedServices[] = ['servicio' => $sv, 'cantidad' => 1, 'precio' => $sv->precio > 0 ? $sv->precio : 25.00];
                } else {
                    $extra = $serviciosExtra[array_rand($serviciosExtra)];
                    $usedServices[] = ['servicio' => null, 'descripcion' => $extra['descripcion'], 'cantidad' => rand(1, 2), 'precio' => $extra['precio']];
                }
            }
            foreach ($usedServices as $us) {
                $subtotal += $us['precio'] * $us['cantidad'];
            }
            $total = round($subtotal, 2);

            $factura = Factura::create([
                'numero_factura' => '001-001-' . str_pad((string)$factNum, 9, '0', STR_PAD_LEFT),
                'clave_acceso' => null,
                'numero_autorizacion' => null,
                'ambiente_sri' => 1,
                'paciente_id' => $pacientes[$pacIdx]->id,
                'medico_id' => $medico->id ?? null,
                'especialidad_id' => $especialidad,
                'turno_id' => null,
                'user_id' => $secretariaId,
                'fecha' => $fecha,
                'subtotal' => $subtotal,
                'descuento' => 0,
                'total' => $total,
                'forma_pago' => $formasPago[array_rand($formasPago)],
                'estado' => 'pagada',
                'estado_sri' => 'pendiente',
            ]);

            foreach ($usedServices as $us) {
                if ($us['servicio']) {
                    FacturaItem::create([
                        'factura_id' => $factura->id,
                        'servicio_id' => $us['servicio']->id,
                        'cantidad' => $us['cantidad'],
                        'precio_unitario' => $us['precio'],
                        'subtotal' => $us['precio'] * $us['cantidad'],
                        'descripcion' => $us['servicio']->nombre,
                    ]);
                } else {
                    FacturaItem::create([
                        'factura_id' => $factura->id,
                        'servicio_id' => null,
                        'cantidad' => $us['cantidad'],
                        'precio_unitario' => $us['precio'],
                        'subtotal' => $us['precio'] * $us['cantidad'],
                        'descripcion' => $us['descripcion'],
                    ]);
                }
            }
        }
    }
}
