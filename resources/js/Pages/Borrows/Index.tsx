import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { BorrowRecord, ItemCondition, Paginated } from '@/types';
import { useState } from 'react';

interface Props {
    borrows: Paginated<BorrowRecord>;
    filters: { status?: string; search?: string };
    can: { delete: boolean };
}

const conditionLabel: Record<ItemCondition, string> = {
    baru: 'Baru',
    baik: 'Baik',
    rusak_ringan: 'Rusak Ringan',
    rusak_berat: 'Rusak Berat',
};

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('id-ID') : '—';

export default function Index({ borrows, filters, can }: Props) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        status: filters.status ?? 'all',
    });

    const apply = (next = form) => {
        router.get(route('borrows.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    const remove = (b: BorrowRecord) => {
        if (
            confirm(
                `Hapus data peminjaman ${b.item_name} oleh ${b.borrower_name}? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(route('borrows.destroy', b.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Peminjaman
                </h1>
            }
        >
            <Head title="Peminjaman" />

            <div className="mb-4 flex flex-wrap items-center gap-2 justify-between">
                <div className='flex flex-wrap items-center gap-2'>
                    <input
                        type="text"
                        value={form.search}
                        onChange={(e) =>
                            setForm({ ...form, search: e.target.value })
                        }
                        onKeyDown={(e) => e.key === 'Enter' && apply()}
                        placeholder="Cari barang, nomor barang, peminjam..."
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
                        <option value="all">Semua</option>
                        <option value="borrowed">Sedang Dipinjam</option>
                        <option value="returned">Sudah Dikembalikan</option>
                    </select>
                </div>
                
                <div>
                    <Link
                        href={route('borrows.create')}
                        className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700"
                    >
                        Pinjam Barang
                    </Link>
                </div>
            </div>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr className="text-left text-xs uppercase text-gray-500">
                            <th className="px-4 py-3">Nomor</th>
                            <th className="px-4 py-3">Nama Barang</th>
                            <th className="px-4 py-3">Peminjam</th>
                            <th className="px-4 py-3">Tgl Pinjam</th>
                            <th className="px-4 py-3">Kegunaan</th>
                            <th className="px-4 py-3">Foto</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Pengembalian</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {borrows.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={9}
                                    className="px-4 py-10 text-center text-sm text-gray-500"
                                >
                                    Tidak ada data peminjaman.
                                </td>
                            </tr>
                        ) : (
                            borrows.data.map((b) => (
                                <tr key={b.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {b.serial_number}
                                    </td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {b.item_name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {b.borrower_name}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">
                                        {fmtDate(b.borrow_date)}
                                    </td>
                                    <td className="px-4 py-3 max-w-xs truncate text-gray-600">
                                        {b.purpose}
                                    </td>
                                    <td className="px-4 py-3">
                                        {b.borrow_image ? (
                                            <a
                                                href={`/storage/${b.borrow_image}`}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <img
                                                    src={`/storage/${b.borrow_image}`}
                                                    alt="Foto peminjaman"
                                                    className="h-10 w-10 rounded object-cover"
                                                />
                                            </a>
                                        ) : (
                                            <span className="text-gray-400">
                                                —
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {b.status === 'borrowed' ? (
                                            <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                                Dipinjam
                                            </span>
                                        ) : (
                                            <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                                Dikembalikan
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-xs text-gray-500">
                                        {b.status === 'returned' ? (
                                            <>
                                                {fmtDate(b.return_date)}
                                                {b.return_condition && (
                                                    <span className="ml-1 text-gray-400">
                                                        (
                                                        {
                                                            conditionLabel[
                                                                b
                                                                    .return_condition
                                                            ]
                                                        }
                                                        )
                                                    </span>
                                                )}
                                            </>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-3">
                                            <Link
                                                href={route('borrows.show', b.id)}
                                                className="rounded border border-brand-600 bg-white px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50"
                                            >
                                                Detail
                                            </Link>
                                            {b.status === 'borrowed' && (
                                                <Link
                                                    href={route(
                                                        'borrows.return.create',
                                                        b.id,
                                                    )}
                                                    className="rounded-md bg-brand-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-brand-700"
                                                >
                                                    Kembalikan
                                                </Link>
                                            )}
                                            {can.delete && (
                                                <button
                                                    type="button"
                                                    onClick={() => remove(b)}
                                                    className="rounded border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                                >
                                                    Hapus
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {borrows.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {borrows.links.map((link, i) => (
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
