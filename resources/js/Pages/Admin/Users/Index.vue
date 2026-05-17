<!-- resources/js/Pages/Admin/Users/Index.vue -->
<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AdminLayout   from '@/Layouts/AdminLayout.vue';
import UserFilters   from '@/Components/Admin/Users/UserFilters.vue';
import UserRoleBadge from '@/Components/Admin/Users/UserRoleBadge.vue';

const props = defineProps({
  users: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({ search: '', role: '' }),
  },
  roles: {
    type: Array,
    default: () => [],
  },
});

// ─── Filtros ─────────────────────────────────────────────────────────────────
const filters = ref({
  search: props.filters.search ?? '',
  role:   props.filters.role   ?? '',
});

const aplicarFiltros = () => {
  router.get(
    route('admin.users.index'),
    {
      search: filters.value.search || undefined,
      role:   filters.value.role   || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

let debounceTimer = null;
watch(() => filters.value.search, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(aplicarFiltros, 400);
});
watch(() => filters.value.role, aplicarFiltros);

// ─── Toggle activo / inactivo ─────────────────────────────────────────────────
const toggleActivo = (user) => {
  // El estado RESULTANTE es el inverso del actual
  const nuevoEstado = !user.is_active;
  const mensaje     = nuevoEstado ? 'Cuenta activada' : 'Cuenta desactivada';

  router.patch(
    route('admin.users.toggle-active', user.id),
    {},
    {
      // preserveState: false → Inertia recarga los props frescos del servidor
      // esto actualiza la tabla con el nuevo is_active
      preserveState:  false,
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({
          icon:              'success',
          title:             mensaje,
          timer:             1800,
          showConfirmButton: false,
          timerProgressBar:  true,
        });
      },
      onError: () => {
        Swal.fire({
          icon:  'error',
          title: 'Error',
          text:  'No se pudo cambiar el estado de la cuenta.',
          confirmButtonColor: '#6366f1',
        });
      },
    }
  );
};

// ─── Eliminar ─────────────────────────────────────────────────────────────────
const eliminar = async (user) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar usuario?',
    html: `Vas a eliminar a <strong>${user.name}</strong>.<br>
           <span class="text-sm text-gray-500">Solo es posible si no tiene intentos registrados.</span>`,
    showCancelButton:  true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText:  'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor:  '#6b7280',
  });

  if (!result.isConfirmed) return;

  router.delete(route('admin.users.destroy', user.id), {
    preserveScroll: true,
    onSuccess: () => Swal.fire({
      icon: 'success', title: 'Usuario eliminado',
      timer: 2000, showConfirmButton: false, timerProgressBar: true,
    }),
    onError: () => Swal.fire({
      icon: 'error', title: 'No se puede eliminar',
      text: 'El usuario tiene intentos de examen registrados.',
      confirmButtonColor: '#6366f1',
    }),
  });
};
</script>

