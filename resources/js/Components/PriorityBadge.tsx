import { TicketPriority } from '@/types';

const map: Record<TicketPriority, { label: string; cls: string }> = {
    low: { label: 'Rendah', cls: 'bg-gray-100 text-gray-700' },
    medium: { label: 'Sedang', cls: 'bg-sky-100 text-sky-800' },
    high: { label: 'Tinggi', cls: 'bg-orange-100 text-orange-800' },
    urgent: { label: 'Mendesak', cls: 'bg-red-100 text-red-800' },
};

export default function PriorityBadge({
    priority,
}: {
    priority: TicketPriority;
}) {
    const p = map[priority];
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${p.cls}`}
        >
            {p.label}
        </span>
    );
}
