import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import PriorityBadge from '@/Components/PriorityBadge';
import MonthlyTrendChart, {
    MonthlyTrendPoint,
} from '@/Components/MonthlyTrendChart';
import { Head, Link } from '@inertiajs/react';
import { Ticket, BorrowRecord } from '@/types';

interface Props {
    stats: { new: number; in_progress: number; resolved_this_month: number };
    recent_tickets: Ticket[];
    unassigned_count: number;
    monthly_trend: MonthlyTrendPoint[];
    borrowed_items: BorrowRecord[];
}

export default function ItSupportDashboard({
    stats,
    recent_tickets,
    unassigned_count,
    monthly_trend,
    borrowed_items,
}: Props) {
    function fmtDate(borrow_date: string): import("react").ReactNode {
        return new Date(borrow_date).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
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

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Baru (ditugaskan ke saya)"
                    value={stats.new}
                    accent="text-blue-600"
                />
                <StatCard
                    label="Dikerjakan"
                    value={stats.in_progress}
                    accent="text-yellow-600"
                />
                <StatCard
                    label="Selesai (bulan ini)"
                    value={stats.resolved_this_month}
                    accent="text-green-600"
                />
                <StatCard
                    label="Antrian Belum Ditugaskan"
                    value={unassigned_count}
                    accent="text-gray-700"
                />
            </div>

            <div className="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div className="border-b border-gray-200 px-5 py-3">
                    <h2 className="text-sm font-semibold text-gray-700">
                        Tren Tiket Saya (6 bulan terakhir)
                    </h2>
                </div>
                <div className="p-5">
                    <MonthlyTrendChart data={monthly_trend} />
                </div>
            </div>

            <div className="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                    <h2 className="text-sm font-semibold text-gray-700">
                        Tiket Terbaru Saya
                    </h2>
                    <Link
                        href={route('tickets.index')}
                        className="text-sm text-brand-600 hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>
                <div className="p-5">
                    {recent_tickets.length === 0 ? (
                        <p className="text-sm text-gray-500">
                            Belum ada tiket yang ditugaskan.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr className="text-left text-xs uppercase text-gray-500">
                                        <th className="py-2 pr-4">Kode</th>
                                        <th className="py-2 pr-4">Judul</th>
                                        <th className="py-2 pr-4">Pelapor</th>
                                        <th className="py-2 pr-4">
                                            Prioritas
                                        </th>
                                        <th className="py-2 pr-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {recent_tickets.map((t) => (
                                        <tr key={t.id}>
                                            <td className="py-2 pr-4 font-mono text-xs">
                                                <Link
                                                    href={route(
                                                        'tickets.show',
                                                        t.id,
                                                    )}
                                                    className="text-brand-600 hover:underline"
                                                >
                                                    {t.ticket_code}
                                                </Link>
                                            </td>
                                            <td className="py-2 pr-4">
                                                {t.title}
                                            </td>
                                            <td className="py-2 pr-4 text-gray-600">
                                                {t.requestor?.name}
                                            </td>
                                            <td className="py-2 pr-4">
                                                <PriorityBadge
                                                    priority={t.priority}
                                                />
                                            </td>
                                            <td className="py-2 pr-4">
                                                <StatusBadge
                                                    status={t.status}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
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
