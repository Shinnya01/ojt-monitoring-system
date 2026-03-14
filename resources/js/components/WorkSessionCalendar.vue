<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { CalendarDay, InternshipSettings } from '@/types';

const props = defineProps<{
    selectedDate: string;
    selectedNotes: string;
    calendarDays: CalendarDay[];
    internshipSettings?: InternshipSettings | null;
    isSavingNotes?: boolean;
    noteError?: string;
}>();

const emit = defineEmits<{
    (e: 'update:selectedDate', value: string): void;
    (e: 'update:selectedNotes', value: string): void;
    (e: 'saveNote'): void;
}>();

const weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const weekdayMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

const visibleMonth = ref(startOfMonth(parseDate(props.selectedDate) ?? todayInPH()));

watch(
    () => props.selectedDate,
    (value) => {
        const parsed = parseDate(value);

        if (parsed !== null) {
            visibleMonth.value = startOfMonth(parsed);
        }
    },
);

const daysByDate = computed(() => new Map(props.calendarDays.map((day) => [day.date, day])));
const selectedDay = computed(() => daysByDate.value.get(props.selectedDate) ?? null);
const monthLabel = computed(() =>
    new Intl.DateTimeFormat('en-PH', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(visibleMonth.value),
);

const calendarCells = computed(() => {
    const monthStart = startOfMonth(visibleMonth.value);
    const gridStart = startOfWeekMonday(monthStart);
    const today = toDateString(todayInPH());

    return Array.from({ length: 42 }, (_, index) => {
        const date = addDays(gridStart, index);
        const isoDate = toDateString(date);
        const details = daysByDate.value.get(isoDate);
        const weekdayKey = weekdayMap[date.getUTCDay()];

        return {
            isoDate,
            dayNumber: date.getUTCDate(),
            isCurrentMonth: date.getUTCMonth() === monthStart.getUTCMonth(),
            isToday: isoDate === today,
            isSelected: isoDate === props.selectedDate,
            isRegularWorkday: props.internshipSettings?.regularWorkdays.includes(weekdayKey) ?? false,
            sessionCount: details?.sessionCount ?? 0,
            totalMinutes: details?.totalMinutes ?? 0,
        };
    });
});

function previousMonth(): void {
    visibleMonth.value = new Date(Date.UTC(
        visibleMonth.value.getUTCFullYear(),
        visibleMonth.value.getUTCMonth() - 1,
        1,
    ));
}

function nextMonth(): void {
    visibleMonth.value = new Date(Date.UTC(
        visibleMonth.value.getUTCFullYear(),
        visibleMonth.value.getUTCMonth() + 1,
        1,
    ));
}

function chooseDate(value: string): void {
    emit('update:selectedDate', value);
}

function updateNotes(value: string): void {
    emit('update:selectedNotes', value);
}

function formatDuration(totalMinutes: number): string {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
}

function parseDate(value: string | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(Date.UTC(year, month - 1, day));
}

function startOfMonth(value: Date): Date {
    return new Date(Date.UTC(value.getUTCFullYear(), value.getUTCMonth(), 1));
}

function startOfWeekMonday(value: Date): Date {
    const day = value.getUTCDay();
    const distance = day === 0 ? -6 : 1 - day;

    return addDays(value, distance);
}

function addDays(value: Date, amount: number): Date {
    const result = new Date(value);

    result.setUTCDate(result.getUTCDate() + amount);

    return result;
}

function toDateString(value: Date): string {
    return value.toISOString().slice(0, 10);
}

function todayInPH(): Date {
    const parts = new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        timeZone: 'Asia/Manila',
    }).formatToParts(new Date());

    const year = Number(parts.find((part) => part.type === 'year')?.value ?? '0');
    const month = Number(parts.find((part) => part.type === 'month')?.value ?? '1');
    const day = Number(parts.find((part) => part.type === 'day')?.value ?? '1');

    return new Date(Date.UTC(year, month - 1, day));
}
</script>

