<!-- resources/js/Pages/Admin/Users/Show.vue -->
<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout   from '@/Layouts/AdminLayout.vue';
import UserRoleBadge from '@/Components/Admin/Users/UserRoleBadge.vue';

// Props alineadas con UserController@show real
// user: { id, name, email, role, created_at }
// attempts: [{ id, exam_title, series_title, status, started_at, completed_at, percentage, passed }]
// stats: { total_attempts, approved_attempts, approval_rate }
// top_failed_questions: [{ question_text, count }]
const props = defineProps({
  user:                 { type: Object, required: true },
  attempts:             { type: Array,  default: () => [] },
  stats:                { type: Object, default: () => ({}) },
  top_failed_questions: { type: Array,  default: () => [] },
});

const statusConfig = (status) => ({
  completed: { label: 'Completado', classes: 'bg-green-100 text-green-700' },
  active:    { label: 'En curso',   classes: 'bg-yellow-100 text-yellow-700' },
  pending:   { label: 'Pendiente',  classes: 'bg-gray-100 text-gray-600' },
  abandoned: { label: 'Abandonado', classes: 'bg-red-100 text-red-700' },
}[status] ?? { label: status, classes: 'bg-gray-100 text-gray-600' });
</script>

<template>
  <AdminLayout :title="user.name">

    <template #header>
      <div class="flex items-center gap-3">
        <Link
          :href="route('admin.users.index')"
          class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <h2 class="text-2xl font-bold text-gray-800">{{ user.name }}</h2>
          <p class="text-sm text-gray-500 mt-0.5">{{ user.email }}</p>
        </div>
      </div>
    </template>

    <div class="space-y-6 max-w-5xl">

      <!-- ─── Perfil del usuario ────────────────────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
          <!-- Avatar -->
          <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600
                      flex items-center justify-center text-white text-2xl font-bold shrink-0">
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
          <!-- Datos -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-1">
            <div>
              <p class="text-xs text-gray-400 uppercase tracking-wide">Nombre</p>
              <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ user.name }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 uppercase tracking-wide">Email</p>
              <p class="text-sm text-gray-700 mt-0.5 break-all">{{ user.email }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 uppercase tracking-wide">Rol</p>
              <div class="mt-1"><UserRoleBadge :role="user.role" /></div>
            </div>
            <div>
              <p class="text-xs text-gray-400 uppercase tracking-wide">Registro</p>
              <p class="text-sm text-gray-700 mt-0.5">{{ user.created_at }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Estadísticas del usuario ─────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
          <p class="text-3xl font-black text-gray-800">{{ stats.total_attempts ?? 0 }}</p>
          <p class="text-xs text-gray-500 mt-1">Completados</p>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-100 shadow-sm p-4 text-center">
          <p class="text-3xl font-black text-green-700">{{ stats.approved_attempts ?? 0 }}</p>
          <p class="text-xs text-green-600 mt-1">Aprobados</p>
        </div>
        <div class="bg-indigo-50 rounded-xl border border-indigo-100 shadow-sm p-4 text-center">
          <p class="text-3xl font-black text-indigo-700">{{ stats.approval_rate ?? 0 }}%</p>
          <p class="text-xs text-indigo-600 mt-1">Tasa de aprobación</p>
        </div>
      </div>

      <!-- ─── Historial de intentos ─────────────────────────────────────── -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4">
          Historial de evaluaciones
          <span class="text-sm font-normal text-gray-400 ml-1">({{ attempts.length }})</span>
        </h3>

        <div v-if="attempts.length === 0"
          class="text-center py-8 text-gray-400 text-sm">
          Este usuario aún no ha respondido ninguna evaluación.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-left px-3 py-2 text-xs font-medium text-gray-500 uppercase">Evaluación</th>
                <th class="text-left px-3 py-2 text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Serie</th>
                <th class="text-center px-3 py-2 text-xs font-medium text-gray-500 uppercase">Estado</th>
                <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase">Puntaje</th>
                <th class="text-right px-3 py-2 text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">Completado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="attempt in attempts" :key="attempt.id"
                class="hover:bg-gray-50 transition-colors">
                <td class="px-3 py-2.5 font-medium text-gray-800 max-w-[200px] truncate">
                  {{ attempt.exam_title }}
                </td>
                <td class="px-3 py-2.5 text-gray-500 hidden md:table-cell">
                  {{ attempt.series_title }}
                </td>
                <td class="px-3 py-2.5 text-center">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="statusConfig(attempt.status).classes"
                  >
                    {{ statusConfig(attempt.status).label }}
                  </span>
                </td>
                <td class="px-3 py-2.5 text-right font-semibold"
                  :class="{
                    'text-green-600': attempt.passed === true,
                    'text-red-600':   attempt.passed === false,
                    'text-gray-400':  attempt.passed === null,
                  }">
                  {{ attempt.percentage !== null && attempt.percentage !== undefined
                    ? attempt.percentage + '%' : '—' }}
                </td>
                <td class="px-3 py-2.5 text-right text-gray-400 text-xs hidden lg:table-cell whitespace-nowrap">
                  {{ attempt.completed_at ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ─── Preguntas más falladas ────────────────────────────────────── -->
      <div
        v-if="top_failed_questions.length > 0"
        class="bg-white rounded-xl border border-gray-100 shadow-sm p-5"
      >
        <h3 class="text-base font-semibold text-gray-800 mb-4">
          🔍 Preguntas más falladas por este usuario
        </h3>
        <div class="space-y-3">
          <div
            v-for="(q, i) in top_failed_questions"
            :key="i"
            class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 border border-gray-100"
          >
            <div class="shrink-0 w-8 h-8 rounded-full bg-red-100 text-red-700
                        flex items-center justify-center text-xs font-bold">
              {{ q.count }}x
            </div>
            <p class="text-sm text-gray-700 leading-snug">{{ q.question_text }}</p>
          </div>
        </div>
      </div>

      <!-- ─── Acciones ─────────────────────────────────────────────────── -->
      <div class="flex gap-3">
        <Link
          :href="route('admin.users.edit', user.id)"
          class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                 text-white text-sm font-semibold rounded-lg transition-colors"
        >
          ✏️ Editar usuario
        </Link>
        <Link
          :href="route('admin.users.index')"
          class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-600
                 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          ← Volver al listado
        </Link>
      </div>

    </div>
  </AdminLayout>
</template>