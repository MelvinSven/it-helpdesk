import { InputHTMLAttributes } from 'react';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-brand-400 dark:focus:ring-offset-slate-900 ' +
                className
            }
        />
    );
}
