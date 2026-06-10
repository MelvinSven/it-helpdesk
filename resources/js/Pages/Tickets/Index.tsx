import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import PriorityBadge from '@/Components/PriorityBadge';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Category, PageProps, Paginated, Ticket } from '@/types';
import { useState } from 'react';

interface Props {
    tickets: Paginated<Ticket>;
    categories: Category[];
    filters: {
        status?: string;
        priority?: string;
        category_id?: number | string;
        search?: string;
    };
}

export default function Index({ tickets, categories, filters }: Props) {
    const { auth } = usePage<PageProps>().props;
    const isAdmin = auth.user.role === 'admin';

    const [form, setForm] = useState({
        search: filters.search ?? '',
        status: filters.status ?? '',
        priority: filters.priority ?? '',
        category_id: filters.category_id ?? '',
    });

    const apply = (next = form) => {
        router.get(route('tickets.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">Tiket</h1>
            }
        >
            <Head title="Tiket" />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        value={form.search}
                        onChange={(e) =>
                            setForm({ ...form, search: e.target.value })
                        }
                        onKeyDown={(e) => e.key === 'Enter' && apply()}
                        placeholder="Cari judul atau kode..."
                        className="rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <select
                        value={form.status}
                        onChange={(e) => {
                            const next = { ...form, status: e.target.value };
                            setForm(next);
                            apply(next);
                        }}
                        className="rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Semua Status</option>
                        <option value="new">Baru</option>
                        <option value="in_progress">Dikerjakan</option>
                        <option value="resolved">Selesai</option>
                    </select>
                    <select
                        value={form.priority}
                        onChange={(e) => {
                            const next = { ...form, priority: e.target.value };
                            setForm(next);
                            apply(next);
                        }}
                        className="rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Semua Prioritas</option>
                        <option value="low">Rendah</option>
                        <option value="medium">Sedang</option>
                        <option value="high">Tinggi</option>
                        <option value="urgent">Mendesak</option>
                    </select>
                    <select
                        value={form.category_id}
                        onChange={(e) => {
                            const next = {
                                ...form,
                                category_id: e.target.value,
                            };
                            setForm(next);
                            apply(next);
                        }}
                        className="rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Semua Kategori</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex items-center gap-2">
                    <a
                        href={`${route('tickets.export')}?${new URLSearchParams(
                            Object.entries(form).filter(
                                ([, v]) => v !== '' && v != null,
                            ) as [string, string][],
                        ).toString()}`}
                        className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Ekspor Excel
                    </a>
                    <Link
                        href={route('tickets.create')}
                        className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700"
                    >
                        Tiket Baru
                    </Link>
                </div>
            </div>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr className="text-left text-xs uppercase text-gray-500">
                            <th className="px-4 py-3">Kode</th>
                            <th className="px-4 py-3">Judul</th>
                            <th className="px-4 py-3">Kategori</th>
                            <th className="px-4 py-3">Pelapor</th>
                            <th className="px-4 py-3">Penangan</th>
                            <th className="px-4 py-3">Prioritas</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Dibuat</th>
                            {isAdmin && <th className="px-4 py-3">Aksi</th>}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {tickets.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={isAdmin ? 9 : 8}
                                    className="px-4 py-10 text-center text-sm text-gray-500"
                                >
                                    Tidak ada tiket yang cocok dengan filter
                                    saat ini.
                                </td>
                            </tr>
                        ) : (
                            tickets.data.map((t) => (
                                <tr key={t.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs">
                                        <Link
                                            href={route('tickets.show', t.id)}
                                            className="text-brand-600 hover:underline"
                                        >
                                            {t.ticket_code}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{t.title}</td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {t.category?.name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {t.requestor?.name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {t.assignee?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <PriorityBadge
                                            priority={t.priority}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusBadge status={t.status} />
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">
                                        {new Date(
                                            t.created_at,
                                        ).toLocaleDateString('id-ID')}
                                    </td>
                                    {isAdmin && (
                                        <td className="px-4 py-3">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    if (window.confirm(`Hapus tiket ${t.ticket_code}? Tindakan ini tidak dapat dibatalkan.`)) {
                                                        router.delete(route('tickets.destroy', t.id));
                                                    }
                                                }}
                                                className="rounded border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    )}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {tickets.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {tickets.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            className={`rounded-md border px-3 py-1 text-sm ${
                                link.active
                                    ? 'border-brand-600 bg-brand-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                            } ${!link.url ? 'opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
