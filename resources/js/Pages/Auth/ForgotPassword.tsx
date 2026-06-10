import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Lupa Kata Sandi" />

            <div className="mb-6 text-center">
                <h1 className="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Lupa Kata Sandi
                </h1>
                <p className="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Khusus akun admin
                </p>
            </div>

            <p className="mb-4 text-sm text-gray-600 dark:text-slate-400">
                Masukkan email admin Anda. Jika cocok dengan akun admin, kami akan
                mengirim tautan untuk mengatur ulang kata sandi.
            </p>

            {status && (
                <div className="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="admin@lixicon.local"
                        className="mt-1.5 block w-full"
                        autoComplete="email"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-900"
                >
                    {processing ? 'Mengirim…' : 'Kirim Tautan Reset'}
                </button>
            </form>

            <div className="mt-6 text-center">
                <Link
                    href={route('login')}
                    className="text-sm font-medium text-brand-600 transition hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                >
                    Kembali ke halaman masuk
                </Link>
            </div>
        </GuestLayout>
    );
}
