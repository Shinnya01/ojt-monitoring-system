<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Clock3, Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { dashboard } from '@/routes';
import {
    destroy as destroyTaskRoute,
    index as tasksIndex,
    store as storeTasks,
    toggle as toggleTaskRoute,
    update as updateTaskRoute,
} from '@/routes/tasks';
import InputError from '@/components/InputError.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, TaskItem } from '@/types';

type Props = {
    tasks: TaskItem[];
    counts: {
        pending: number;
        completed: number;
        total: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Tasks', href: tasksIndex() },
];

const editingTaskId = ref<number | null>(null);
const isUpdateConfirmOpen = ref(false);
const isDeleteConfirmOpen = ref(false);
const taskPendingDelete = ref<TaskItem | null>(null);

const taskForm = useForm<{
    title: string;
    notes: string;
    due_date: string;
    priority: TaskItem['priority'];
}>({
    title: '',
    notes: '',
    due_date: '',
    priority: 'medium',
});

function resetTaskForm(): void {
    editingTaskId.value = null;
    taskForm.clearErrors();
    taskForm.title = '';
    taskForm.notes = '';
    taskForm.due_date = '';
    taskForm.priority = 'medium';
}

function startEditingTask(task: TaskItem): void {
    editingTaskId.value = task.id;
    taskForm.clearErrors();
    taskForm.title = task.title;
    taskForm.notes = task.notes ?? '';
    taskForm.due_date = task.dueDate ?? '';
    taskForm.priority = task.priority;
}

function submitTask(): void {
    if (editingTaskId.value) {
        isUpdateConfirmOpen.value = true;
        return;
    }

    taskForm.post(storeTasks.url(), {
        preserveScroll: true,
        onSuccess: () => {
            resetTaskForm();
            toast.success('Task saved successfully.');
        },
    });
}

function toggleTask(id: number): void {
    router.patch(toggleTaskRoute.url(id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Task status updated.');
        },
    });
}

function confirmTaskUpdate(): void {
    if (!editingTaskId.value) {
        return;
    }

    taskForm.patch(updateTaskRoute.url(editingTaskId.value), {
        preserveScroll: true,
        onSuccess: () => {
            isUpdateConfirmOpen.value = false;
            resetTaskForm();
            toast.success('Task updated successfully.');
        },
    });
}

function requestTaskDelete(task: TaskItem): void {
    taskPendingDelete.value = task;
    isDeleteConfirmOpen.value = true;
}

