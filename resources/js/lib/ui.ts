export type ButtonVariant = 'primary' | 'brand' | 'secondary' | 'danger' | 'ghost';

const orangeAction =
    'bg-linear-to-br from-orange-500 via-orange-600 to-amber-700 text-white shadow-md shadow-orange-950/20 hover:from-orange-600 hover:via-orange-700 hover:to-amber-800 focus-visible:ring-orange-500 dark:from-orange-400 dark:via-orange-500 dark:to-amber-500 dark:text-orange-950 dark:shadow-black/30';

const buttonVariants: Record<ButtonVariant, string> = {
    primary: orangeAction,
    brand: orangeAction,
    secondary:
        'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:ring-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
    danger: 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus-visible:ring-rose-500',
    ghost: 'text-slate-600 hover:bg-slate-100 focus-visible:ring-slate-400 dark:text-slate-300 dark:hover:bg-slate-800',
};

export function buttonClass(variant: ButtonVariant = 'primary', extra = ''): string {
    return [
        'inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus-visible:ring-offset-slate-950',
        buttonVariants[variant],
        extra,
    ]
        .filter(Boolean)
        .join(' ');
}

export const inputClass =
    'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 disabled:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:disabled:bg-slate-800';

export const authInputClass =
    'w-full rounded-xl border border-slate-300/90 bg-white/90 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/15 disabled:bg-slate-100 dark:border-orange-100/10 dark:bg-[#1f160f]/80 dark:text-orange-50 dark:placeholder:text-orange-100/35 dark:disabled:bg-[#251a11]';

export const labelClass = 'mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200';

export const errorClass = 'mt-1 text-xs font-medium text-rose-600 dark:text-rose-400';

export const cardClass =
    'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';

export const thClass =
    'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';

export const tdClass = 'px-4 py-3 text-sm text-slate-700 dark:text-slate-200';

export const inlineInputClass =
    'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100';
