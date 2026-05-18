<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Topic;
use App\Models\Lesson;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNormas();
        $this->seedSenalectica();
        $this->seedConduccionSegura();
    }

    // ─── Topic: Ley de Tránsito y Normativa Vial ─────────────────────────────

    private function seedNormas(): void
    {
        $topic = Topic::where('slug', 'ley-de-transito-y-normativa-vial')->first();
        if (!$topic) return;

        $lessons = [
            [
                'topic_id'         => $topic->id,
                'title'            => 'Derechos y Deberes del Conductor',
                'slug'             => 'derechos-y-deberes-del-conductor',
                'order'            => 1,
                'duration_minutes' => 5,
                'is_preview'       => true,
                'content'          => "## Derechos y Deberes del Conductor\n\nComo conductor, tenés **derechos y responsabilidades** que la ley establece claramente.\n\n### Deberes principales\n\n- Portar siempre la **licencia de conducir vigente**\n- Respetar las señales de tránsito y límites de velocidad\n- Usar el **cinturón de seguridad** en todo momento\n- No conducir bajo los efectos del alcohol o drogas\n- Ceder el paso a peatones en pasos de cebra\n\n### Documentación obligatoria\n\nAl volante debes llevar siempre:\n\n1. **Licencia de conducir** vigente y de la clase correspondiente\n2. **Permiso de circulación** del vehículo al día\n3. **SOAP** (Seguro Obligatorio de Accidentes Personales)\n4. **Revisión técnica** aprobada (cuando corresponda)\n\n> 💡 Circular sin documentación puede resultar en multas y retención del vehículo.",
            ],
            [
                'topic_id'         => $topic->id,
                'title'            => 'Límites de Velocidad',
                'slug'             => 'limites-de-velocidad',
                'order'            => 2,
                'duration_minutes' => 4,
                'is_preview'       => true,
                'content'          => "## Límites de Velocidad\n\nLos límites de velocidad varían según el tipo de vía.\n\n### Por tipo de vía\n\n| Tipo de vía | Velocidad máxima |\n|---|---|\n| Zona urbana | **40 km/h** |\n| Zona escolar / hospital | **30 km/h** |\n| Carretera doble vía sin separador | **80 km/h** |\n| Carretera con separador central | **100 km/h** |\n| Autopista | **120 km/h** |\n\n### Factores que obligan a reducir la velocidad\n\n- Condiciones climáticas adversas (lluvia, niebla)\n- Presencia de peatones o ciclistas\n- Zonas de obras\n- Visibilidad reducida\n\n> ⚠️ Exceder los límites es una infracción grave y puede implicar la suspensión de la licencia.",
            ],
            [
                'topic_id'         => $topic->id,
                'title'            => 'Prioridad de Paso y Adelantamientos',
                'slug'             => 'prioridad-de-paso-y-adelantamientos',
                'order'            => 3,
                'duration_minutes' => 6,
                'is_preview'       => false,
                'content'          => "## Prioridad de Paso\n\nLa prioridad de paso determina quién tiene el derecho a circular primero.\n\n### Reglas de prioridad\n\n1. **Señal PARE**: detención obligatoria antes de continuar\n2. **Ceda el paso**: reducir velocidad y ceder al tráfico preferente\n3. **Intersecciones sin señal**: prioridad al vehículo que viene por la derecha\n4. **Vehículos de emergencia**: ceder siempre (ambulancias, bomberos, policía)\n5. **Peatones en cruce habilitado**: siempre tienen prioridad\n\n### Adelantamientos — cuándo está prohibido\n\nNunca adelantes en:\n- Curvas sin visibilidad\n- Puentes o túneles\n- Intersecciones\n- Zonas con línea continua en el centro\n- Pendientes pronunciadas",
            ],
        ];

        foreach ($lessons as $data) {
            Lesson::create($data);
        }
    }

    // ─── Topic: Señales de Tránsito ───────────────────────────────────────────

    private function seedSenalectica(): void
    {
        $topic = Topic::where('slug', 'senales-de-transito')->first();
        if (!$topic) return;

        $lessons = [
            [
                'topic_id'         => $topic->id,
                'title'            => 'Señales Reglamentarias',
                'slug'             => 'senales-reglamentarias',
                'order'            => 1,
                'duration_minutes' => 5,
                'is_preview'       => true,
                'content'          => "## Señales Reglamentarias\n\nIndican **obligaciones o prohibiciones** que deben cumplirse.\n\n### Características\n\n- **Forma**: circular (en su mayoría) u octogonal (PARE)\n- **Color**: fondo blanco con borde y símbolo rojo, o fondo rojo\n\n### Ejemplos principales\n\n| Señal | Significado |\n|---|---|\n| PARE (octágono rojo) | Detención completa obligatoria |\n| Ceda el paso (triángulo invertido) | Dar preferencia al tráfico que cruza |\n| Velocidad máxima (círculo con número) | No superar esa velocidad |\n| No adelantar (círculo con autos) | Prohibido sobrepasar |\n| Prohibido estacionar | No detener el vehículo en esa zona |\n\n> 🚦 **Respetar las señales reglamentarias es obligatorio**. Infringirlas puede significar multa, puntos en la licencia o suspensión.",
            ],
            [
                'topic_id'         => $topic->id,
                'title'            => 'Señales Preventivas e Informativas',
                'slug'             => 'senales-preventivas-e-informativas',
                'order'            => 2,
                'duration_minutes' => 5,
                'is_preview'       => true,
                'content'          => "## Señales Preventivas\n\nAdvierten sobre condiciones peligrosas o especiales en la vía.\n\n### Características\n\n- **Forma**: rombo o cuadrado rotado 45°\n- **Color**: fondo amarillo con símbolo negro\n\n### Ejemplos\n\n- Curva peligrosa\n- Zona escolar\n- Cruce ferroviario\n- Animales en la vía\n- Trabajos en la vía\n\n---\n\n## Señales Informativas\n\nOrientan y guían al conductor.\n\n### Características\n\n- **Forma**: rectangular\n- **Color**: fondo verde (carreteras) o azul (servicios)\n\n### Ejemplos\n\n- Indicadores de ruta y destino\n- Hospital cercano\n- Bencinera\n- Servicios de emergencia",
            ],
        ];

        foreach ($lessons as $data) {
            Lesson::create($data);
        }
    }

    // ─── Topic: Conducción Segura ─────────────────────────────────────────────

    private function seedConduccionSegura(): void
    {
        $topic = Topic::where('slug', 'conduccion-segura')->first();
        if (!$topic) return;

        $lessons = [
            [
                'topic_id'         => $topic->id,
                'title'            => 'Conducción Defensiva',
                'slug'             => 'conduccion-defensiva',
                'order'            => 1,
                'duration_minutes' => 6,
                'is_preview'       => true,
                'content'          => "## Conducción Defensiva\n\nLa conducción defensiva consiste en **anticipar riesgos** y actuar antes de que se conviertan en peligro.\n\n### Principios básicos\n\n1. **Mantener la distancia de seguridad** — mínimo 2 segundos con el vehículo delantero\n2. **Observar el espejo** constantemente — cada 5-8 segundos\n3. **Anticipar las intenciones** de otros conductores y peatones\n4. **Adaptar la velocidad** a las condiciones de la vía\n5. **Evitar distracciones** dentro del vehículo\n\n### La regla de los 2 segundos\n\nElige un punto fijo en la vía. Cuando el vehículo delantero lo pase, contá: *\"mil uno, mil dos\"* — si pasás el punto antes de terminar, estás muy cerca.\n\n> 🛡️ El 90% de los accidentes se pueden evitar con una conducción anticipatoria.",
            ],
            [
                'topic_id'         => $topic->id,
                'title'            => 'Factores de Riesgo',
                'slug'             => 'factores-de-riesgo',
                'order'            => 2,
                'duration_minutes' => 7,
                'is_preview'       => true,
                'content'          => "## Factores de Riesgo en la Conducción\n\n### Alcohol y drogas\n\nEl alcohol **reduce los reflejos, la concentración y la visión** incluso en pequeñas cantidades.\n\n- Límite legal en Chile: **0,3 g/L en sangre** para conductores\n- Conductores noveles (menos de 2 años): **0,0 g/L**\n- Conducción bajo efectos de drogas es delito, sin límite permitido\n\n### Fatiga y sueño\n\nSeñales de fatiga al volante:\n- Dificultad para mantener la trayectoria\n- Parpadeo frecuente o visión borrosa\n- Cabeceos\n\n**Acción**: detener el vehículo en zona segura y descansar.\n\n### Uso del celular\n\nUsar el celular al volante **multiplica por 4 el riesgo de accidente**.\n\n- Prohibido hablar sin manos libres\n- Prohibido escribir mensajes\n- Las notificaciones también distraen aunque no las respondas",
            ],
        ];

        foreach ($lessons as $data) {
            Lesson::create($data);
        }
    }
}