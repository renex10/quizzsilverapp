Ejemplos de uso
Ejemplo 1: Modal con formulario simple
vue<script setup>
import { ref } from 'vue';
import BaseModal from '@/Components/Common/BaseModal.vue';

const showModal = ref(false);
const form = ref({ name: '', email: '' });

const submit = () => {
    console.log(form.value);
    showModal.value = false;
};
</script>

<template>
    <button @click="showModal = true" class="btn">Abrir Formulario</button>

    <BaseModal
        v-model:show="showModal"
        title="Crear Usuario"
        size="md"
        variant="admin"
    >
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre</label>
                <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input v-model="form.email" type="email" class="w-full border rounded-lg px-3 py-2" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button @click="showModal = false" class="px-4 py-2 bg-gray-200 rounded-lg">
                    Cancelar
                </button>
                <button @click="submit" class="px-4 py-2 bg-admin-gradient text-white rounded-lg">
                    Guardar
                </button>
            </div>
        </template>
    </BaseModal>
</template>
Ejemplo 2: Confirmación para eliminar
vue<script setup>
import { ref } from 'vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';

const showConfirm = ref(false);
const loading = ref(false);

const deleteItem = async () => {
    loading.value = true;
    await new Promise(r => setTimeout(r, 1500)); // simulación
    loading.value = false;
    showConfirm.value = false;
};
</script>

<template>
    <button @click="showConfirm = true">Eliminar</button>

    <ConfirmModal
        v-model:show="showConfirm"
        title="¿Eliminar examen?"
        message="Esta acción eliminará el examen permanentemente. No podrás recuperarlo."
        confirm-text="Sí, eliminar"
        cancel-text="Cancelar"
        variant="danger"
        :loading="loading"
        @confirm="deleteItem"
    />
</template>
Ejemplo 3: Alerta de éxito/error
vue<script setup>
import { ref } from 'vue';
import AlertModal from '@/Components/Common/AlertModal.vue';

const showSuccess = ref(false);
const showError = ref(false);
</script>

<template>
    <AlertModal
        v-model:show="showSuccess"
        type="success"
        title="¡Guardado!"
        message="Los cambios se han guardado correctamente."
    />

    <AlertModal
        v-model:show="showError"
        type="error"
        title="Error al guardar"
        message="Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo."
        button-text="Entendido"
    />
</template>
Ejemplo 4: Wizard multipaso
vue<script setup>
import { ref } from 'vue';
import MultiStepModal from '@/Components/Common/MultiStepModal.vue';

const showWizard = ref(false);
const formData = ref({ name: '', email: '', plan: '' });

const steps = [
    { title: 'Datos', description: 'Información básica' },
    { title: 'Contacto', description: 'Email y teléfono' },
    { title: 'Plan', description: 'Selección de plan' },
];

const canProceed = (stepIndex) => {
    if (stepIndex === 0) return formData.value.name.length > 0;
    if (stepIndex === 1) return formData.value.email.includes('@');
    if (stepIndex === 2) return formData.value.plan !== '';
    return true;
};

const handleFinish = () => {
    console.log('Datos finales:', formData.value);
    showWizard.value = false;
};
</script>

<template>
    <button @click="showWizard = true">Abrir Wizard</button>

    <MultiStepModal
        v-model:show="showWizard"
        title="Registro de Usuario"
        :steps="steps"
        :can-proceed="canProceed"
        @finish="handleFinish"
    >
        <template #step-0>
            <label class="block text-sm font-medium mb-1">Nombre completo</label>
            <input v-model="formData.name" type="text" class="w-full border rounded-lg px-3 py-2" />
        </template>

        <template #step-1>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input v-model="formData.email" type="email" class="w-full border rounded-lg px-3 py-2" />
        </template>

        <template #step-2>
            <label class="block text-sm font-medium mb-2">Selecciona un plan</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input v-model="formData.plan" type="radio" value="basic" />
                    Básico
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="formData.plan" type="radio" value="pro" />
                    Pro
                </label>
            </div>
        </template>
    </MultiStepModal>
</template>