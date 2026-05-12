<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useLocale } from '@/lib/i18n';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};

const { t } = useLocale();
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-brand-text">
                {{ t('profileForms.deleteTitle') }}
            </h2>

            <p class="mt-1 text-sm leading-6 text-brand-muted">
                {{ t('profileForms.deleteDescription') }}
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">{{ t('profileForms.removeAccount') }}</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-brand-text">
                    {{ t('profileForms.removeQuestion') }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-brand-muted">
                    {{ t('profileForms.removeWarning') }}
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        :value="t('common.password')"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        :placeholder="t('common.password')"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        {{ t('profileForms.neverMind') }}
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        {{ t('profileForms.removeAccount') }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
