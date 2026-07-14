import Modal from '@/Components/Modal';
import { useState } from 'react';

/**
 * Thumbnail that opens the full-size image in a modal when clicked.
 * Size/rounding come from `thumbClassName`; the image fills the button.
 */
export default function ImageLightbox({
    src,
    alt,
    thumbClassName,
}: {
    src: string;
    alt: string;
    thumbClassName: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className={`cursor-zoom-in overflow-hidden focus:outline-none focus:ring-2 focus:ring-brand-500 ${thumbClassName}`}
                aria-label={`Perbesar gambar ${alt}`}
            >
                <img
                    src={src}
                    alt={alt}
                    className="h-full w-full object-cover"
                />
            </button>
            <Modal show={open} maxWidth="2xl" onClose={() => setOpen(false)}>
                <img
                    src={src}
                    alt={alt}
                    className="max-h-[80vh] w-full cursor-zoom-out object-contain"
                    onClick={() => setOpen(false)}
                />
                <div className="flex justify-end border-t border-gray-100 px-4 py-2">
                    <a
                        href={src}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-xs font-medium text-brand-600 hover:text-brand-700"
                    >
                        Buka ukuran penuh ↗
                    </a>
                </div>
            </Modal>
        </>
    );
}
