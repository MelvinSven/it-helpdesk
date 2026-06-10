import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import {
    BorrowRecord,
    Item,
    ItemCondition,
    ProcurementRequest,
    ProcurementRequestOption,
} from '@/types';

interface Props {
    item: Item;
    borrows: BorrowRecord[];
    procurements: ProcurementRequest[];
    availableProcurements: ProcurementRequestOption[];
    can: { manage: boolean };
}

const conditionLabel: Record<ItemCondition, string> = {
    baru: 'Baru',
    baik: 'Baik',
    rusak_ringan: 'Rusak Ringan',
    rusak_berat: 'Rusak Berat',
};

const isPdf = (path: string) => path.toLowerCase().endsWith('.pdf');

const formatDate = (value: string | null) =>
    value
        ? new Date(value).toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          })
        : '—';

function BorrowStatusPill({ status }: { status: BorrowRecord['status'] }) {
    return status === 'borrowed' ? (
        <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
            Dipinjam
        </span>
    ) : (
        <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
            Dikembalikan
        </span>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs uppercase text-gray-500">{label}</dt>
            <dd className="mt-0.5 text-sm text-gray-900">{value}</dd>
        </div>
    );
}

export default function Show({
    item,
    borrows,
    procurements,
    availableProcurements,
    can,
}: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        procurement_request_id: string;
    }>({ procurement_request_id: '' });

    const attach: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('items.procurements.attach', item.id), {
            preserveScroll: true,
            onSuccess: () => reset('procurement_request_id'),
        });
    };

    const detach = (p: ProcurementRequest) => {
        if (confirm(`Lepas tautan pengajuan ${p.request_number} dari barang ini?`)) {
            router.delete(
                route('items.procurements.detach', [item.id, p.id]),
                { preserveScroll: true },
            );
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Detail Barang
                </h1>
            }
        >
            <Head title={item.item_name} />

            <div className="mb-4 flex items-center justify-between">
                <Link
                    href={route('items.index')}
                    className="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Kembali ke daftar
                </Link>
                {can.manage && (
                    <Link
                        href={route('items.edit', item.id)}
                        className="rounded-md border border-brand-600 bg-white px-3 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50"
                    >
                        Ubah Barang
                    </Link>
                )}
            </div>

            {/* Item info */}
            <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div className="flex flex-col gap-6 sm:flex-row">
                    {item.item_image ? (
                        <img
                            src={`/storage/${item.item_image}`}
                            alt={item.item_name}
                            className="h-32 w-32 shrink-0 rounded-lg object-cover ring-1 ring-gray-200"
                        />
                    ) : (
                        <div className="flex h-32 w-32 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm text-gray-400">
                            Tanpa gambar
                        </div>
                    )}
                    <div className="flex-1">
                        <div className="mb-4 flex items-center gap-3">
                            <h2 className="text-lg font-semibold text-gray-900">
                                {item.item_name}
                            </h2>
                            {item.status === 'available' ? (
                                <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                    Tersedia
                                </span>
                            ) : (
                                <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                    Dipinjam
                                </span>
                            )}
                        </div>
                        <dl className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <Field
                                label="Nomor"
                                value={item.serial_number}
                            />
                            <Field label="Merek" value={item.brand_name} />
                            <Field label="Tipe" value={item.type} />
                            <Field
                                label="MAC Address"
                                value={item.mac_address ?? '—'}
                            />
                            <Field
                                label="Kondisi"
                                value={conditionLabel[item.condition]}
                            />
                        </dl>
                    </div>
                </div>
            </div>

            {/* Borrowing logs */}
            <section className="mt-6">
                <h3 className="mb-2 text-sm font-semibold text-gray-900">
                    Log Peminjaman
                    <span className="ml-2 font-normal text-gray-500">
                        ({borrows.length})
                    </span>
                </h3>
                <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr className="text-left text-xs uppercase text-gray-500">
                                <th className="px-4 py-3">Tanggal Pinjam</th>
                                <th className="px-4 py-3">Peminjam</th>
                                <th className="px-4 py-3">Keperluan</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Tanggal Kembali</th>
                                <th className="px-4 py-3">Kondisi Kembali</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {borrows.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        Belum ada riwayat peminjaman.
                                    </td>
                                </tr>
                            ) : (
                                borrows.map((b) => (
                                    <tr key={b.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-gray-600">
                                            {formatDate(b.borrow_date)}
                                        </td>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {b.borrower_name}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {b.purpose}
                                        </td>
                                        <td className="px-4 py-3">
                                            <BorrowStatusPill status={b.status} />
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {formatDate(b.return_date)}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {b.return_condition
                                                ? conditionLabel[
                                                      b.return_condition
                                                  ]
                                                : '—'}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            {/* Linked procurement requests (admin only) */}
            {can.manage && (
                <section className="mt-6">
                    <h3 className="mb-2 text-sm font-semibold text-gray-900">
                        Pengajuan Barang Terkait
                        <span className="ml-2 font-normal text-gray-500">
                            ({procurements.length})
                        </span>
                    </h3>

                    {/* Attach an existing request by its number. */}
                    <form
                        onSubmit={attach}
                        className="mb-3 flex flex-wrap items-end gap-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <div className="flex-1">
                            <label
                                htmlFor="procurement_request_id"
                                className="block text-xs font-medium text-gray-700"
                            >
                                Hubungkan Pengajuan (berdasarkan nomor)
                            </label>
                            <select
                                id="procurement_request_id"
                                value={data.procurement_request_id}
                                onChange={(e) =>
                                    setData(
                                        'procurement_request_id',
                                        e.target.value,
                                    )
                                }
                                disabled={availableProcurements.length === 0}
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-50 disabled:text-gray-400"
                            >
                                <option value="">
                                    {availableProcurements.length === 0
                                        ? 'Tidak ada pengajuan yang tersedia'
                                        : 'Pilih nomor pengajuan…'}
                                </option>
                                {availableProcurements.map((opt) => (
                                    <option key={opt.id} value={opt.id}>
                                        {opt.request_number} —{' '}
                                        {opt.requested_item} ({opt.employee_name}
                                        )
                                    </option>
                                ))}
                            </select>
                            {errors.procurement_request_id && (
                                <p className="mt-1 text-xs text-red-600">
                                    {errors.procurement_request_id}
                                </p>
                            )}
                        </div>
                        <button
                            type="submit"
                            disabled={
                                processing || !data.procurement_request_id
                            }
                            className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            Hubungkan
                        </button>
                    </form>

                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr className="text-left text-xs uppercase text-gray-500">
                                    <th className="px-4 py-3">Nomor</th>
                                    <th className="px-4 py-3">Karyawan</th>
                                    <th className="px-4 py-3">Barang Diajukan</th>
                                    <th className="px-4 py-3">Tanggal</th>
                                    <th className="px-4 py-3">Form</th>
                                    <th className="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {procurements.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            Belum ada pengajuan barang yang
                                            terhubung.
                                        </td>
                                    </tr>
                                ) : (
                                    procurements.map((p) => (
                                        <tr
                                            key={p.id}
                                            className="hover:bg-gray-50"
                                        >
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {p.request_number}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-gray-900">
                                                {p.employee_name}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {p.requested_item}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                {formatDate(p.request_date)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {p.form_file ? (
                                                    <a
                                                        href={`/storage/${p.form_file}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className={`inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium ${
                                                            isPdf(p.form_file)
                                                                ? 'bg-red-50 text-red-600 hover:bg-red-100'
                                                                : 'bg-brand-50 text-brand-700 hover:bg-brand-100'
                                                        }`}
                                                    >
                                                        {isPdf(p.form_file)
                                                            ? 'PDF · Lihat'
                                                            : 'Gambar · Lihat'}
                                                    </a>
                                                ) : (
                                                    <span className="text-gray-400">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => detach(p)}
                                                    className="rounded border border-red-300 bg-white px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                                >
                                                    Lepas
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </AuthenticatedLayout>
    );
}
