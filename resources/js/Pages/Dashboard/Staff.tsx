import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import PriorityBadge from '@/Components/PriorityBadge';
import { Head, Link } from '@inertiajs/react';
import { Ticket, BorrowRecord } from '@/types';

interface Props {
    stats: { open: number; resolved: number; total: number };
    recent_tickets: Ticket[];
    borrowed_items: BorrowRecord[];
}

export default function StaffDashboard({ stats, recent_tickets, borrowed_items }: Props) {
    function fmtDate(borrow_date: string): import("react").ReactNode {
        const date = new Date(borrow_date);

        if (Number.isNaN(date.getTime())) {
            return borrow_date;
        }

        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
        }).format(date);
    }

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Dasbor Saya
                </h1>
            }
        >
            <Head title="Dasbor" />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Tiket Terbuka"
                    value={stats.open}
                    accent="text-yellow-600"
                />
                <StatCard
                    label="Selesai"
                    value={stats.resolved}
                    accent="text-green-600"
                />
                <StatCard label="Total Dikirim" value={stats.total} />
            </div>

            <div className="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                    <h2 className="text-sm font-semibold text-gray-700">
                        Tiket Saya
                    </h2>
                    <Link
                        href={route('tickets.create')}
                        className="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700"
                    >
                        Tiket Baru
                    </Link>
                </div>
                <div className="p-5">
                    {recent_tickets.length === 0 ? (
                        <p className="text-sm text-gray-500">
                            Anda belum mengirim tiket apa pun.
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {recent_tickets.map((t) => (
                                <li key={t.id} className="py-3">
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="min-w-0">
                                            <Link
                                                href={route(
                                                    'tickets.show',
                                                    t.id,
                                                )}
                                                className="block truncate font-medium text-gray-900 hover:text-brand-600"
                                            >
                                                {t.title}
                                            </Link>
                                            <div className="mt-0.5 text-xs text-gray-500">
                                                <span className="font-mono">
                                                    {t.ticket_code}
                                                </span>
                                                <span> - </span>
                                                {t.category?.name}
                                                <span> - </span>
                                                {t.assignee?.name
                                                    ? `Ditangani ${t.assignee.name}`
                                                    : 'Belum ditugaskan'}
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-2">
                                            <PriorityBadge
                                                priority={t.priority}
                                            />
                                            <StatusBadge status={t.status} />
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <div className="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                    <h2 className="text-sm font-semibold text-gray-700">
                        Barang Sedang Saya Pinjam
                    </h2>
                    <Link
                        href={route('borrows.index')}
                        className="text-sm text-brand-600 hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>
                <div className="p-5">
                    {borrowed_items.length === 0 ? (
                        <p className="text-sm text-gray-500">
                            Anda tidak sedang meminjam barang apa pun.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase text-gray-500">
                                        <th className="py-2 pr-4">Nama Barang</th>
                                        <th className="py-2 pr-4">Nomor</th>
                                        <th className="py-2 pr-4">Tgl Pinjam</th>
                                        <th className="py-2 pr-4">Keperluan</th>
                                        <th className="py-2 pr-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {borrowed_items.map((b) => (
                                        <tr key={b.id}>
                                            <td className="py-2 pr-4 font-medium text-gray-900">
                                                {b.item_name}
                                            </td>
                                            <td className="py-2 pr-4 font-mono text-xs text-gray-600">
                                                {b.serial_number}
                                            </td>
                                            <td className="py-2 pr-4 text-gray-600">
                                                {fmtDate(b.borrow_date)}
                                            </td>
                                            <td className="py-2 pr-4 text-gray-600 max-w-xs truncate">
                                                {b.purpose}
                                            </td>
                                            <td className="py-2 pr-4">
                                                <Link
                                                    href={route('borrows.show', b.id)}
                                                    className="rounded border border-brand-600 bg-white px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50"
                                                >
                                                    Detail
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
