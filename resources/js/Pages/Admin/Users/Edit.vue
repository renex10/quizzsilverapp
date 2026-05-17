<!-- resources/js/Pages/Admin/Users/Edit.vue -->
<template>
  <AdminLayout title="Editar Usuario">

    <template #header>
      <div class="flex items-center gap-4">
        <Link
          :href="route('admin.users.show', user.id)"
          class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600
                 transition-all duration-150"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div class="flex items-center gap-3">
          <!-- Avatar -->
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600
                      flex items-center justify-center text-white font-bold text-base shrink-0">
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800 leading-tight">Editar Usuario</h2>
            <p class="text-sm text-gray-500">{{ user.name }}</p>
          </div>
        </div>
      </div>
    </template>

    <div class="max-w-2xl">
      <form @submit.prevent="submit" class="space-y-5">

        <!-- ─── Sección: Información personal ─────────────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
              <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center
                           justify-center text-xs font-bold">1</span>
              Información personal
            </h3>
          </div>

          <div class="p-6 space-y-4">

            <!-- Nombre -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Nombre completo
                <span class="text-red-500 ml-0.5">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Nombre completo del usuario"
                  class="w-full pl-10 pr-4 py-2.5 text-sm border rounded-xl
                         focus:outline-none focus:ring-2 transition-all duration-150"
                  :class="form.errors.name
                    ? 'border-red-400 bg-red-50 focus:ring-red-200'
                    : 'border-gray-300 bg-white focus:ring-indigo-200 focus:border-indigo-400'"
                />
              </div>
              <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0
                       012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"/>
                </svg>
                {{ form.errors.name }}
              </p>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Correo electrónico
                <span class="text-red-500 ml-0.5">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2
                         0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="correo@ejemplo.com"
                  class="w-full pl-10 pr-4 py-2.5 text-sm border rounded-xl
                         focus:outline-none focus:ring-2 transition-all duration-150"
                  :class="form.errors.email
                    ? 'border-red-400 bg-red-50 focus:ring-red-200'
                    : 'border-gray-300 bg-white focus:ring-indigo-200 focus:border-indigo-400'"
                />
              </div>
              <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0
                       012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"/>
                </svg>
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Rol -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Rol
                <span class="text-red-500 ml-0.5">*</span>
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label
                  v-for="role in roles"
                  :key="role.id"
                  class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer
                         transition-all duration-150 select-none"
                  :class="form.role_id === role.id
                    ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200'
                    : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40'"
                >
                  <input
                    type="radio"
                    :value="role.id"
                    v-model="form.role_id"
                    class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 shrink-0"
                  />
                  <div>
                    <p class="text-sm font-medium text-gray-800">
                      {{ role.name === 'admin' ? '🛡️ Administrador' : '🎓 Estudiante' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                      {{ role.name === 'admin' ? 'Acceso completo al panel' : 'Solo acceso al catálogo' }}
                    </p>
                  </div>
                </label>
              </div>
              <p v-if="form.errors.role_id" class="mt-1.5 text-xs text-red-600">
                {{ form.errors.role_id }}
              </p>
            </div>

          </div>
        </div>

        <!-- ─── Sección: Cambio de contraseña ──────────────────────────── -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <span class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-md flex items-center
                             justify-center text-xs font-bold">2</span>
                Cambio de contraseña
              </h3>
              <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                Opcional
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1 ml-8">
              Dejá estos campos vacíos si no querés modificar la contraseña actual.
            </p>
          </div>

          <div class="p-6 space-y-4">

            <!-- Nueva contraseña -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Nueva contraseña
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6
                         a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                </div>
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Mínimo 8 caracteres"
                  class="w-full pl-10 pr-10 py-2.5 text-sm border rounded-xl
                         focus:outline-none focus:ring-2 transition-all duration-150"
                  :class="form.errors.password
                    ? 'border-red-400 bg-red-50 focus:ring-red-200'
                    : 'border-gray-300 bg-white focus:ring-indigo-200 focus:border-indigo-400'"
                />
                <!-- Toggle mostrar contraseña -->
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-3 flex items-center text-gray-400
                         hover:text-gray-600 transition-colors"
                >
                  <svg v-if="!showPassword" class="w-4 h-4" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943
                         9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97
                         9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242
                         4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0
                         0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0
                         01-4.132 5.411m0 0L21 21"/>
                  </svg>
                </button>
              </div>

              <!-- Indicador de fortaleza de contraseña -->
              <div v-if="form.password" class="mt-2">
                <div class="flex gap-1 mb-1">
                  <div
                    v-for="i in 4" :key="i"
                    class="h-1 flex-1 rounded-full transition-all duration-300"
                    :class="i <= passwordStrength.score
                      ? passwordStrength.color
                      : 'bg-gray-200'"
                  ></div>
                </div>
                <p class="text-xs" :class="passwordStrength.textColor">
                  {{ passwordStrength.label }}
                </p>
              </div>

              <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0
                       012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"/>
                </svg>
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Confirmar contraseña -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Confirmar nueva contraseña
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955
                         0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622
                         5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                  </svg>
                </div>
                <input
                  v-model="form.password_confirmation"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Repetí la contraseña"
                  class="w-full pl-10 pr-4 py-2.5 text-sm border rounded-xl
                         focus:outline-none focus:ring-2 transition-all duration-150"
                  :class="passwordMismatch
                    ? 'border-red-400 bg-red-50 focus:ring-red-200'
                    : passwordMatch
                    ? 'border-green-400 bg-green-50/30 focus:ring-green-200'
                    : 'border-gray-300 bg-white focus:ring-indigo-200 focus:border-indigo-400'"
                />
                <!-- Indicador de coincidencia -->
                <div class="absolute inset-y-0 right-3 flex items-center">
                  <svg v-if="passwordMatch" class="w-4 h-4 text-green-500"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7"/>
                  </svg>
                  <svg v-else-if="passwordMismatch" class="w-4 h-4 text-red-500"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </div>
              </div>
              <p v-if="passwordMismatch" class="mt-1.5 text-xs text-red-600">
                Las contraseñas no coinciden
              </p>
              <p v-else-if="passwordMatch" class="mt-1.5 text-xs text-green-600">
                ✓ Las contraseñas coinciden
              </p>
            </div>

          </div>
        </div>

        <!-- ─── Barra de acciones ───────────────────────────────────────── -->
        <div class="flex items-center justify-between bg-white rounded-2xl border
                    border-gray-200 shadow-sm px-6 py-4">

          <!-- Indicador de cambios -->
          <div class="flex items-center gap-2">
            <div
              class="w-2 h-2 rounded-full transition-colors duration-300"
              :class="form.isDirty ? 'bg-amber-400' : 'bg-gray-200'"
            ></div>
            <span class="text-xs text-gray-500">
              {{ form.isDirty ? 'Cambios sin guardar' : 'Sin cambios' }}
            </span>
          </div>

          <div class="flex items-center gap-3">
            <Link
              :href="route('admin.users.show', user.id)"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-xl
                     hover:bg-gray-50 transition-colors"
            >
              Cancelar
            </Link>
            <button
              type="submit"
              :disabled="form.processing || !form.isDirty || passwordMismatch"
              class="flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600
                     to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white
                     text-sm font-semibold rounded-xl shadow-sm hover:shadow-md
                     transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed
                     disabled:shadow-none"
            >
              <svg v-if="form.processing" class="animate-spin w-4 h-4"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                  stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 13l4 4L19 7"/>
              </svg>
              {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>
        </div>

      </form>
    </div>

  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  user:  { type: Object, required: true },
  roles: { type: Array,  default: () => [] },
});

const form = useForm({
  name:                  props.user.name,
  email:                 props.user.email,
  role_id:               props.user.role_id,
  password:              '',
  password_confirmation: '',
});

const showPassword = ref(false);

// ─── Fortaleza de contraseña ─────────────────────────────────────────────────
const passwordStrength = computed(() => {
  const pwd = form.password;
  if (!pwd) return { score: 0, label: '', color: 'bg-gray-200', textColor: 'text-gray-400' };

  let score = 0;
  if (pwd.length >= 8)                   score++;
  if (/[A-Z]/.test(pwd))                 score++;
  if (/[0-9]/.test(pwd))                 score++;
  if (/[^A-Za-z0-9]/.test(pwd))          score++;

  const levels = [
    { score: 1, label: 'Muy débil',  color: 'bg-red-500',    textColor: 'text-red-600' },
    { score: 2, label: 'Débil',      color: 'bg-orange-400',  textColor: 'text-orange-600' },
    { score: 3, label: 'Moderada',   color: 'bg-yellow-400',  textColor: 'text-yellow-600' },
    { score: 4, label: 'Fuerte',     color: 'bg-green-500',   textColor: 'text-green-600' },
  ];

  return { ...levels[score - 1], score };
});

// ─── Coincidencia de contraseñas ─────────────────────────────────────────────
const passwordMatch = computed(() =>
  form.password &&
  form.password_confirmation &&
  form.password === form.password_confirmation
);

const passwordMismatch = computed(() =>
  form.password_confirmation.length > 0 &&
  form.password !== form.password_confirmation
);

// ─── Submit ───────────────────────────────────────────────────────────────────
const submit = () => {
  form.put(route('admin.users.update', props.user.id));
};
</script>