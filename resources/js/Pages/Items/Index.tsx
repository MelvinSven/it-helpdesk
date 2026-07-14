import ImageLightbox from '@/Components/ImageLightbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Item, ItemCondition, Paginated, PageProps } from '@/types';
import { useRef, useState } from 'react';

interface Props {
    items: Paginated<Item>;
    filters: { status?: string; condition?: string; search?: string };
    can: { manage: boolean };
}

const conditionLabel: Record<ItemCondition, string> = {
    baru: 'Baru',
    baik: 'Baik',
    rusak_ringan: 'Rusak Ringan',
    rusak_berat: 'Rusak Berat',
};

function StatusPill({ status }: { status: Item['status'] }) {
    return status === 'available' ? (
        <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
            Tersedia
        </span>
    ) : (
        <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
            Dipinjam
        </span>
    );
}

export default function Index({ items, filters, can }: Props) {
    const { flash } = usePage<PageProps>().props;

    const [form, setForm] = useState({
        search: filters.search ?? '',
        status: filters.status ?? '',
        condition: filters.condition ?? '',
    });

    const [showImport, setShowImport] = useState(false);
    const fileRef = useRef<HTMLInputElement>(null);

    const {
        data: importData,
        setData: setImportData,
        post: postImport,
        processing: importing,
        errors: importErrors,
        reset: resetImport,
    } = useForm<{ file: File | null }>({ file: null });

    const apply = (next = form) => {
        router.get(route('items.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        postImport(route('items.import'), {
            forceFormData: true,
            onSuccess: () => {
                setShowImport(false);
                resetImport();
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    const closeImport = () => {
        setShowImport(false);
        resetImport();
        if (fileRef.current) fileRef.current.value = '';
    };

    const remove = (item: Item) => {
        if (
            confirm(
                `Hapus barang ${item.item_name}? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(route('items.destroy', item.id), {
                preserveScroll: true,
            });
        }
    };

    // The Aksi column always renders (Detail for everyone); Ubah / Hapus are
    // added for admins.
    const colSpan = 9;

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">Barang</h1>
            }
        >
            <Head title="Barang" />

            {flash?.success && (
                <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {flash.error}
                </div>
            )}

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div className="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        value={form.search}
                        onChange={(e) =>
                            setForm({ ...form, search: e.target.value })
                        }
                        onKeyDown={(e) => e.key === 'Enter' && apply()}
                        placeholder="Cari nama, nomor, merek, MAC..."
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
                        <option value="available">Tersedia</option>
                        <option value="borrowed">Dipinjam</option>
                    </select>
                    <select
                        value={form.condition}
                        onChange={(e) => {
                            const next = { ...form, condition: e.target.value };
                            setForm(next);
                            apply(next);
                        }}
                        className="rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Semua Kondisi</option>
                        <option value="baru">Baru</option>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </div>
                <div className="flex items-center gap-2">
                    {can.manage && (
                        <>
                            <button
                                type="button"
                                onClick={() => setShowImport(true)}
                                className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Impor Excel
                            </button>
                            <Link
                                href={route('items.create')}
                                className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Tambahkan Barang
                            </Link>
                        </>
                    )}
                </div>
            </div>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr className="text-left text-xs uppercase text-gray-500">
                            <th className="px-4 py-3">Gambar</th>
                            <th className="px-4 py-3">Nomor</th>
                            <th className="px-4 py-3">Nama Barang</th>
                            <th className="px-4 py-3">Merek</th>
                            <th className="px-4 py-3">MAC Address</th>
                            <th className="px-4 py-3">Tipe</th>
                            <th className="px-4 py-3">Kondisi</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {items.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={colSpan}
                                    className="px-4 py-10 text-center text-sm text-gray-500"
                                >
                                    Tidak ada barang yang cocok dengan filter
                                    saat ini.
                                </td>
                            </tr>
                        ) : (
                            items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        {item.item_image ? (
                                            <ImageLightbox
                                                src={`/storage/${item.item_image}`}
                                                alt={item.item_name}
                                                thumbClassName="h-10 w-10 rounded"
                                            />
                                        ) : (
                                            <div className="flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">
                                                —
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {item.serial_number}
                                    </td>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {item.item_name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {item.brand_name}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs text-gray-600">
                                        {item.mac_address ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {item.type}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {conditionLabel[item.condition]}
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusPill status={item.status} />
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-3">
                                            <Link
                                                href={route(
                                                    'items.show',
                                                    item.id,
                                                )}
                                                className="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                            >
                                                Detail
                                            </Link>
                                            {can.manage && (
                                                <>
                                                    <Link
                                                        href={route(
                                                            'items.edit',
                                                            item.id,
                                                        )}
                                                        className="rounded border border-brand-600 bg-white px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50"
                                                    >
                                                        Ubah
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            remove(item)
                                                        }
                                                        disabled={
                                                            item.status ===
                                                            'borrowed'
                                                        }
                                                        className="rounded border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                                    >
                                                        Hapus
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {items.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {items.links.map((link, i) => (
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

            {/* Import Modal */}
            {showImport && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <h2 className="mb-1 text-lg font-semibold text-gray-900">
                            Impor Barang dari Excel
                        </h2>
                        <p className="mb-4 text-sm text-gray-500">
                            File harus berformat <strong>.xlsx</strong> atau{' '}
                            <strong>.xls</strong> dengan header:{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">
                                Nomor Seri
                            </code>
                            ,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">
                                Nama Barang
                            </code>
                            ,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">
                                Merek
                            </code>
                            ,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">
                                MAC Address
                            </code>
                            ,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">
                                Tipe
                            </code>
                            ,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">
                                Kondisi
                            </code>
                            . Kolom MAC Address bersifat opsional. Nilai Kondisi:{' '}
                            <em>Baru</em>, <em>Baik</em>, <em>Rusak Ringan</em>,
                            atau <em>Rusak Berat</em>. Status awal semua barang:{' '}
                            <strong>Tersedia</strong>.
                        </p>

                        <div className="mb-4">
                            <a
                                href={route('items.import.template')}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline"
                                download
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Unduh Template Excel
                            </a>
                        </div>

                        <form onSubmit={submitImport} className="space-y-4">
                            <div>
                                <input
                                    ref={fileRef}
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={(e) =>
                                        setImportData(
                                            'file',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-50"
                                />
                                {importErrors.file && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {importErrors.file}
                                    </p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeImport}
                                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={importing || !importData.file}
                                    className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                                >
                                    {importing ? 'Mengimpor...' : 'Impor'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
