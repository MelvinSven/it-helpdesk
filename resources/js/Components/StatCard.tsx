import { ReactNode } from 'react';

export default function StatCard({
    label,
    value,
    accent,
}: {
    label: string;
    value: ReactNode;
    accent?: string;
}) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div className="text-sm font-medium text-gray-500">{label}</div>
            <div
                className={`mt-2 text-3xl font-semibold ${accent ?? 'text-gray-900'}`}
            >
                {value}
            </div>
        </div>
    );
}
