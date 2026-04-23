<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface InvitationPayload {
    id: number;
    email: string;
    name: null | string;
    company_name: null | string;
    invited_by_name: null | string;
    expires_at: null | string;
    accept_url: string;
    is_valid: boolean;
}

const props = defineProps<{
    invitation: InvitationPayload;
}>();

const form = useForm({
    name: props.invitation.name ?? '',
    password: '',
    password_confirmation: '',
});

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'Not set';

const submit = () => {
    form.put(props.invitation.accept_url, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase :title="invitation.is_valid ? 'Join your company workspace' : 'Invitation unavailable'">
        <Head title="Accept invitation" />

        <div v-if="invitation.is_valid" class="space-y-6">
            <div class="rounded-2xl border border-border/70 bg-muted/30 px-4 py-4 text-sm">
                <p class="font-medium text-foreground">{{ invitation.company_name || 'Company workspace' }}</p>
                <p class="mt-1 text-muted-foreground">{{ invitation.email }}</p>
                <p v-if="invitation.invited_by_name" class="mt-3 text-muted-foreground">
                    Invited by {{ invitation.invited_by_name }}
                </p>
                <p class="mt-1 text-muted-foreground">Link expires {{ formatDate(invitation.expires_at) }}</p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Full name</Label>
                    <Input id="name" v-model="form.name" required autofocus autocomplete="name" placeholder="Your full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Create password</Label>
                    <Input id="password" v-model="form.password" type="password" required autocomplete="new-password" placeholder="Password" />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Join workspace
                </Button>
            </form>
        </div>

        <div v-else class="space-y-5 text-center">
            <div class="rounded-2xl border border-dashed border-border/80 bg-muted/30 px-4 py-8">
                <p class="text-sm font-medium text-foreground">This invitation link is no longer valid.</p>
                <p class="mt-2 text-sm text-muted-foreground">Ask your company admin to send you a new invite.</p>
            </div>

            <TextLink :href="route('login')" class="inline-flex justify-center underline underline-offset-4">Back to log in</TextLink>
        </div>
    </AuthBase>
</template>
