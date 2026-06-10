import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Paginated, ProcurementRequest } from '@/types';
import { useState } from 'react';

interface Props {
    procurements: Paginated<ProcurementRequest>;
    filters: { search?: string };
}

const isPdf = (path: string) => path.toLowerCase().endsWith('.pdf');

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

function FormFileCell({ path }: { path: string | null }) {
    if (!path) {
        return <span className="text-gray-400">—</span>;
    }

    const url = `/storage/${path}`;

    if (isPdf(path)) {
        return (
            <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100"
            >
                PDF · Lihat
            </a>
        );
    }

    return (
        
        // <a href={url} target="_blank" rel="noopener noreferrer">
        //     <img
        //         src={url}
        //         alt="Form pengajuan"
        //         className="h-10 w-10 rounded object-cover ring-1 ring-gray-200 hover:opacity-80"
        //     />
        // </a>
         <a
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100"
            >
            Foto · Lihat
        </a>
    );
}

export default function Index({ procurements, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const apply = () => {
        router.get(
            route('admin.procurements.index'),
            { search },
            { preserveState: true, replace: true },
        );
    };

    const remove = (item: ProcurementRequest) => {
        if (
            confirm(
                `Hapus pengajuan ${item.request_number}? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(route('admin.procurements.destroy', item.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Pengajuan Barang
                </h1>
            }
        >
            <Head title="Pengajuan Barang" />

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && apply()}
                    placeholder="Cari nomor, karyawan, barang..."
                    className="rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                />
                <Link
                    href={route('admin.procurements.create')}
                    className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Tambah Pengajuan
                </Link>
            </div>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr className="text-left text-xs uppercase text-gray-500">
                            <th className="px-4 py-3">Nomor Pengajuan</th>
                            <th className="px-4 py-3">Nama Karyawan</th>
                            <th className="px-4 py-3">Barang Diajukan</th>
                            <th className="px-4 py-3">Tanggal</th>
                            {/* <th className="px-4 py-3">Keterangan</th> */}
                            <th className="px-4 py-3">Form</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {procurements.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-10 text-center text-sm text-gray-500"
                                >
                                    Belum ada data pengajuan barang.
                                </td>
                            </tr>
                        ) : (
                            procurements.data.map((item) => (
                                <tr key={item.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {item.request_number}
                                    </td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {item.employee_name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {item.requested_item}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {formatDate(item.request_date)}
                                    </td>
                                    {/* <td className="px-4 py-3 max-w-xs truncate text-gray-600">
                                        {item.notes ?? '—'}
                                    </td> */}
                                    <td className="px-4 py-3">
                                        <FormFileCell path={item.form_file} />
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-3">
                                            <Link
                                                href={route(
                                                    'admin.procurements.show',
                                                    item.id,
                                                )}
                                                className="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Detail
                                            </Link>
                                            <Link
                                                href={route(
                                                    'admin.procurements.edit',
                                                    item.id,
                                                )}
                                                className="rounded border border-brand-600 bg-white px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50"
                                            >
                                                Ubah
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => remove(item)}
                                                className="rounded border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {procurements.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {procurements.links.map((link, i) => (
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
