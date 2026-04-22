<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
</script>

<template>
    <Head title="Gereg" />

    <div class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8">
            <header class="flex items-center justify-between gap-4 border-b border-border pb-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-md border border-border bg-card text-sm font-semibold">
                        G
                    </div>
                    <div>
                        <p class="text-base font-semibold">Gereg</p>
                    </div>
                </div>

                <nav class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                        <Button>Open workspace</Button>
                    </Link>
                    <template v-else>
                        <Link :href="route('login')">
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link :href="route('register')">
                            <Button>Start</Button>
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="flex flex-1 items-center py-16">
                <div class="w-full max-w-3xl">
                    <div class="space-y-6">
                        <div class="space-y-3">
                            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Simple CRM for visa agents</h1>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                                <Button class="gap-2">
                                    Open workspace
                                    <ArrowRight class="size-4" />
                                </Button>
                            </Link>
                            <template v-else>
                                <Link :href="route('register')">
                                    <Button class="gap-2">
                                        Create agency
                                        <ArrowRight class="size-4" />
                                    </Button>
                                </Link>
                                <Link :href="route('login')">
                                    <Button variant="outline">Log in</Button>
                                </Link>
                            </template>
                        </div>

                        <section class="app-panel p-5">
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <p class="text-sm font-medium">Clients</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Cases</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Tasks</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
