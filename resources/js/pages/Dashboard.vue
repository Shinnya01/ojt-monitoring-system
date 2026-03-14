<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BriefcaseBusiness, ClipboardList, Clock3, PlayCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InternshipSetupDialog from '@/components/InternshipSetupDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateTimeForPH } from '@/lib/time';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, hrCounter } from '@/routes';
import { index as tasksIndex } from '@/routes/tasks';
import type { ActiveSession, BreadcrumbItem, InternshipSettings, TrackerSummary } from '@/types';

type Props = {
    summary: TrackerSummary;
    activeSession: ActiveSession | null;
    taskSummary: {
        pending: number;
        completed: number;
        total: number;
    };
    internshipSettings: InternshipSettings | null;
    showSetupDialog: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

const isSetupOpen = ref(props.showSetupDialog);

const cards = computed(() => [
    {
        title: 'Logged Hours',
        value: formatDuration(props.summary.liveCompletedMinutes),
        description: `${props.summary.completedSessions} completed sessions`,
        icon: Clock3,
    },
    {
        title: 'Remaining',
        value: formatDuration(props.summary.remainingMinutes),
        description: props.internshipSettings?.requiredHours
            ? `${props.internshipSettings.requiredHours} hr goal`
            : 'Finish setup to unlock target tracking',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Progress',
        value: `${props.summary.completionPercentage}%`,
        description: 'Based on your saved internship setup',
        icon: PlayCircle,
    },
    {
        title: 'Tasks',
        value: `${props.taskSummary.pending}`,
        description: `${props.taskSummary.completed} done, ${props.taskSummary.total} total`,
        icon: ClipboardList,
    },
]);

function formatDuration(totalMinutes: number): string {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
}

const formatDateTime = formatDateTimeForPH;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-8 max-w-7xl mx-auto">
            <section class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
                <Card class="border-border bg-card shadow-sm overflow-hidden">
                    <CardHeader class="border-b bg-muted/20 pb-8">
                        <div class="flex items-center gap-2 mb-2">
                            <Badge variant="outline" class="border-primary/30 text-primary font-bold tracking-tight">
                                Internship Overview
                            </Badge>
                        </div>
                        <CardTitle class="text-4xl font-black tracking-tight text-foreground">
                            See the big picture first.
                        </CardTitle>
                        <CardDescription class="text-base max-w-2xl mt-2">
                            Dashboard keeps your internship overview simple while HR Counter and Tasks handle the heavy lifting.
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent class="grid gap-4 pt-6 md:grid-cols-2 xl:grid-cols-4 bg-background/50">
                        <div
                            v-for="card in cards"
                            :key="card.title"
                            class="group rounded-2xl border border-border bg-card p-5 transition-all hover:border-primary/50 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/70">
                                        {{ card.title }}
                                    </p>
                                    <p class="text-3xl font-black text-foreground tracking-tight group-hover:text-primary transition-colors">
                                        {{ card.value }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-muted p-2 group-hover:bg-primary/10 transition-colors">
                                    <component :is="card.icon" class="size-5 text-muted-foreground group-hover:text-primary" />
                                </div>
                            </div>
                            <p class="mt-4 text-xs font-medium text-muted-foreground border-t border-border pt-3">
                                {{ card.description }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-primary/20 bg-primary/[0.02] shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                        <Clock3 class="size-32" />
                    </div>
                    
                    <CardHeader>
                        <CardTitle class="text-xl font-bold">Live Status</CardTitle>
                        <CardDescription>
                            {{ activeSession ? 'Your timer is currently running.' : 'No active work session right now.' }}
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent class="space-y-6">
                        <div class="rounded-3xl bg-foreground p-6 text-background shadow-xl">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60">System State</span>
                            <h2 class="text-5xl font-black mt-2 tracking-tighter">
                                {{ activeSession ? 'ACTIVE' : 'IDLE' }}
                            </h2>
                            <p class="mt-6 text-xs font-bold opacity-80 border-t border-background/10 pt-4 flex items-center gap-2">
                                <div v-if="activeSession" class="size-2 rounded-full bg-emerald-400 animate-pulse" />
                                {{ activeSession ? `Started ${formatDateTime(activeSession.startTime)}` : 'Ready to begin your shift?' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-border bg-card p-5 space-y-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Setup Progress</p>
                                <p class="text-sm font-bold text-foreground mt-1">
                                    {{
                                        internshipSettings?.isSetupComplete
                                            ? `Started ${internshipSettings.startDate}`
                                            : 'Setup Incomplete'
                                    }}
                                </p>
                            </div>
                            <Button 
                                variant="secondary" 
                                class="w-full font-bold shadow-sm" 
                                @click="isSetupOpen = true"
                            >
                                {{ internshipSettings?.isSetupComplete ? 'Modify Setup' : 'Complete Setup' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 md:grid-cols-2">
                <Card class="group border-border bg-card hover:border-primary/30 transition-all shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-2xl font-black tracking-tight">HR Counter</CardTitle>
                        <CardDescription>
                            Manage clock-ins, manual logs, and your full session history.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            Access the high-precision tracker for real-time monitoring and schedule adjustments.
                        </p>
                        <Button as-child class="w-full sm:w-auto font-bold px-8">
                            <Link :href="hrCounter()">Open Tracker</Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card class="group border-border bg-card hover:border-primary/30 transition-all shadow-sm">
                    <CardHeader>
                        <CardTitle class="text-2xl font-black tracking-tight">Task Board</CardTitle>
                        <CardDescription>
                            Keep track of deliverables and internship requirements.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            You currently have <span class="text-foreground font-bold">{{ taskSummary.pending }} pending</span> items to complete.
                        </p>
                        <Button as-child variant="outline" class="w-full sm:w-auto font-bold px-8 border-2">
                            <Link :href="tasksIndex()">View Tasks</Link>
                        </Button>
                    </CardContent>
                </Card>
            </section>

            <InternshipSetupDialog
                v-model:open="isSetupOpen"
                :settings="internshipSettings"
            />
        </div>
    </AppLayout>
</template>