function confirmTaskDelete(): void {
    if (!taskPendingDelete.value) {
        return;
    }

    const pendingId = taskPendingDelete.value.id;

    router.delete(destroyTaskRoute.url(pendingId), {
        preserveScroll: true,
        onSuccess: () => {
            if (editingTaskId.value === pendingId) {
                resetTaskForm();
            }

            isDeleteConfirmOpen.value = false;
            taskPendingDelete.value = null;
            toast.success('Task deleted successfully.');
        },
    });
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'No due date';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function badgeVariant(priority: TaskItem['priority']): 'default' | 'secondary' | 'destructive' {
    if (priority === 'high') {
        return 'destructive';
    }

    if (priority === 'medium') {
        return 'default';
    }

    return 'secondary';
}
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 ">
            <section class="grid gap-4 md:grid-cols-3">
                <div v-for="(val, label) in { Pending: counts.pending, Completed: counts.completed, Total: counts.total }" 
                    :key="label"
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/70">{{ label }}</p>
                    <p class="mt-2 text-4xl font-black tracking-tighter text-foreground">{{ val }}</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <Card :class="[
                    'border-border shadow-sm transition-all duration-300',
                    editingTaskId ? 'ring-2 ring-primary/20 border-primary/50' : ''
                ]">
                    <CardHeader class="border-b bg-muted/10">
                        <CardTitle class="text-xl font-bold tracking-tight">
                            {{ editingTaskId ? 'Edit Task Detail' : 'Create New Task' }}
                        </CardTitle>
                        <CardDescription>
                            {{ editingTaskId ? 'Updating existing deliverable.' : 'Add a new internship deliverable.' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <form class="space-y-5" @submit.prevent="submitTask">
                            <div class="space-y-2">
                                <Label for="task-title" class="text-xs font-black uppercase tracking-widest text-muted-foreground">Title</Label>
                                <Input id="task-title" v-model="taskForm.title" placeholder="e.g., Documentation Review" class="bg-muted/30 focus-visible:ring-primary" />
                                <InputError :message="taskForm.errors.title" />
                            </div>

                            <div class="space-y-2">
                                <Label for="task-notes" class="text-xs font-black uppercase tracking-widest text-muted-foreground">Detailed Notes</Label>
                                <Textarea
                                    id="task-notes"
                                    v-model="taskForm.notes"
                                    placeholder="Add specific instructions or links..."
                                    rows="4"
                                    class="bg-muted/30 resize-none focus-visible:ring-primary"
                                />
                                <InputError :message="taskForm.errors.notes" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="task-date" class="text-xs font-black uppercase tracking-widest text-muted-foreground">Due Date</Label>
                                    <Input id="task-date" v-model="taskForm.due_date" type="date" class="bg-muted/30" />
                                    <InputError :message="taskForm.errors.due_date" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="task-priority" class="text-xs font-black uppercase tracking-widest text-muted-foreground">Priority</Label>
                                    <Select v-model="taskForm.priority">
                                        <SelectTrigger id="task-priority" class="bg-muted/30">
                                            <SelectValue placeholder="Select" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="low">Low</SelectItem>
                                            <SelectItem value="medium">Medium</SelectItem>
                                            <SelectItem value="high">High</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="taskForm.errors.priority" />
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-4 border-t border-border">
                                <Button :disabled="taskForm.processing" class="font-bold px-6 shadow-sm">
                                    {{ editingTaskId ? 'Update Task' : 'Save Task' }}
                                </Button>
                                <Button type="button" variant="ghost" class="font-bold text-muted-foreground" @click="resetTaskForm">
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card class="border-border shadow-sm">
                    <CardHeader class="border-b bg-muted/10">
                        <CardTitle class="text-xl font-bold tracking-tight">Deliverables</CardTitle>
                        <CardDescription>Click a task title to toggle completion.</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="divide-y divide-border">
                            <div
                                v-for="task in tasks"
                                :key="task.id"
                                class="group flex flex-col gap-3 p-5 transition-colors hover:bg-muted/30 md:flex-row md:items-start md:justify-between"
                            >
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="text-left text-base font-bold tracking-tight text-foreground decoration-primary/40 decoration-2 transition-all hover:text-primary"
                                            :class="{ 'line-through opacity-40 grayscale': task.isDone }"
                                            @click="toggleTask(task.id)"
                                        >
                                            {{ task.title }}
                                        </button>
                                        <Badge :variant="badgeVariant(task.priority)" class="rounded-sm px-1.5 py-0 text-[10px] font-black uppercase">
                                            {{ task.priority }}
                                        </Badge>
                                        <Badge v-if="task.isDone" variant="outline" class="border-emerald-500/30 bg-emerald-500/5 text-emerald-600 font-bold text-[10px] uppercase">
                                            Resolved
                                        </Badge>
                                    </div>
                                    
                                    <div class="flex items-center gap-4 text-xs font-medium text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Clock3 class="size-3 opacity-70" />
                                            {{ formatDate(task.dueDate) }}
                                        </span>
                                    </div>

                                    <p v-if="task.notes" class="text-sm leading-relaxed text-muted-foreground max-w-md italic">
                                        "{{ task.notes }}"
                                    </p>
                                </div>

                                <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Button size="icon" variant="ghost" class="size-8 hover:bg-background hover:text-primary" @click="startEditingTask(task)">
                                        <Pencil class="size-3.5" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="size-8 hover:bg-destructive/10 hover:text-destructive" @click="requestTaskDelete(task)">
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </div>
                            </div>
                            
                            <div v-if="tasks.length === 0" class="p-12 text-center">
                                <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest">No tasks found</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Dialog v-model:open="isUpdateConfirmOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Confirm task update</DialogTitle>
                        <DialogDescription>
                            Save these changes to this task?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isUpdateConfirmOpen = false">
                            Cancel
                        </Button>
                        <Button :disabled="taskForm.processing" @click="confirmTaskUpdate">
                            Confirm update
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isDeleteConfirmOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete task?</DialogTitle>
                        <DialogDescription>
                            {{ taskPendingDelete ? `This will permanently remove "${taskPendingDelete.title}".` : 'This action cannot be undone.' }}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isDeleteConfirmOpen = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="confirmTaskDelete">
                            Delete task
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
