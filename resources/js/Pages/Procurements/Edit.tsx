import ImageLightbox from '@/Components/ImageLightbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { ProcurementRequest } from '@/types';
import { FormEventHandler } from 'react';

const isPdf = (path: string) => path.toLowerCase().endsWith('.pdf');

export default function Edit({
    procurement,
}: {
    procurement: ProcurementRequest;
}) {
    const { data, setData, post, processing, errors, progress } = useForm<{
        _method: string;
        request_number: string;
        employee_name: string;
        requested_item: string;
        request_date: string;
        notes: string;
        form_file: File | null;
    }>({
        _method: 'patch',
        request_number: procurement.request_number,
        employee_name: procurement.employee_name,
        requested_item: procurement.requested_item,
        request_date: procurement.request_date.slice(0, 10),
        notes: procurement.notes ?? '',
        form_file: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        // Method-spoofed POST so the file upload is parsed by PHP
        // (multipart bodies aren't read on a real PATCH request).
        post(route('admin.procurements.update', procurement.id), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Ubah Pengajuan Barang
                </h1>
            }
        >
            <Head title={`Ubah ${procurement.request_number}`} />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form onSubmit={submit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="request_number"
                                value="Nomor Pengajuan"
                            />
                            <TextInput
                                id="request_number"
                                value={data.request_number}
                                onChange={(e) =>
                                    setData('request_number', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.request_number}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="request_date"
                                value="Tanggal Pengajuan"
                            />
                            <TextInput
                                id="request_date"
                                type="date"
                                value={data.request_date}
                                onChange={(e) =>
                                    setData('request_date', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.request_date}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="employee_name"
                                value="Nama Karyawan"
                            />
                            <TextInput
                                id="employee_name"
                                value={data.employee_name}
                                onChange={(e) =>
                                    setData('employee_name', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.employee_name}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="requested_item"
                                value="Barang yang Diajukan"
                            />
                            <TextInput
                                id="requested_item"
                                value={data.requested_item}
                                onChange={(e) =>
                                    setData('requested_item', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.requested_item}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="notes"
                            value="Keterangan (opsional)"
                        />
                        <textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                        <InputError className="mt-2" message={errors.notes} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="form_file"
                            value="Foto Form Pengajuan"
                        />
                        <div className="mt-1 flex items-center gap-4">
                            {procurement.form_file ? (
                                isPdf(procurement.form_file) ? (
                                    <a
                                        href={`/storage/${procurement.form_file}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center rounded bg-red-50 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-100"
                                    >
                                        PDF · Lihat
                                    </a>
                                ) : (
                                    <ImageLightbox
                                        src={`/storage/${procurement.form_file}`}
                                        alt="Form pengajuan"
                                        thumbClassName="h-16 w-16 shrink-0 rounded ring-1 ring-gray-200"
                                    />
                                )
                            ) : (
                                <div className="flex h-16 w-16 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">
                                    Tidak ada
                                </div>
                            )}
                            <input
                                id="form_file"
                                type="file"
                                accept="image/jpeg,image/png,image/webp,application/pdf"
                                onChange={(e) =>
                                    setData(
                                        'form_file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                            />
                        </div>
                        <p className="mt-1 text-xs text-gray-500">
                            Pilih berkas baru untuk mengganti yang sekarang. JPG,
                            PNG, WEBP, atau PDF. Maksimal 5MB.
                        </p>
                        {progress && (
                            <p className="mt-1 text-xs text-gray-500">
                                Mengunggah: {progress.percentage}%
                            </p>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.form_file}
                        />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