<template>
    <Card class="border-border bg-card shadow-sm">
        <CardHeader class="flex flex-col gap-4 border-b bg-muted/20 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <CardTitle class="text-xl font-bold tracking-tight">Internship Calendar</CardTitle>
                    <Badge variant="outline" class="font-medium">View & Notes</Badge>
                </div>
                <CardDescription>
                    Select a date to log daily notes and review recorded hours.
                </CardDescription>
            </div>

            <div class="flex flex-col gap-3 sm:items-end">
                <div class="flex items-center gap-1.5">
                    <Button 
                        variant="ghost" 
                        size="sm" 
                        class="h-8 text-xs font-semibold hover:bg-muted"
                        @click="visibleMonth = startOfMonth(todayInPH()); chooseDate(toDateString(todayInPH()))"
                    >
                        Today
                    </Button>
                    
                    <div class="flex items-center rounded-md border bg-background px-1">
                        <Button variant="ghost" size="icon" class="size-8" @click="previousMonth">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <div class="min-w-32 text-center text-xs font-bold uppercase tracking-widest text-foreground">
                            {{ monthLabel }}
                        </div>
                        <Button variant="ghost" size="icon" class="size-8" @click="nextMonth">
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent class="grid gap-6 pt-6 xl:grid-cols-[1fr_350px]">
            <div class="overflow-x-auto">
                <div class="min-w-[700px] space-y-2">
                    <div class="grid grid-cols-7 gap-2 px-1">
                        <div
                            v-for="label in weekdayLabels"
                            :key="label"
                            class="py-2 text-center text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/70"
                        >
                            {{ label }}
                        </div>
                    </div>

                    <div class="grid grid-cols-7 gap-2">
                        <button
                            v-for="cell in calendarCells"
                            :key="cell.isoDate"
                            type="button"
                            class="group relative flex min-h-24 flex-col rounded-xl border p-2 text-left transition-all"
                            :class="[
                                cell.isSelected
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : cell.sessionCount > 0
                                      ? 'border-emerald-500/30 bg-emerald-500/5 hover:bg-emerald-500/10'
                                      : 'border-border bg-background hover:bg-muted/50',
                                !cell.isCurrentMonth && 'opacity-25 grayscale',
                                cell.isToday && !cell.isSelected ? 'border-foreground/50 ring-1 ring-foreground/20' : ''
                            ]"
                            @click="chooseDate(cell.isoDate)"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="flex size-7 items-center justify-center rounded-lg text-xs font-bold"
                                    :class="[
                                        cell.isToday ? 'bg-foreground text-background' : 'text-foreground',
                                    ]"
                                >
                                    {{ cell.dayNumber }}
                                </span>
                                
                                <div v-if="cell.sessionCount > 0" class="size-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                            </div>

                            <div class="mt-auto pt-2">
                                <p v-if="cell.totalMinutes > 0" class="text-[11px] font-black text-foreground">
                                    {{ formatDuration(cell.totalMinutes) }}
                                </p>
                                <p v-else-if="cell.isRegularWorkday && cell.isCurrentMonth" class="text-[9px] font-medium uppercase tracking-tighter text-muted-foreground/50">
                                    Scheduled
                                </p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 rounded-2xl border bg-muted/30 p-4">
                <div class="space-y-1">
                    <Label class="text-[10px] uppercase tracking-widest text-muted-foreground">Selected Date</Label>
                    <p class="font-bold text-foreground">{{ selectedDate }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ selectedDay ? `${formatDuration(selectedDay.totalMinutes)} logged` : 'No hours recorded' }}
                    </p>
                </div>

                <div class="mt-2 space-y-3">
                    <div class="grid gap-2">
                        <Label for="calendar-session-note" class="text-xs">Daily Log / Notes</Label>
                        <Textarea
                            id="calendar-session-note"
                            :model-value="selectedNotes"
                            rows="5"
                            placeholder="What did you work on today?"
                            @update:model-value="updateNotes(String($event ?? ''))"
                            class="resize-none bg-background text-sm"
                        />
                        <InputError :message="noteError" />
                    </div>
                    
                    <Button 
                        class="w-full font-bold" 
                        size="sm"
                        :disabled="isSavingNotes" 
                        @click="$emit('saveNote')"
                    >
                        {{ isSavingNotes ? 'Saving...' : 'Save daily note' }}
                    </Button>
                </div>

                <div v-if="selectedDay && selectedDay.sessionCount > 0" class="mt-auto border-t border-border pt-4">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-muted-foreground">Total Sessions:</span>
                        <span class="font-bold text-foreground">{{ selectedDay.sessionCount }}</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>