import ImageLightbox from '@/Components/ImageLightbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { BorrowRecord, ItemCondition } from '@/types';
import { ReactNode } from 'react';

const conditionLabel: Record<ItemCondition, string> = {
    baru: 'Baru',
    baik: 'Baik',
    rusak_ringan: 'Rusak Ringan',
    rusak_berat: 'Rusak Berat',
};

const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('id-ID') : '—';

function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm text-gray-500">{label}</dt>
            <dd className="text-sm text-gray-900 sm:col-span-2">{children}</dd>
        </div>
    );
}

function Photo({ path, alt }: { path: string | null; alt: string }) {
    if (!path) return <span className="text-gray-400">—</span>;
    return (
        <ImageLightbox
            src={`/storage/${path}`}
            alt={alt}
            thumbClassName="h-28 w-28 rounded-md ring-1 ring-gray-200"
        />
    );
}

export default function Show({
    borrow,
    can_return,
    can_delete,
}: {
    borrow: BorrowRecord;
    can_return: boolean;
    can_delete: boolean;
}) {
    const returned = borrow.status === 'returned';

    const remove = () => {
        if (
            confirm(
                `Hapus data peminjaman ${borrow.item_name} oleh ${borrow.borrower_name}? Tindakan ini tidak dapat dibatalkan.`,
            )
        ) {
            router.delete(route('borrows.destroy', borrow.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Detail Peminjaman
                </h1>
            }
        >
            <Head title={`Detail · ${borrow.item_name}`} />

            <div className="max-w-3xl space-y-6">
                <div className="flex items-center justify-between">
                    <Link
                        href={route('borrows.index')}
                        className="text-sm text-brand-600 hover:underline"
                    >
                        ← Kembali ke Peminjaman
                    </Link>
                    <div className="flex items-center gap-2">
                        {can_return && (
                            <Link
                                href={route('borrows.return.create', borrow.id)}
                                className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700"
                            >
                                Kembalikan
                            </Link>
                        )}
                        {can_delete && (
                            <button
                                type="button"
                                onClick={remove}
                                className="rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                            >
                                Hapus
                            </button>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <h2 className="text-sm font-semibold text-gray-900">
                            Peminjaman
                        </h2>
                        {returned ? (
                            <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                Dikembalikan
                            </span>
                        ) : (
                            <span className="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">
                                Dipinjam
                            </span>
                        )}
                    </div>
                    <dl className="divide-y divide-gray-100">
                        <Row label="Nama Barang">{borrow.item_name}</Row>
                        <Row label="Nomor Barang">
                            <span className="font-mono">
                                {borrow.serial_number}
                            </span>
                        </Row>
                        <Row label="Peminjam">{borrow.borrower_name}</Row>
                        <Row label="Tanggal Pinjam">
                            {fmtDate(borrow.borrow_date)}
                        </Row>
                        <Row label="Kegunaan">
                            <span className="whitespace-pre-wrap">
                                {borrow.purpose}
                            </span>
                        </Row>
                        <Row label="Foto Peminjaman">
                            <Photo
                                path={borrow.borrow_image}
                                alt="Foto peminjaman"
                            />
                        </Row>
                    </dl>
                </div>

                {returned && (
                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <h2 className="text-sm font-semibold text-gray-900">
                                Pengembalian
                            </h2>
                        </div>
                        <dl className="divide-y divide-gray-100">
                            <Row label="Tanggal Kembali">
                                {fmtDate(borrow.return_date)}
                            </Row>
                            <Row label="Kondisi Saat Kembali">
                                {borrow.return_condition
                                    ? conditionLabel[borrow.return_condition]
                                    : '—'}
                            </Row>
                            <Row label="Catatan">
                                {borrow.notes ? (
                                    <span className="whitespace-pre-wrap">
                                        {borrow.notes}
                                    </span>
                                ) : (
                                    '—'
                                )}
                            </Row>
                            <Row label="Foto Pengembalian">
                                <Photo
                                    path={borrow.return_image}
                                    alt="Foto pengembalian"
                                />
                            </Row>
                        </dl>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
