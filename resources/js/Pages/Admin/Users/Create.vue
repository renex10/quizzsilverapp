<!-- resources/js/Pages/Admin/Users/Create.vue -->
<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  roles: { type: Array, default: () => [] },
  // [{ id, name }]
});

const form = useForm({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  role_id:               '',
});

const submit = () => {
  form.post(route('admin.users.store'), {
    onError: () => {},
  });
};
</script>

<template>
  <AdminLayout title="Nuevo Usuario">

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
          <h2 class="text-2xl font-bold text-gray-800">Nuevo Usuario</h2>
          <p class="text-sm text-gray-500 mt-0.5">Crear cuenta de acceso a la plataforma</p>
        </div>
      </div>
    </template>

    <div class="max-w-lg">
      <form @submit.prevent="submit"
        class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">

        <!-- Nombre -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Nombre completo <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Ej. René Estudiante"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors"
            :class="form.errors.name ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          />
          <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Email <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.email"
            type="email"
            placeholder="correo@ejemplo.com"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors"
            :class="form.errors.email ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          />
          <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <!-- Rol -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Rol <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.role_id"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors bg-white"
            :class="form.errors.role_id ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          >
            <option value="" disabled>Seleccioná un rol</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.name === 'admin' ? '🛡️ Administrador' : '🎓 Estudiante' }}
            </option>
          </select>
          <p v-if="form.errors.role_id" class="mt-1 text-xs text-red-600">{{ form.errors.role_id }}</p>
        </div>

        <!-- Contraseña -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Contraseña <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.password"
            type="password"
            placeholder="Mínimo 8 caracteres"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors"
            :class="form.errors.password ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          />
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <!-- Confirmar contraseña -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Confirmar contraseña <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.password_confirmation"
            type="password"
            placeholder="Repetí la contraseña"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none
                   focus:ring-2 focus:ring-indigo-400 transition-colors"
            :class="form.errors.password_confirmation ? 'border-red-400 bg-red-50' : 'border-gray-300'"
          />
          <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600">
            {{ form.errors.password_confirmation }}
          </p>
        </div>

        <!-- Botones -->
        <div class="flex items-center gap-3 pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700
                   text-white text-sm font-semibold rounded-lg transition-colors
                   disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4"
              fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ form.processing ? 'Creando...' : 'Crear usuario' }}
          </button>
          <Link
            :href="route('admin.users.index')"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg
                   hover:bg-gray-50 transition-colors"
          >
            Cancelar
          </Link>
        </div>

      </form>
    </div>

  </AdminLayout>
</template>