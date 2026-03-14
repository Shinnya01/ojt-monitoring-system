<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Clock3, Pencil, Settings2, Square, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { dashboard, hrCounter } from '@/routes';
import { store as storeDailyNote } from '@/routes/daily-notes';
import {
    bulkStore as bulkStoreWorkSessions,
    clockIn as clockInWorkSessions,
    clockOut as clockOutWorkSessions,
    destroy as destroyWorkSession,
    store as storeWorkSessions,
    update as updateWorkSession,
} from '@/routes/work-sessions';
import InternshipSetupDialog from '@/components/InternshipSetupDialog.vue';
import InputError from '@/components/InputError.vue';
import WorkSessionCalendar from '@/components/WorkSessionCalendar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { buildHalfHourTimeOptions, formatDateTimeForPH, toPHTimeInputValue } from '@/lib/time';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ActiveSession, BreadcrumbItem, CalendarDay, DailyNote, InternshipSettings, TrackerSummary } from '@/types';

type Props = {
    summary: TrackerSummary;
    activeSession: ActiveSession | null;
    recentSessions: ActiveSession[];
    calendarDays: CalendarDay[];
    dailyNotes: DailyNote[];
    internshipSettings: InternshipSettings | null;
    manualEntryDefaults: {
        date: string;
        startTime: string;
        endTime: string;
        breakMinutes: number;
    };
    showSetupDialog: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'HR Counter', href: hrCounter() },
];

const page = usePage<{ errors?: Record<string, string> }>();
const isSetupOpen = ref(props.showSetupDialog);
const isBulkAddOpen = ref(false);
const editingSessionId = ref<number | null>(null);
const selectedCalendarDate = ref(props.manualEntryDefaults.date);
const selectedDailyNote = ref('');
const isSessionUpdateConfirmOpen = ref(false);
const isSessionDeleteConfirmOpen = ref(false);
const sessionPendingDelete = ref<ActiveSession | null>(null);
const savedDailyNotes = ref<Record<string, string>>(
    Object.fromEntries(props.dailyNotes.map((dailyNote) => [dailyNote.date, dailyNote.note ?? ''])),
);

const sessionForm = useForm({
    date: props.manualEntryDefaults.date,
    start_time: props.manualEntryDefaults.startTime,
    end_time: props.manualEntryDefaults.endTime,
    break_minutes: props.manualEntryDefaults.breakMinutes,
    notes: '',
});

const clockOutForm = useForm({
    break_minutes: props.activeSession?.breakMinutes ?? 0,
    notes: props.activeSession?.notes ?? '',
});

const bulkAddForm = useForm({
    start_date: props.internshipSettings?.startDate ?? props.manualEntryDefaults.date,
    end_date: props.manualEntryDefaults.date,
    break_minutes: props.manualEntryDefaults.breakMinutes,
    notes: 'Bulk added from internship schedule.',
});

const dailyNoteForm = useForm({
    date: props.manualEntryDefaults.date,
    note: '',
});

const clockError = computed(() => page.props.errors?.clock);
const bulkError = computed(() => page.props.errors?.bulk);
const dailyNoteError = computed(() => dailyNoteForm.errors.note || dailyNoteForm.errors.date);
const timeOptions = buildHalfHourTimeOptions();

watch(
    selectedCalendarDate,
    (value) => {
        sessionForm.date = value;
        dailyNoteForm.date = value;
        selectedDailyNote.value = savedDailyNotes.value[value] ?? '';
    },
    { immediate: true },
);

watch(
    () => sessionForm.date,
    (value) => {
        if (value !== selectedCalendarDate.value) {
            selectedCalendarDate.value = value;
        }
    },
);

function formatDuration(totalMinutes: number): string {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
}

const formatDateTime = formatDateTimeForPH;

function toTimeInput(value: string): string {
    return toPHTimeInputValue(value);
}

function resetSessionForm(): void {
    editingSessionId.value = null;
    sessionForm.clearErrors();
    sessionForm.date = props.manualEntryDefaults.date;
    selectedCalendarDate.value = props.manualEntryDefaults.date;
    sessionForm.start_time = props.manualEntryDefaults.startTime;
    sessionForm.end_time = props.manualEntryDefaults.endTime;
    sessionForm.break_minutes = props.manualEntryDefaults.breakMinutes;
    sessionForm.notes = '';
}

function startEditingSession(session: ActiveSession): void {
    editingSessionId.value = session.id;
    sessionForm.clearErrors();
    sessionForm.date = session.date;
    selectedCalendarDate.value = session.date;
    sessionForm.start_time = toTimeInput(session.startTime);
    sessionForm.end_time = session.endTime ? toTimeInput(session.endTime) : props.manualEntryDefaults.endTime;
    sessionForm.break_minutes = session.breakMinutes;
    sessionForm.notes = session.notes ?? '';
}

function updateSelectedCalendarDate(value: string): void {
    selectedCalendarDate.value = value;
}

function updateSelectedCalendarNotes(value: string): void {
    selectedDailyNote.value = value;
    dailyNoteForm.note = value;
}

