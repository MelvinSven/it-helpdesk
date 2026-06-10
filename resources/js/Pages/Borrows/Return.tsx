import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface ReturnTarget {
    id: number;
    item_name: string;
    serial_number: string;
    borrower_name: string;
}

const today = new Date().toISOString().slice(0, 10);

export default function Return({
    borrow,
    can_return,
}: {
    borrow: ReturnTarget;
    can_return: boolean;
}) {
    const { data, setData, post, processing, errors, progress } = useForm<{
        _method: string;
        return_date: string;
        return_condition: string;
        notes: string;
        return_image: File | null;
    }>({
        _method: 'patch',
        return_date: today,
        return_condition: 'baik',
        notes: '',
        return_image: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!can_return) return; // Only the borrower may submit.
        // Method-spoofed POST so the optional file upload is parsed by PHP
        // (multipart bodies aren't read on a real PATCH request).
        post(route('borrows.return.store', borrow.id), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Kembalikan Barang
                </h1>
            }
        >
            <Head title="Kembalikan Barang" />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                {!can_return && (
                    <div className="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Hanya peminjam (
                        <span className="font-medium">
                            {borrow.borrower_name}
                        </span>
                        ) atau admin yang dapat mengembalikan barang ini. Anda
                        hanya dapat melihat data peminjaman.
                    </div>
                )}

                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <InputLabel htmlFor="item_name" value="Nama Barang" />
                        <TextInput
                            id="item_name"
                            value={borrow.item_name}
                            className="mt-1 block w-full bg-gray-50"
                            readOnly
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="serial_number"
                                value="Nomor"
                            />
                            <TextInput
                                id="serial_number"
                                value={borrow.serial_number}
                                className="mt-1 block w-full bg-gray-50 font-mono"
                                readOnly
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="borrower_name"
                                value="Nama Peminjam"
                            />
                            <TextInput
                                id="borrower_name"
                                value={borrow.borrower_name}
                                className="mt-1 block w-full bg-gray-50"
                                readOnly
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="return_date"
                                value="Tanggal Kembali"
                            />
                            <TextInput
                                id="return_date"
                                type="date"
                                value={data.return_date}
                                onChange={(e) =>
                                    setData('return_date', e.target.value)
                                }
                                className="mt-1 block w-full disabled:bg-gray-50 disabled:text-gray-500"
                                disabled={!can_return}
                            />
                            <InputError
                                className="mt-2"
                                message={errors.return_date}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="return_condition"
                                value="Kondisi Saat Kembali"
                            />
                            <select
                                id="return_condition"
                                value={data.return_condition}
                                onChange={(e) =>
                                    setData('return_condition', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-50 disabled:text-gray-500"
                                disabled={!can_return}
                            >
                                <option value="baru">Baru</option>
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">
                                    Rusak Ringan
                                </option>
                                <option value="rusak_berat">Rusak Berat</option>
                            </select>
                            <InputError
                                className="mt-2"
                                message={errors.return_condition}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="notes"
                            value="Catatan (opsional)"
                        />
                        <textarea
                            id="notes"
                            rows={4}
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-50 disabled:text-gray-500"
                            placeholder="Kerusakan, kelengkapan, atau catatan lain..."
                            disabled={!can_return}
                        />
                        <InputError className="mt-2" message={errors.notes} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="return_image"
                            value="Foto Pengembalian"
                        />
                        <input
                            id="return_image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            required
                            onChange={(e) =>
                                setData(
                                    'return_image',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                            disabled={!can_return}
                            className="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 disabled:opacity-50"
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            JPG, PNG, atau WEBP. Maksimal 5MB.
                        </p>
                        {progress && (
                            <p className="mt-1 text-xs text-gray-500">
                                Mengunggah: {progress.percentage}%
                            </p>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.return_image}
                        />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing || !can_return}>
                            Kembalikan Barang
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
