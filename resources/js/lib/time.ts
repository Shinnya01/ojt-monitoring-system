export type TimeOption = {
    value: string;
    label: string;
};

const PH_LOCALE = 'en-PH';
const PH_TIMEZONE = 'Asia/Manila';

export function buildHalfHourTimeOptions(): TimeOption[] {
    const options: TimeOption[] = [];

    for (let hour = 0; hour < 24; hour += 1) {
        for (const minute of [0, 30]) {
            const value = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;

            options.push({
                value,
                label: formatTimeValue(value),
            });
        }
    }

    return options;
}

export function formatTimeValue(value: string): string {
    const [hours, minutes] = value.split(':').map(Number);
    const date = new Date(Date.UTC(2026, 0, 1, hours, minutes));

    return new Intl.DateTimeFormat(PH_LOCALE, {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: 'UTC',
    }).format(date);
}

export function formatDateTimeForPH(value: string): string {
    return new Intl.DateTimeFormat(PH_LOCALE, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: PH_TIMEZONE,
    }).format(new Date(value));
}

export function toPHTimeInputValue(value: string): string {
    const formatter = new Intl.DateTimeFormat(PH_LOCALE, {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: PH_TIMEZONE,
    });

    return formatter.format(new Date(value));
}
