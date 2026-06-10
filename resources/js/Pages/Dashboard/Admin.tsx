import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import MonthlyTrendChart, {
    MonthlyTrendPoint,
} from '@/Components/MonthlyTrendChart';
import { Head, Link } from '@inertiajs/react';
import { Ticket, BorrowRecord } from '@/types';

interface Props {
    stats: {
        total: number;
        open: number;
        in_progress: number;
        resolved_this_month: number;
    };
    status_counts: Record<string, number>;
    category_counts: Record<string, number>;
    workload: { name: string; count: number }[];
    monthly_trend: MonthlyTrendPoint[];
    recent_tickets: Ticket[];
    borrowed_items: BorrowRecord[];
}

export default function AdminDashboard({
    stats,
    category_counts,
    workload,
    monthly_trend,
    recent_tickets,
    borrowed_items,
}: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Dasbor Admin
                </h1>
            }
        >
            <Head title="Dasbor" />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total Tiket" value={stats.total} />
                <StatCard

                
                    label="Terbaru
                    "
                    value={stats.open}
                    accent="text-yellow-600"
                />
                <StatCard
                    label="Dikerjakan"
                    value={stats.in_progress}
                    accent="text-blue-600"
                />
                <StatCard
                    label="Selesai (bulan ini)"
                    value={stats.resolved_this_month}
                    accent="text-green-600"
                />
            </div>

            <div className="mt-6">
                <Panel title="Tren Tiket Bulanan (6 bulan terakhir)">
                    <MonthlyTrendChart data={monthly_trend} />
                </Panel>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Panel title="Tiket per Kategori">
                    <BarList data={category_counts} />
                </Panel>
                <Panel title="Beban Dukungan TI (tiket aktif)">
                    <BarList
                        data={Object.fromEntries(
                            workload.map((w) => [w.name, w.count]),
                        )}
                    />
                </Panel>
            </div>

            <div className="mt-6">
                <Panel
                    title="Tiket Terbaru"
                    action={
                        <Link
                            href={route('tickets.index')}
                            className="text-sm text-brand-600 hover:underline"
                        >
                            Lihat semua
                        </Link>
                    }
                >
                    <RecentTicketTable tickets={recent_tickets} />
                </Panel>
            </div>

            <div className="mt-6">
                <Panel
                    title="Barang Sedang Dipinjam"
                    action={
                        <Link
                            href={route('borrows.index')}
                            className="text-sm text-brand-600 hover:underline"
                        >
                            Lihat semua
                        </Link>
                    }
                >
                    <BorrowedItemsTable items={borrowed_items} showBorrower />
                </Panel>
            </div>
        </AuthenticatedLayout>
    );
}

function Panel({
    title,
    children,
    action,
}: {
    title: string;
    children: React.ReactNode;
    action?: React.ReactNode;
}) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <h2 className="text-sm font-semibold text-gray-700">{title}</h2>
                {action}
            </div>
            <div className="p-5">{children}</div>
        </div>
    );
}

function BarList({ data }: { data: Record<string, number> }) {
    const entries = Object.entries(data);
    if (!entries.length) {
        return <p className="text-sm text-gray-500">Belum ada data.</p>;
    }
    const max = Math.max(...entries.map(([, v]) => v));
    return (
        <ul className="space-y-3">
            {entries.map(([name, count]) => (
                <li key={name}>
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-700">{name}</span>
                        <span className="font-medium text-gray-900">
                            {count}
                        </span>
                    </div>
                    <div className="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div
                            className="h-full rounded-full bg-brand-500"
                            style={{
                                width: `${max ? (count / max) * 100 : 0}%`,
                            }}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}

function BorrowedItemsTable({
    items,
    showBorrower = false,
}: {
    items: BorrowRecord[];
    showBorrower?: boolean;
}) {
    if (!items.length) {
        return (
            <p className="text-sm text-gray-500">
                Tidak ada barang yang sedang dipinjam.
            </p>
        );
    }
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr className="text-left text-xs uppercase text-gray-500">
                        <th className="py-2 pr-4">Nama Barang</th>
                        <th className="py-2 pr-4">Nomor</th>
                        {showBorrower && (
                            <th className="py-2 pr-4">Peminjam</th>
                        )}
                        <th className="py-2 pr-4">Tgl Pinjam</th>
                        <th className="py-2 pr-4">Keperluan</th>
                        <th className="py-2 pr-4">Aksi</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {items.map((b) => (
                        <tr key={b.id}>
                            <td className="py-2 pr-4 font-medium text-gray-900">
                                {b.item_name}
                            </td>
                            <td className="py-2 pr-4 font-mono text-xs text-gray-600">
                                {b.serial_number}
                            </td>
                            {showBorrower && (
                                <td className="py-2 pr-4 text-gray-600">
                                    {b.borrower_name}
                                </td>
                            )}
                            <td className="py-2 pr-4 text-gray-600">
                                {b.borrow_date}
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
    );
}

function RecentTicketTable({ tickets }: { tickets: Ticket[] }) {
    if (!tickets.length) {
        return <p className="text-sm text-gray-500">Belum ada tiket.</p>;
    }
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr className="text-left text-xs uppercase text-gray-500">
                        <th className="py-2 pr-4">Kode</th>
                        <th className="py-2 pr-4">Judul</th>
                        <th className="py-2 pr-4">Pelapor</th>
                        <th className="py-2 pr-4">Penangan</th>
                        <th className="py-2 pr-4">Status</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {tickets.map((t) => (
                        <tr key={t.id}>
                            <td className="py-2 pr-4 font-mono text-xs">
                                <Link
                                    href={route('tickets.show', t.id)}
                                    className="text-brand-600 hover:underline"
                                >
                                    {t.ticket_code}
                                </Link>
                            </td>
                            <td className="py-2 pr-4">{t.title}</td>
                            <td className="py-2 pr-4 text-gray-600">
                                {t.requestor?.name}
                            </td>
                            <td className="py-2 pr-4 text-gray-600">
                                {t.assignee?.name ?? '—'}
                            </td>
                            <td className="py-2 pr-4">
                                <StatusBadge status={t.status} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