<template>
  <AdminLayout title="Usuarios">

    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Usuarios</h2>
          <p class="text-sm text-gray-500 mt-0.5">
            {{ users.total ?? 0 }} usuario{{ (users.total ?? 0) !== 1 ? 's' : '' }} registrado{{ (users.total ?? 0) !== 1 ? 's' : '' }}
          </p>
        </div>
        <Link
          :href="route('admin.users.create')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600
                 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg
                 transition-colors shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 4v16m8-8H4"/>
          </svg>
          Nuevo usuario
        </Link>
      </div>
    </template>

    <!-- Filtros -->
    <div class="mb-5">
      <UserFilters v-model="filters" />
    </div>

    <!-- Estado vacío -->
    <div v-if="!users.data || users.data.length === 0"
      class="text-center py-20 text-gray-400">
      <div class="text-5xl mb-4">👥</div>
      <p class="text-lg font-medium text-gray-500">No hay usuarios que coincidan</p>
      <p class="text-sm mt-1">Probá cambiando los filtros de búsqueda.</p>
    </div>

    <!-- Tabla -->
    <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase">Usuario</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase hidden md:table-cell">Email</th>
            <th class="text-center px-4 py-3 font-medium text-gray-600 text-xs uppercase">Rol</th>
            <th class="text-center px-4 py-3 font-medium text-gray-600 text-xs uppercase hidden lg:table-cell">Estado</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase hidden lg:table-cell">Registro</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="user in users.data"
            :key="user.id"
            class="transition-colors"
            :class="user.is_active ? 'hover:bg-gray-50' : 'bg-gray-50/60 hover:bg-gray-100/60'"
          >
            <!-- Nombre -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center
                         text-xs font-bold shrink-0 transition-all"
                  :class="user.is_active
                    ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white'
                    : 'bg-gray-200 text-gray-400'"
                >
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <p
                    class="font-medium truncate transition-colors"
                    :class="user.is_active ? 'text-gray-800' : 'text-gray-400'"
                  >
                    {{ user.name }}
                  </p>
                  <p class="text-xs text-gray-400 truncate md:hidden">{{ user.email }}</p>
                </div>
              </div>
            </td>

            <!-- Email -->
            <td class="px-4 py-3 hidden md:table-cell"
              :class="user.is_active ? 'text-gray-500' : 'text-gray-400'">
              {{ user.email }}
            </td>

            <!-- Rol -->
            <td class="px-4 py-3 text-center">
              <UserRoleBadge :role="user.role" />
            </td>

            <!-- Estado — badge reactivo -->
            <td class="px-4 py-3 text-center hidden lg:table-cell">
              <span
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full
                       text-xs font-medium transition-all duration-300"
                :class="user.is_active
                  ? 'bg-green-100 text-green-700'
                  : 'bg-gray-100 text-gray-500'"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="user.is_active ? 'bg-green-500' : 'bg-gray-400'"
                ></span>
                {{ user.is_active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>

            <!-- Fecha -->
            <td class="px-4 py-3 text-gray-400 text-xs hidden lg:table-cell whitespace-nowrap">
              {{ user.created_at }}
            </td>

            <!-- Acciones -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-1 justify-end">

                <!-- Ver detalle -->
                <Link
                  :href="route('admin.users.show', user.id)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600
                         hover:bg-indigo-50 transition-colors"
                  title="Ver detalle"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943
                         9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </Link>

                <!-- Toggle activo / inactivo -->
                <button
                  @click="toggleActivo(user)"
                  class="p-1.5 rounded-lg transition-colors"
                  :class="user.is_active
                    ? 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50'
                    : 'text-gray-400 hover:text-green-600 hover:bg-green-50'"
                  :title="user.is_active ? 'Desactivar cuenta' : 'Activar cuenta'"
                >
                  <!-- Ícono cambia según el estado actual -->
                  <svg v-if="user.is_active" class="w-4 h-4"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072
                         M6.343 6.343a9 9 0 000 12.728m2.829-2.829a5 5 0 000-7.07"/>
                  </svg>
                  <svg v-else class="w-4 h-4"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5.636 18.364a9 9 0 010-12.728m2.829 9.9a5 5 0 010-7.072
                         m5.657 9.9a9 9 0 000-12.728m-2.829 9.9a5 5 0 000-7.072"/>
                  </svg>
                </button>

                <!-- Eliminar -->
                <button
                  @click="eliminar(user)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-red-600
                         hover:bg-red-50 transition-colors"
                  title="Eliminar usuario"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                         L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>

              </div>
            </td>

          </tr>
        </tbody>
      </table>

      <!-- Paginación -->
      <div v-if="users.last_page > 1"
        class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
        <p class="text-xs text-gray-500">
          Mostrando {{ users.from }}–{{ users.to }} de {{ users.total }} usuarios
        </p>
        <div class="flex gap-1">
          <Link
            v-for="link in users.links"
            :key="link.label"
            :href="link.url ?? '#'"
            preserve-scroll
            :class="[
              'px-3 py-1 text-xs rounded border transition-colors',
              link.active
                ? 'bg-indigo-600 text-white border-indigo-600'
                : link.url
                ? 'border-gray-300 text-gray-600 hover:bg-gray-100'
                : 'border-gray-200 text-gray-300 cursor-not-allowed pointer-events-none',
            ]"
            v-html="link.label"
          />
        </div>
      </div>

    </div>

  </AdminLayout>
</template>