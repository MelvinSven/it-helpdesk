import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

function formatMacAddress(value: string): string {
    const hex = value
        .replace(/[^0-9a-fA-F]/g, '')
        .toUpperCase()
        .slice(0, 12);

    return hex.match(/.{1,2}/g)?.join('-') ?? '';
}


export default function Create() {
    const { data, setData, post, processing, errors, progress } = useForm<{
        serial_number: string;
        item_name: string;
        brand_name: string;
        mac_address: string;
        type: string;
        condition: string;
        item_image: File | null;
    }>({
        serial_number: '',
        item_name: '',
        brand_name: '',
        mac_address: '',
        type: '',
        condition: 'baik',
        item_image: null,
    });

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
                                htmlFor="serial_number"
                                value="Nomor"
                            />
                            <TextInput
                                id="serial_number"
                                value={data.serial_number}
                                onChange={(e) =>
                                    setData('serial_number', e.target.value)
                                }
                                className="mt-1 block w-full"
                                isFocused
                            />
                            <InputError
                                className="mt-2"
                                message={errors.serial_number}
                            />
                        </div>
                        <div>
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
                            htmlFor="item_image"
                            value="Gambar Barang (opsional)"
                        />
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
                            className="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
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
                            message={errors.item_image}
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

