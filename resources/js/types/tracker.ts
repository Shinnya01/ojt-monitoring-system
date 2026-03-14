export type InternshipSettings = {
    startDate: string | null;
    requiredHours: number | null;
    regularWorkdays: string[];
    defaultStartTime: string | null;
    defaultEndTime: string | null;
    isSetupComplete: boolean;
};

export type TrackerSummary = {
    completedMinutes: number;
    liveCompletedMinutes: number;
    todayMinutes: number;
    liveTodayMinutes: number;
    remainingMinutes: number;
    completionPercentage: number;
    completedSessions: number;
    hasInternshipSettings: boolean;
};

export type ActiveSession = {
    id: number;
    date: string;
    startTime: string;
    endTime: string | null;
    breakMinutes: number;
    durationMinutes: number;
    notes: string | null;
    isRunning: boolean;
};

export type CalendarDay = {
    date: string;
    sessionCount: number;
    totalMinutes: number;
};

export type DailyNote = {
    date: string;
    note: string | null;
};

export type TaskItem = {
    id: number;
    title: string;
    notes: string | null;
    dueDate: string | null;
    priority: 'low' | 'medium' | 'high';
    isDone: boolean;
};
