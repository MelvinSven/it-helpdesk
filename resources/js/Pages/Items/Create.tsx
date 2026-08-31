import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { ChangeEventHandler, FormEventHandler, useState } from 'react';

function formatMacAddress(value: string): string {
    const hex = value
        .replace(/[^0-9a-fA-F]/g, '')
        .toUpperCase()
        .slice(0, 12);

    return hex.match(/.{1,2}/g)?.join('-') ?? '';
}


export default function Create() {
    const [tooManyImages, setTooManyImages] = useState(false);
    const { data, setData, post, processing, errors, progress } = useForm<{
        kode_barang: string;
        serial_number: string;
        item_name: string;
        brand_name: string;
        mac_address: string;
        type: string;
        condition: string;
        description: string;
        images: File[];
    }>({
        kode_barang: '',
        serial_number: '',
        item_name: '',
        brand_name: '',
        mac_address: '',
        type: '',
        condition: 'baik',
        description: '',
        images: [],
    });

    // Server-side rules land on `images` and `images.0`, `images.1`, ...
    // so collect them all rather than reading a single key.
    const imageError = Object.entries(errors)
        .filter(([key]) => key === 'images' || key.startsWith('images.'))
        .map(([, message]) => message)
        .join(' ');

    const pickImages: ChangeEventHandler<HTMLInputElement> = (e) => {
        const files = Array.from(e.target.files ?? []);

        // PHP's max_file_uploads (default 20) silently drops extra files,
        // so reject oversized batches before they reach the server.
        if (files.length > 20) {
            setTooManyImages(true);
            e.target.value = '';
            setData('images', []);

            return;
        }

        setTooManyImages(false);
        setData('images', files);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('items.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Barang Baru
                </h1>
            }
        >
            <Head title="Barang Baru" />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form onSubmit={submit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="kode_barang"
                                value="Kode Barang"
                            />
                            <TextInput
                                id="kode_barang"
                                value={data.kode_barang}
                                onChange={(e) =>
                                    setData('kode_barang', e.target.value)
                                }
                                className="mt-1 block w-full"
                                isFocused
                            />
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
                                    setData('item_name', e.target.value)
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
                                    setData('brand_name', e.target.value)
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
                                    setData(
                                        'mac_address',
                                        formatMacAddress(e.target.value),
                                    )
                                }
                                className="mt-1 block w-full"
                                placeholder="00-1A-2B-3C-4D-5E"
                                inputMode="text"
                                maxLength={17}
                            />
                            <InputError
                                className="mt-2"
                                message={errors.mac_address}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="type"
                                value="Tipe"
                            />
                            <TextInput
                                id="type"
                                value={data.type}
                                onChange={(e) =>
                                    setData('type', e.target.value)
                                }
                                className="mt-1 block w-full"
                                placeholder="Laptop, Monitor, Mouse..."
                            />
                            <InputError className="mt-2" message={errors.type} />
                        </div>
                        <div>
                            <InputLabel htmlFor="condition" value="Kondisi" />
                            <select
                                id="condition"
                                value={data.condition}
                                onChange={(e) =>
                                    setData('condition', e.target.value)
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
                        <InputLabel
                            htmlFor="images"
                            value="Gambar Barang (opsional)"
                        />
                        <input
                            id="images"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            onChange={pickImages}
                            className="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            JPG, PNG, atau WEBP. Maksimal 20MB per gambar,
                            hingga 20 gambar. Gambar pertama menjadi gambar
                            utama, sisanya masuk ke galeri.
                        </p>
                        {data.images.length > 0 && (
                            <ul className="mt-2 space-y-1 text-xs text-gray-600">
                                {data.images.map((file, i) => (
                                    <li key={`${file.name}-${i}`}>
                                        {i === 0 ? '★ ' : '• '}
                                        {file.name}{' '}
                                        <span className="text-gray-400">
                                            ({(file.size / 1024 / 1024).toFixed(1)}{' '}
                                            MB)
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {progress && (
                            <p className="mt-1 text-xs text-gray-500">
                                Mengunggah: {progress.percentage}%
                            </p>
                        )}
                        <InputError
                            className="mt-2"
                            message={
                                tooManyImages
                                    ? 'Maksimal 20 gambar per unggahan.'
                                    : imageError
                            }
                        />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>
                            Simpan Barang
                        </PrimaryButton>
                    </div>
                </form>
            </div>
            
        </AuthenticatedLayout>
    );
}

