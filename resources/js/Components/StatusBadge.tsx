import { TicketStatus } from '@/types';

const map: Record<TicketStatus, { label: string; cls: string }> = {
    new: { label: 'Baru', cls: 'bg-blue-100 text-blue-800' },
    in_progress: { label: 'Dikerjakan', cls: 'bg-yellow-100 text-yellow-800' },
    resolved: { label: 'Selesai', cls: 'bg-green-100 text-green-800' },
};

export default function StatusBadge({ status }: { status: TicketStatus }) {
    const s = map[status];
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${s.cls}`}
        >
            {s.label}
        </span>
    );
}
