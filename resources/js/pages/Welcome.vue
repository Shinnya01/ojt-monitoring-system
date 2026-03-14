<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import { LayoutDashboard, Clock3, ClipboardList } from 'lucide-vue-next';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-background text-foreground selection:bg-primary/20">
        <nav class="flex items-center justify-between p-6 lg:px-12">
            <div class="flex items-center gap-2 font-black text-xl tracking-tighter">
                <div class="size-8 bg-primary rounded-lg flex items-center justify-center text-primary-foreground">
                    <Clock3 class="size-5" />
                </div>
                INTERNTRACK
            </div>
            <div class="flex items-center gap-3">
                <template v-if="$page.props.auth.user">
                    <Link :href="dashboard()" class="text-sm font-bold hover:text-primary transition-colors">Dashboard</Link>
                </template>
                <template v-else>
                    <Link :href="login()" class="text-sm font-bold hover:text-primary transition-colors">Log in</Link>
                    <Link v-if="canRegister" :href="register()" class="rounded-full bg-foreground px-5 py-2 text-sm font-bold text-background hover:bg-primary transition-colors">
                        Get Started
                    </Link>
                </template>
            </div>
        </nav>

        <main class="flex flex-col items-center justify-center px-6 pt-20 pb-32 text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary mb-8">
                <span>Enterprise Grade Intern Tracking</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black tracking-tighter max-w-3xl mb-6">
                Master your internship, <span class="text-primary">one hour at a time.</span>
            </h1>
            
            <p class="text-lg md:text-xl text-muted-foreground max-w-xl mb-10 leading-relaxed">
                The professional toolkit for logging hours, managing deliverables, and keeping your internship documentation organized.
            </p>

            <div class="flex items-center gap-4">
                <Link v-if="!$page.props.auth.user" :href="register()" class="rounded-lg bg-primary px-8 py-4 font-black text-primary-foreground hover:bg-primary/90 transition-all shadow-xl shadow-primary/20">
                    Create Your Account
                </Link>
                <Link v-else :href="dashboard()" class="rounded-lg bg-primary px-8 py-4 font-black text-primary-foreground">
                    Enter Dashboard
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-24 max-w-4xl w-full">
                <div class="p-6 rounded-2xl border border-border bg-card/50">
                    <LayoutDashboard class="size-6 text-primary mb-4" />
                    <h3 class="font-bold mb-2">Live Tracking</h3>
                    <p class="text-xs text-muted-foreground">Clock in and out with precision timing.</p>
                </div>
                <div class="p-6 rounded-2xl border border-border bg-card/50">
                    <ClipboardList class="size-6 text-primary mb-4" />
                    <h3 class="font-bold mb-2">Task Management</h3>
                    <p class="text-xs text-muted-foreground">Keep your deliverables in check.</p>
                </div>
                <div class="p-6 rounded-2xl border border-border bg-card/50">
                    <Clock3 class="size-6 text-primary mb-4" />
                    <h3 class="font-bold mb-2">Progress Overview</h3>
                    <p class="text-xs text-muted-foreground">Visualize your internship goals.</p>
                </div>
            </div>
        </main>
    </div>
</template>