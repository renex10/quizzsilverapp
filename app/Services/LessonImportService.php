<?php

/**
 * Servicio: LessonImportService
 * 
 * Fecha y hora de creación: 17 de mayo de 2026, 22:30 hrs (UTC-4)
 * 
 * Contexto:
 * Este servicio orquesta el proceso de importación de contenido educativo
 * (Topics y Lessons) desde archivos JSON o Markdown.
 * 
 * Flujo principal:
 * 1. Recibe el archivo y el contexto (series_id o topic_id)
 * 2. Crea un registro en la tabla lesson_imports (auditoría) con estado 'pending'
 * 3. Detecta el tipo de archivo y valida su estructura usando TopicContentValidator
 * 4. Si la validación falla: marca la importación como 'failed' y devuelve errores
 * 5. Si pasa: ejecuta una transacción de base de datos para crear el Topic y sus Lessons
 * 6. Marca la importación como 'success' con los contadores respectivos
 * 7. Devuelve el objeto LessonImport al controlador
 * 
 * Dependencias:
 * - TopicContentValidator (validador de estructura)
 * - Modelos: LessonImport, Topic, Lesson, Series
 */

namespace App\Services;

use App\Models\LessonImport;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Series;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LessonImportService
{
    protected TopicContentValidator $validator;

    public function __construct(TopicContentValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Importa contenido desde un archivo (JSON o Markdown) dentro de una serie.
     *
     * @param UploadedFile $file
     * @param Series $series
     * @param int $userId
     * @return LessonImport
     * @throws \Exception
     */
    public function importSeries(UploadedFile $file, Series $series, int $userId): LessonImport
    {
        // Crear registro de auditoría
        $import = LessonImport::create([
            'series_id' => $series->id,
            'topic_id' => null,
            'imported_by' => $userId,
            'filename' => $file->getClientOriginalName(),
            'type' => $this->detectType($file),
            'status' => 'pending',
            'validation_errors' => null,
            'topics_created' => 0,
            'lessons_created' => 0,
            'imported_at' => null,
        ]);

        try {
            // Leer y validar contenido
            $content = $this->readFileContent($file);
            $availableCategories = $this->getAvailableCategories($series);
            
            $validation = $this->validator->validate($content, 'series_full', $availableCategories);
            if (!$validation['valid']) {
                $import->markAsFailed($validation['errors']);
                throw new \Exception('Validación fallida: ' . json_encode($validation['errors']));
            }

            $data = json_decode($content, true);
            
            // Transacción para crear topics y lessons
            DB::transaction(function () use ($data, $series, $import) {
                $topicsCreated = 0;
                $lessonsCreated = 0;
                
                // Actualizar datos de la serie si se proporcionan
                if (isset($data['series'])) {
                    $series->update([
                        'title' => $data['series']['title'] ?? $series->title,
                        'difficulty' => $data['series']['difficulty'] ?? $series->difficulty,
                        'estimated_hours' => $data['series']['estimated_hours'] ?? $series->estimated_hours,
                    ]);
                }
                
                // Crear topics y lessons
                foreach ($data['topics'] as $topicData) {
                    $topic = $this->createTopic($topicData, $series->id);
                    $topicsCreated++;
                    
                    foreach ($topicData['lessons'] as $lessonData) {
                        $this->createLesson($lessonData, $topic->id);
                        $lessonsCreated++;
                    }
                }
                
                $import->markAsSuccess($topicsCreated, $lessonsCreated);
            });
            
            return $import->fresh();
            
        } catch (\Exception $e) {
            if ($import->status !== 'failed') {
                $import->markAsFailed([['message' => $e->getMessage()]]);
            }
            throw $e;
        }
    }

    /**
     * Importa contenido desde un archivo (JSON) dentro de un topic existente.
     *
     * @param UploadedFile $file
     * @param Topic $topic
     * @param int $userId
     * @return LessonImport
     * @throws \Exception
     */
    public function importTopic(UploadedFile $file, Topic $topic, int $userId): LessonImport
    {
        $import = LessonImport::create([
            'series_id' => $topic->series_id,
            'topic_id' => $topic->id,
            'imported_by' => $userId,
            'filename' => $file->getClientOriginalName(),
            'type' => $this->detectType($file),
            'status' => 'pending',
            'validation_errors' => null,
            'topics_created' => 0,
            'lessons_created' => 0,
            'imported_at' => null,
        ]);

        try {
            $content = $this->readFileContent($file);
            $availableCategories = $this->getAvailableCategories($topic->series);
            
            $validation = $this->validator->validate($content, 'topic_only', $availableCategories);
            if (!$validation['valid']) {
                $import->markAsFailed($validation['errors']);
                throw new \Exception('Validación fallida: ' . json_encode($validation['errors']));
            }

            $data = json_decode($content, true);
            
            DB::transaction(function () use ($data, $topic, $import) {
                $lessonsCreated = 0;
                
                // Si el JSON incluye datos del topic, actualizar el existente
                if (isset($data['topic'])) {
                    $topic->update([
                        'title' => $data['topic']['title'] ?? $topic->title,
                        'icon' => $data['topic']['icon'] ?? $topic->icon,
                        'color' => $data['topic']['color'] ?? $topic->color,
                        'order' => $data['topic']['order'] ?? $topic->order,
                        'is_public' => $data['topic']['is_public'] ?? $topic->is_public,
                        'exam_category' => $data['topic']['exam_category'] ?? $topic->exam_category,
                    ]);
                }
                
                // Crear lessons
                if (isset($data['lessons'])) {
                    foreach ($data['lessons'] as $lessonData) {
                        $this->createLesson($lessonData, $topic->id);
                        $lessonsCreated++;
                    }
                } elseif (isset($data['topic']['lessons'])) {
                    foreach ($data['topic']['lessons'] as $lessonData) {
                        $this->createLesson($lessonData, $topic->id);
                        $lessonsCreated++;
                    }
                }
                
                $import->markAsSuccess(0, $lessonsCreated);
            });
            
            return $import->fresh();
            
        } catch (\Exception $e) {
            if ($import->status !== 'failed') {
                $import->markAsFailed([['message' => $e->getMessage()]]);
            }
            throw $e;
        }
    }

    /**
     * Detecta el tipo de archivo (json o markdown) según extensión o contenido.
     */
    private function detectType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'json') {
            return 'json';
        }
        if (in_array($extension, ['md', 'markdown'])) {
            return 'markdown';
        }
        // Por defecto, intentar decodificar como JSON
        $content = $file->get();
        if (json_decode($content) !== null) {
            return 'json';
        }
        return 'markdown';
    }

    /**
     * Lee el contenido del archivo.
     */
    private function readFileContent(UploadedFile $file): string
    {
        if ($this->detectType($file) === 'json') {
            return $file->get();
        } else {
            // Para markdown, lo encapsulamos en un formato JSON básico para validación
            $markdown = $file->get();
            // Estructura esperada para markdown: { "lessons": [ { "content": "..." } ] }
            // Pero como es solo markdown sin estructura, lo rechazamos (solo JSON soportado en esta versión)
            throw new \Exception('Importación de Markdown sin estructura JSON no está soportada. Usa el contrato JSON.');
        }
    }

    /**
     * Obtiene las categorías disponibles del examen final de la serie.
     */
    private function getAvailableCategories(Series $series): array
    {
        $finalExam = $series->exams()->where('is_final_exam', true)->first();
        if (!$finalExam || empty($finalExam->json_schema['questions'])) {
            return [];
        }
        $categories = [];
        foreach ($finalExam->json_schema['questions'] as $question) {
            if (!empty($question['category'])) {
                $categories[] = $question['category'];
            }
        }
        return array_values(array_unique($categories));
    }

    /**
     * Crea un topic a partir de datos validados.
     */
    private function createTopic(array $data, int $seriesId): Topic
    {
        $slug = Str::slug($data['title']);
        $original = $slug;
        $count = 1;
        while (Topic::where('series_id', $seriesId)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }
        
        return Topic::create([
            'series_id' => $seriesId,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? '#6366f1',
            'order' => $data['order'] ?? 0,
            'is_public' => $data['is_public'] ?? false,
            'exam_category' => $data['exam_category'] ?? null,
        ]);
    }

    /**
     * Crea una lección a partir de datos validados.
     */
    private function createLesson(array $data, int $topicId): Lesson
    {
        $slug = Str::slug($data['title']);
        $original = $slug;
        $count = 1;
        while (Lesson::where('topic_id', $topicId)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }
        
        return Lesson::create([
            'topic_id' => $topicId,
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'],
            'order' => $data['order'] ?? 0,
            'duration_minutes' => $data['duration_minutes'] ?? 5,
            'is_preview' => $data['is_preview'] ?? false,
        ]);
    }
}