function saveDailyNote(): void {
    dailyNoteForm.date = selectedCalendarDate.value;
    dailyNoteForm.note = selectedDailyNote.value;

    dailyNoteForm.post(storeDailyNote.url(), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            const next = { ...savedDailyNotes.value };

            if (selectedDailyNote.value.trim() === '') {
                delete next[selectedCalendarDate.value];
            } else {
                next[selectedCalendarDate.value] = selectedDailyNote.value.trim();
            }

            savedDailyNotes.value = next;
            toast.success((page.props as { flash?: { success?: string } }).flash?.success ?? 'Daily note saved.');
        },
    });
}

function submitSession(): void {
    if (editingSessionId.value) {
        isSessionUpdateConfirmOpen.value = true;
        return;
    }

    sessionForm.post(storeWorkSessions.url(), {
        preserveScroll: true,
        onSuccess: () => {
            resetSessionForm();
            toast.success('Session saved successfully.');
        },
    });
}

function confirmSessionUpdate(): void {
    if (!editingSessionId.value) {
        return;
    }

    sessionForm.patch(updateWorkSession.url(editingSessionId.value), {
        preserveScroll: true,
        onSuccess: () => {
            isSessionUpdateConfirmOpen.value = false;
            resetSessionForm();
            toast.success('Session updated successfully.');
        },
    });
}

function requestSessionDelete(session: ActiveSession): void {
    sessionPendingDelete.value = session;
    isSessionDeleteConfirmOpen.value = true;
}

function confirmSessionDelete(): void {
    if (!sessionPendingDelete.value) {
        return;
    }

    const pendingId = sessionPendingDelete.value.id;

    router.delete(destroyWorkSession.url(pendingId), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingSessionId.value === pendingId) {
                resetSessionForm();
            }

            isSessionDeleteConfirmOpen.value = false;
            sessionPendingDelete.value = null;
            toast.success('Session deleted successfully.');
        },
    });
}

function clockIn(): void {
    router.post(clockInWorkSessions.url(), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Clocked in successfully.');
        },
    });
}

function clockOut(): void {
    clockOutForm.post(clockOutWorkSessions.url(), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Clocked out successfully.');
        },
    });
}

function openBulkAdd(): void {
    bulkAddForm.clearErrors();
    bulkAddForm.start_date = props.internshipSettings?.startDate ?? props.manualEntryDefaults.date;
    bulkAddForm.end_date = props.manualEntryDefaults.date;
    bulkAddForm.break_minutes = props.manualEntryDefaults.breakMinutes;
    bulkAddForm.notes = 'Bulk added from internship schedule.';
    isBulkAddOpen.value = true;
}

function submitBulkAdd(): void {
    bulkAddForm.post(bulkStoreWorkSessions.url(), {
        preserveScroll: true,
        onSuccess: (page) => {
            isBulkAddOpen.value = false;
            toast.success((page.props as { flash?: { success?: string } }).flash?.success ?? 'Bulk add complete.');
        },
    });
}
</script>

