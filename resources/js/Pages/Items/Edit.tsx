import ImageLightbox from '@/Components/ImageLightbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { Item } from '@/types';
import {
    KODE_BARANG_PATTERN,
    KODE_BARANG_PREFIX,
    formatKodeBarang,
    kodeBarangSegments,
} from '@/lib/itemFormat';
import { FormEventHandler } from 'react';

export default function Edit({ item }: { item: Item }) {
    const { data, setData, post, processing, errors, progress } = useForm<{
        _method: string;
        kode_barang: string;
        serial_number: string;
        item_name: string;
        brand_name: string;
        mac_address: string;
        type: string;
        condition: Item['condition'];
        description: string;
        item_image: File | null;
    }>({
        _method: 'patch',
        kode_barang: item.kode_barang,
        serial_number: item.serial_number ?? '',
        item_name: item.item_name,
        brand_name: item.brand_name,
        mac_address: item.mac_address ?? '',
        type: item.type,
        condition: item.condition,
        description: item.description ?? '',
        item_image: null,
    });

    // Codes from before the LIX-EL- format are shown as-is (never silently
    // renamed); the server rejects them until the admin retypes the code.
    const legacyKode =
        data.kode_barang === item.kode_barang &&
        !KODE_BARANG_PATTERN.test(item.kode_barang);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        // Method-spoofed POST so the image upload is parsed by PHP
        // (multipart bodies aren't read on a real PATCH request).
        post(route('items.update', item.id), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Ubah Barang
                </h1>
            }
        >
            <Head title={`Ubah ${item.item_name}`} />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div className="mb-5 rounded-md bg-gray-50 px-4 py-2 text-sm text-gray-600">
                    Status:{' '}
                    <span className="font-medium">
                        {item.status === 'available' ? 'Tersedia' : 'Dipinjam'}
                    </span>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="kode_barang"
                                value="Kode Barang"
                            />
                            <div className="mt-1 flex">
                                <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 font-mono text-sm text-gray-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                                    {KODE_BARANG_PREFIX}
                                </span>
                                <TextInput
                                    id="kode_barang"
                                    value={kodeBarangSegments(data.kode_barang)}
                                    onChange={(e) =>
                                        setData(
                                            'kode_barang',
                                            formatKodeBarang(e.target.value),
                                        )
                                    }
                                    className="block w-full min-w-0 rounded-l-none"
                                    placeholder="LAPTOP-001"
                                />
                            </div>
                            {legacyKode && (
                                <p className="mt-1 text-xs text-amber-700">
                                    Kode lama{' '}
                                    <span className="font-mono">
                                        {item.kode_barang}
                                    </span>{' '}
                                    belum sesuai format LIX-EL-XXX-XXX. Ketik
                                    ulang kode sebelum menyimpan.
                                </p>
                            )}
                            <InputError
                                className="mt-2"
                                message={errors.kode_barang}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="serial_number"
                                value="Nomor Seri (opsional)"
                            />
                            <TextInput
                                id="serial_number"
                                value={data.serial_number}
                                onChange={(e) =>
                                    setData('serial_number', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.serial_number}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <InputLabel htmlFor="item_name" value="Nama Barang" />
                            <TextInput
                                id="item_name"
                                value={data.item_name}
                                onChange={(e) =>
                                    setData(
                                        'item_name',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.item_name}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="brand_name" value="Merek" />
                            <TextInput
                                id="brand_name"
                                value={data.brand_name}
                                onChange={(e) =>
                                    setData(
                                        'brand_name',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.brand_name}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="mac_address"
                                value="MAC Address (opsional)"
                            />
                            <TextInput
                                id="mac_address"
                                value={data.mac_address}
                                onChange={(e) =>
                                    setData('mac_address', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.mac_address}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="type" value="Tipe" />
                            <TextInput
                                id="type"
                                value={data.type}
                                onChange={(e) =>
                                    setData('type', e.target.value.toUpperCase())
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError className="mt-2" message={errors.type} />
                        </div>
                        <div>
                            <InputLabel htmlFor="condition" value="Kondisi" />
                            <select
                                id="condition"
                                value={data.condition}
                                onChange={(e) =>
                                    setData(
                                        'condition',
                                        e.target.value as Item['condition'],
                                    )
                                }
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
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
                                message={errors.condition}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="description"
                            value="Deskripsi (opsional)"
                        />
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={4}
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Spesifikasi, software terpasang, catatan lainnya..."
                        />
                        <InputError
                            className="mt-2"
                            message={errors.description}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="item_image" value="Gambar Barang" />
                        <div className="mt-1 flex items-center gap-4">
                            {item.item_image ? (
                                <ImageLightbox
                                    src={`/storage/${item.item_image}`}
                                    alt={item.item_name}
                                    thumbClassName="h-16 w-16 shrink-0 rounded"
                                />
                            ) : (
                                <div className="flex h-16 w-16 items-center justify-center rounded bg-gray-100 text-xs text-gray-400">
                                    Tidak ada
                                </div>
                            )}
                            <input
                                id="item_image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(e) =>
                                    setData(
                                        'item_image',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                            />
                        </div>
                        <p className="mt-1 text-xs text-gray-500">
                            Pilih berkas baru untuk mengganti gambar saat ini.
                            JPG, PNG, atau WEBP. Maksimal 20MB.
                        </p>
                        {progress && (
                            <p className="mt-1 text-xs text-gray-500">
                                Mengunggah: {progress.percentage}%
                            </p>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.item_image}
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
