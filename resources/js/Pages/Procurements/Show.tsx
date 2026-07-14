import ImageLightbox from '@/Components/ImageLightbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ProcurementRequest } from '@/types';
import { ReactNode } from 'react';

const isPdf = (path: string) => path.toLowerCase().endsWith('.pdf');

const fmtDate = (d: string | null) =>
    d
        ? new Date(d).toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'long',
              year: 'numeric',
          })
        : '—';

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm text-gray-500">{label}</dt>
            <dd className="text-sm text-gray-900 sm:col-span-2">{children}</dd>
        </div>
    );
}

function FormFile({ path }: { path: string | null }) {
    if (!path) return <span className="text-gray-400">—</span>;

    const url = `/storage/${path}`;

    if (isPdf(path)) {
        return (
            <a
                href={url}
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-1 rounded bg-red-50 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-100"
            >
                PDF · Lihat
            </a>
        );
    }

    return (
        <ImageLightbox
            src={url}
            alt="Form pengajuan"
            thumbClassName="h-40 w-40 rounded-md ring-1 ring-gray-200"
        />
    );
}

function StatusPill({ status }: { status: 'available' | 'borrowed' }) {
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

export default function Show({
    procurement,
}: {
    procurement: ProcurementRequest;
}) {
    const items = procurement.items ?? [];

    const remove = () => {
        if (
            confirm(
                `Hapus pengajuan ${procurement.request_number}? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(route('admin.procurements.destroy', procurement.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Detail Pengajuan Barang
                </h1>
            }
        >
            <Head title={`Detail · ${procurement.request_number}`} />

            <div className="max-w-3xl space-y-6">
                <div className="flex items-center justify-between">
                    <Link
                        href={route('admin.procurements.index')}
                        className="text-sm text-brand-600 hover:underline"
                    >
                        ← Kembali ke Pengajuan Barang
                    </Link>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route(
                                'admin.procurements.edit',
                                procurement.id,
                            )}
                            className="rounded-md border border-brand-600 bg-white px-3 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50"
                        >
                            Ubah
                        </Link>
                        <button
                            type="button"
                            onClick={remove}
                            className="rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                        >
                            Hapus
                        </button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <h2 className="text-sm font-semibold text-gray-900">
                            Pengajuan
                        </h2>
                    </div>
                    <dl className="divide-y divide-gray-100">
                        <Row label="Nomor Pengajuan">
                            <span className="font-mono">
                                {procurement.request_number}
                            </span>
                        </Row>
                        <Row label="Nama Karyawan">
                            {procurement.employee_name}
                        </Row>
                        <Row label="Barang yang Diajukan">
                            {procurement.requested_item}
                        </Row>
                        <Row label="Tanggal Pengajuan">
                            {fmtDate(procurement.request_date)}
                        </Row>
                        <Row label="Keterangan">
                            {procurement.notes ? (
                                <span className="whitespace-pre-wrap">
                                    {procurement.notes}
                                </span>
                            ) : (
                                '—'
                            )}
                        </Row>
                        <Row label="Foto Form Pengajuan">
                            <FormFile path={procurement.form_file} />
                        </Row>
                    </dl>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <h2 className="text-sm font-semibold text-gray-900">
                            Barang Terkait
                        </h2>
                        <span className="text-xs text-gray-500">
                            {items.length} barang
                        </span>
                    </div>
                    {items.length === 0 ? (
                        <p className="px-4 py-6 text-center text-sm text-gray-500">
                            Belum dihubungkan ke barang mana pun.
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {items.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between px-4 py-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium text-gray-900">
                                            {item.item_name}
                                        </p>
                                        <p className="font-mono text-xs text-gray-500">
                                            {item.serial_number} ·{' '}
                                            {item.brand_name} · {item.type}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <StatusPill status={item.status} />
                                        <Link
                                            href={route('items.show', item.id)}
                                            className="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            Detail
                                        </Link>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
