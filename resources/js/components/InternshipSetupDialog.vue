<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store as storeInternshipSettings } from '@/routes/internship-settings';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { buildHalfHourTimeOptions } from '@/lib/time';
import type { InternshipSettings } from '@/types';

const props = withDefaults(defineProps<{
    open: boolean;
    settings?: InternshipSettings | null;
    title?: string;
    description?: string;
}>(), {
    settings: null,
    title: 'Set up your internship tracker',
    description: 'Save your internship schedule so hour tracking and progress can use your real setup.',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const weekdayOptions = [
    { value: 'monday', label: 'Monday' },
    { value: 'tuesday', label: 'Tuesday' },
    { value: 'wednesday', label: 'Wednesday' },
    { value: 'thursday', label: 'Thursday' },
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' },
];

const timeOptions = computed(() => buildHalfHourTimeOptions());

const localOpen = ref(props.open);

const form = useForm<{
    start_date: string;
    required_hours: number;
    regular_workdays: string[];
    default_start_time: string;
    default_end_time: string;
}>({
    start_date: '',
    required_hours: 486,
    regular_workdays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    default_start_time: '09:00',
    default_end_time: '18:00',
});

watch(
    () => props.open,
    (value) => {
        localOpen.value = value;
    },
);

watch(
    () => props.settings,
    () => {
        syncForm();
    },
    { immediate: true },
);

watch(localOpen, (value) => {
    emit('update:open', value);
});

function syncForm(): void {
    form.clearErrors();
    form.start_date = props.settings?.startDate ?? '';
    form.required_hours = props.settings?.requiredHours ?? 486;
    form.regular_workdays = props.settings?.regularWorkdays?.length
        ? [...props.settings.regularWorkdays]
        : ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    form.default_start_time = props.settings?.defaultStartTime ?? '09:00';
    form.default_end_time = props.settings?.defaultEndTime ?? '18:00';
}

const projectedEndDate = computed(() => {
    if (!form.start_date || !form.required_hours || !form.regular_workdays.length || !form.default_start_time || !form.default_end_time) {
        return null;
    }

    const [startHour, startMinute] = form.default_start_time.split(':').map(Number);
    const [endHour, endMinute] = form.default_end_time.split(':').map(Number);
    const dailyMinutes = ((endHour * 60) + endMinute) - ((startHour * 60) + startMinute);

    if (dailyMinutes <= 0) {
        return null;
    }

    const requiredMinutes = form.required_hours * 60;
    const workdays = new Set(form.regular_workdays);
    const weekdayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    let accumulatedMinutes = 0;
    let date = new Date(`${form.start_date}T00:00:00`);

    for (let index = 0; index < 3660; index += 1) {
        if (workdays.has(weekdayKeys[date.getDay()])) {
            accumulatedMinutes += dailyMinutes;

            if (accumulatedMinutes >= requiredMinutes) {
                return date.toLocaleDateString('en-CA', {
                    timeZone: 'Asia/Manila',
                });
            }
        }

        date.setDate(date.getDate() + 1);
    }

    return null;
});

function toggleWorkday(day: string, checked: boolean | 'indeterminate'): void {
    const next = new Set(form.regular_workdays);

    if (checked === true) {
        next.add(day);
    } else {
        next.delete(day);
    }

    form.regular_workdays = [...next];
}

function submit(): void {
    form.post(storeInternshipSettings.url(), {
        preserveScroll: true,
        onSuccess: () => {
            localOpen.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="localOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="internship-start-date">Internship start date</Label>
                    <Input
                        id="internship-start-date"
                        v-model="form.start_date"
                        type="date"
                    />
                    <InputError :message="form.errors.start_date" />
                </div>

                <div class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <Label class="text-sm">Projected internship end date</Label>
                    <p class="text-sm font-semibold text-slate-950">
                        {{ projectedEndDate ?? 'Set your schedule details to calculate this automatically.' }}
                    </p>
                    <p class="text-xs text-slate-500">
                        Based on your start date, required hours, regular workdays, and default daily schedule.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="required-hours">Required total hours</Label>
                    <Input
                        id="required-hours"
                        v-model="form.required_hours"
                        type="number"
                        min="1"
                    />
                    <InputError :message="form.errors.required_hours" />
                </div>

                <div class="grid gap-3">
                    <Label>Regular workdays</Label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Label
                            v-for="day in weekdayOptions"
                            :key="day.value"
                            :for="day.value"
                            class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3"
                        >
                            <Checkbox
                                :id="day.value"
                                :model-value="form.regular_workdays.includes(day.value)"
                                @update:model-value="toggleWorkday(day.value, $event)"
                            />
                            <span>{{ day.label }}</span>
                        </Label>
                    </div>
                    <InputError :message="form.errors.regular_workdays" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="default-start-time">Default start time</Label>
                        <Select v-model="form.default_start_time">
                            <SelectTrigger id="default-start-time" class="w-full">
                                <SelectValue placeholder="Select start time" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="time in timeOptions"
                                    :key="time.value"
                                    :value="time.value"
                                >
                                    {{ time.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.default_start_time" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="default-end-time">Default end time</Label>
                        <Select v-model="form.default_end_time">
                            <SelectTrigger id="default-end-time" class="w-full">
                                <SelectValue placeholder="Select end time" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="time in timeOptions"
                                    :key="time.value"
                                    :value="time.value"
                                >
                                    {{ time.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.default_end_time" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        Save internship setup
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
