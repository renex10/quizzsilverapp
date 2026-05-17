<x-mail::message>
# ¡Examen completado, {{ $userName }}!

Has finalizado el examen **{{ $examTitle }}** de la serie **{{ $seriesTitle }}**.

## Resumen de resultados

- **Puntaje obtenido:** {{ $score }} / {{ $totalCorrect + $totalWrong }}
- **Porcentaje:** {{ $percentage }}%
- **Estado:** @if($passed) ✅ Aprobado @else ❌ Reprobado @endif
- **Respuestas correctas:** {{ $totalCorrect }}
- **Respuestas incorrectas:** {{ $totalWrong }}

@if(!$passed)
> No te desanimes. Puedes revisar los detalles y volver a intentarlo para mejorar tu puntaje.
@endif

<x-mail::button :url="$detailUrl">
Ver detalle completo
</x-mail::button>

Gracias por usar nuestra plataforma de evaluaciones.

Saludos,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>