<template>
    <Head title="HR Counter" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 p-4 md:p-8 max-w-7xl mx-auto">
            <WorkSessionCalendar
                :selected-date="selectedCalendarDate"
                :selected-notes="selectedDailyNote"
                :calendar-days="calendarDays"
                :internship-settings="internshipSettings"
                :is-saving-notes="dailyNoteForm.processing"
                :note-error="dailyNoteError"
                @update:selected-date="updateSelectedCalendarDate"
                @update:selected-notes="updateSelectedCalendarNotes"
                @save-note="saveDailyNote"
            />

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                
                <Card class="bg-card shadow-sm border-border">
                    <CardHeader class="border-b bg-muted/20">
                        <CardTitle>Hour Progress</CardTitle>
                        <CardDescription>Track your internship hours against the saved requirement.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6 pt-6">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-xl border bg-muted/30 p-4">
                                <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Logged</p>
                                <p class="mt-2 text-2xl font-bold text-foreground">
                                    {{ formatDuration(summary.liveCompletedMinutes) }}
                                </p>
                            </div>
                            <div class="rounded-xl border bg-muted/30 p-4">
                                <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Remaining</p>
                                <p class="mt-2 text-2xl font-bold text-foreground">
                                    {{ formatDuration(summary.remainingMinutes) }}
                                </p>
                            </div>
                            <div class="rounded-xl border bg-primary/5 p-4">
                                <p class="text-xs font-medium uppercase tracking-wider text-primary">Progress</p>
                                <p class="mt-2 text-2xl font-bold text-foreground">
                                    {{ summary.completionPercentage }}%
                                </p>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-muted/20 p-4 flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold">Internship Setup</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ internshipSettings 
                                        ? `${internshipSettings.requiredHours} hours • ${internshipSettings.regularWorkdays.join(', ')}` 
                                        : 'No setup saved yet' 
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" @click="openBulkAdd">Bulk Add</Button>
                                <Button variant="secondary" size="sm" @click="isSetupOpen = true">
                                    <Settings2 class="size-4 mr-2" /> Settings
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-orange-500/20 bg-orange-500/5 dark:bg-orange-500/10">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-orange-600 dark:text-orange-400">
                            <Clock3 class="size-5" /> Live Timer
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="rounded-2xl bg-slate-950 p-6 text-white dark:bg-white dark:text-slate-950 shadow-xl">
                            <span class="text-[10px] uppercase tracking-[0.3em] opacity-60">Session Status</span>
                            <h2 class="text-4xl font-black mt-1">{{ activeSession ? 'RUNNING' : 'IDLE' }}</h2>
                            <p class="mt-4 text-xs font-medium opacity-80 border-t border-white/10 dark:border-slate-950/10 pt-4">
                                {{ activeSession ? `Started at ${formatDateTime(activeSession.startTime)}` : 'Ready to clock in?' }}
                            </p>
                        </div>

                        <div v-if="activeSession" class="space-y-4 pt-2">
                            <div class="grid gap-2">
                                <Label class="text-xs">Break (Minutes)</Label>
                                <Input v-model="clockOutForm.break_minutes" type="number" class="bg-background" />
                            </div>
                            <div class="grid gap-2">
                                <Label class="text-xs">Notes</Label>
                                <Textarea v-model="clockOutForm.notes" rows="3" placeholder="What did you work on?" class="bg-background" />
                            </div>
                            <Button class="w-full bg-orange-600 hover:bg-orange-700 text-white" :disabled="clockOutForm.processing" @click="clockOut">
                                <Square class="size-4 mr-2 fill-current" /> Stop Session
                            </Button>
                        </div>
                        <Button v-else class="w-full h-12 text-lg font-bold" @click="clockIn">Start Session</Button>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                
                <Card class="bg-card border-border">
                    <CardHeader class="border-b">
                        <CardTitle>{{ editingSessionId ? 'Edit Session' : 'Manual Entry' }}</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <form class="space-y-5" @submit.prevent="submitSession">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2"><Label>Date</Label><Input v-model="sessionForm.date" type="date" /></div>
                                <div class="grid gap-2"><Label>Break</Label><Input v-model="sessionForm.break_minutes" type="number" /></div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2"><Label>Start</Label>
                                    <Select v-model="sessionForm.start_time">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent><SelectItem v-for="t in timeOptions" :key="t.value" :value="t.value">{{ t.label }}</SelectItem></SelectContent>
                                    </Select>
                                </div>
                                <div class="grid gap-2"><Label>End</Label>
                                    <Select v-model="sessionForm.end_time">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent><SelectItem v-for="t in timeOptions" :key="t.value" :value="t.value">{{ t.label }}</SelectItem></SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div class="grid gap-2"><Label>Session Notes</Label><Textarea v-model="sessionForm.notes" rows="3" /></div>
                            <div class="flex gap-3 pt-2">
                                <Button class="flex-1" :disabled="sessionForm.processing">{{ editingSessionId ? 'Update' : 'Save' }}</Button>
                                <Button type="button" variant="ghost" @click="resetSessionForm">Clear</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card class="bg-card border-border">
                    <CardHeader class="border-b">
                        <CardTitle>Recent Sessions</CardTitle>
                    </CardHeader>
                    <CardContent class="divide-y divide-border pt-0 px-0">
                        <div v-for="session in recentSessions" :key="session.id" class="p-4 hover:bg-muted/30 transition-colors flex items-center justify-between group">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-lg">{{ formatDuration(session.durationMinutes) }}</span>
                                    <Badge :variant="session.isRunning ? 'default' : 'secondary'">{{ session.isRunning ? 'Live' : 'Saved' }}</Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTime(session.startTime) }} <span v-if="session.endTime">→ {{ formatDateTime(session.endTime) }}</span>
                                </p>
                            </div>
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Button variant="ghost" size="icon" @click="startEditingSession(session)"><Pencil class="size-4" /></Button>
                                <Button variant="ghost" size="icon" class="text-destructive" @click="requestSessionDelete(session)"><Trash2 class="size-4" /></Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <InternshipSetupDialog v-model:open="isSetupOpen" :settings="internshipSettings" />
            <Dialog v-model:open="isSessionUpdateConfirmOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Confirm session update</DialogTitle>
                        <DialogDescription>
                            Save these changes to this work session?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isSessionUpdateConfirmOpen = false">
                            Cancel
                        </Button>
                        <Button :disabled="sessionForm.processing" @click="confirmSessionUpdate">
                            Confirm update
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isSessionDeleteConfirmOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete session?</DialogTitle>
                        <DialogDescription>
                            {{ sessionPendingDelete ? `This will permanently remove the session on ${sessionPendingDelete.date}.` : 'This action cannot be undone.' }}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isSessionDeleteConfirmOpen = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="confirmSessionDelete">
                            Delete session
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog v-model:open="isBulkAddOpen">
                </Dialog>
        </div>
    </AppLayout>
</template>
