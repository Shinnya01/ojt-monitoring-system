<script setup lang="ts">
import { computed } from 'vue';
import { dashboard, hrCounter } from '@/routes';
import { index as tasksIndex } from '@/routes/tasks';
import { Link } from '@inertiajs/vue3';

const currentYear = computed(() => new Date().getFullYear());

const footerLinks = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'HR Counter', href: hrCounter() },
    { title: 'Tasks', href: tasksIndex() },
];
</script>

<template>
    <footer class="mt-auto border-t border-border bg-muted/20">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 py-10 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <p class="font-black text-foreground tracking-tight">INTERNTRACK</p>
                <p class="text-xs text-muted-foreground">Professional internship logging and task management.</p>
            </div>

            <div class="flex flex-col gap-4 md:items-end">
                <nav class="flex items-center gap-6">
                    <Link
                        v-for="link in footerLinks"
                        :key="link.title"
                        :href="link.href"
                        class="text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground transition-colors"
                    >
                        {{ link.title }}
                    </Link>
                </nav>
                
                <div class="text-[10px] uppercase tracking-widest text-muted-foreground/60">
                    © {{ currentYear }} Internship Tracker • Built by CARL JUSTIN CARREON
                </div>
            </div>
        </div>
    </footer>
</template>