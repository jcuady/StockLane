import { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';

type ProductRow = {
    id: number;
    sku: string;
    name: string;
    quantity: number;
    reorder_at: number;
    is_low_stock: boolean;
    unit_cost_cents: number;
};

type Summary = {
    sku_count: number;
    low_stock_count: number;
    units_on_hand: number;
};

type Props = {
    products: ProductRow[];
    summary: Summary;
};

export default function Index({ products, summary }: Props) {
    const [busyId, setBusyId] = useState<number | null>(null);

    function submitSale(e: FormEvent<HTMLFormElement>, productId: number) {
        e.preventDefault();
        const form = e.currentTarget;
        const units = Number(new FormData(form).get('units') || 1);
        setBusyId(productId);
        router.post(
            `/products/${productId}/sale`,
            { units },
            { onFinish: () => setBusyId(null), preserveScroll: true },
        );
    }

    function submitRestock(e: FormEvent<HTMLFormElement>, productId: number) {
        e.preventDefault();
        const form = e.currentTarget;
        const units = Number(new FormData(form).get('units') || 1);
        setBusyId(productId);
        router.post(
            `/products/${productId}/restock`,
            { units },
            { onFinish: () => setBusyId(null), preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Inventory" />
            <div className="min-h-[100dvh] bg-zinc-950 text-zinc-100">
                <header className="border-b border-zinc-800/80 bg-zinc-900/70">
                    <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-8 sm:px-6 md:flex-row md:items-end md:justify-between">
                        <div className="space-y-2">
                            <p className="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
                                StockLane
                            </p>
                            <h1 className="max-w-xl text-3xl font-semibold tracking-tight text-zinc-50 sm:text-4xl">
                                SME inventory board
                            </h1>
                            <p className="max-w-[65ch] text-sm leading-relaxed text-zinc-400 sm:text-base">
                                On-hand quantities, reorder thresholds, queued low-stock SMS, and
                                PayMongo restock payment webhooks.
                            </p>
                        </div>
                        <p className="text-xs text-zinc-500 md:text-right">
                            Portfolio 2026 · Laravel · MySQL · Redis
                        </p>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6">
                    <section className="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                        <Metric label="SKUs" value={summary.sku_count} />
                        <Metric label="Units on hand" value={summary.units_on_hand} />
                        <Metric
                            label="Low stock"
                            value={summary.low_stock_count}
                            warn={summary.low_stock_count > 0}
                        />
                    </section>

                    {/* Mobile: card stack */}
                    <section className="space-y-3 md:hidden" aria-label="Products mobile">
                        <h2 className="text-sm font-medium text-zinc-300">Products</h2>
                        {products.length === 0 && <EmptyState />}
                        {products.map((product) => (
                            <article
                                key={product.id}
                                className="rounded-2xl border border-zinc-800 bg-zinc-900/50 p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-mono text-sm text-emerald-400">
                                            {product.sku}
                                        </p>
                                        <h3 className="mt-1 text-base font-medium text-zinc-100">
                                            {product.name}
                                        </h3>
                                    </div>
                                    <StatusPill low={product.is_low_stock} />
                                </div>
                                <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt className="text-xs uppercase tracking-wide text-zinc-500">
                                            Qty
                                        </dt>
                                        <dd className="mt-1 font-mono tabular-nums text-zinc-100">
                                            {product.quantity}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs uppercase tracking-wide text-zinc-500">
                                            Reorder at
                                        </dt>
                                        <dd className="mt-1 font-mono tabular-nums text-zinc-400">
                                            {product.reorder_at}
                                        </dd>
                                    </div>
                                </dl>
                                <ActionForms
                                    productId={product.id}
                                    busy={busyId === product.id}
                                    onSale={submitSale}
                                    onRestock={submitRestock}
                                />
                            </article>
                        ))}
                    </section>

                    {/* Desktop: table */}
                    <section
                        className="hidden overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/50 md:block"
                        aria-label="Products desktop"
                    >
                        <div className="border-b border-zinc-800 px-4 py-3 text-sm font-medium text-zinc-300">
                            Products
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-zinc-950/60 text-xs uppercase tracking-wide text-zinc-500">
                                    <tr>
                                        <th className="px-4 py-3">SKU</th>
                                        <th className="px-4 py-3">Name</th>
                                        <th className="px-4 py-3">Qty</th>
                                        <th className="px-4 py-3">Reorder at</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map((product) => (
                                        <tr
                                            key={product.id}
                                            className="border-t border-zinc-800/80"
                                        >
                                            <td className="px-4 py-3 font-mono text-emerald-400">
                                                {product.sku}
                                            </td>
                                            <td className="px-4 py-3 text-zinc-200">
                                                {product.name}
                                            </td>
                                            <td className="px-4 py-3 font-mono tabular-nums">
                                                {product.quantity}
                                            </td>
                                            <td className="px-4 py-3 font-mono tabular-nums text-zinc-400">
                                                {product.reorder_at}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusPill low={product.is_low_stock} />
                                            </td>
                                            <td className="px-4 py-3">
                                                <ActionForms
                                                    productId={product.id}
                                                    busy={busyId === product.id}
                                                    onSale={submitSale}
                                                    onRestock={submitRestock}
                                                    compact
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                    {products.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-10">
                                                <EmptyState />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}

function StatusPill({ low }: { low: boolean }) {
    return low ? (
        <span className="inline-flex min-h-8 items-center rounded-md bg-amber-500/15 px-2.5 text-xs font-medium text-amber-300">
            Low stock
        </span>
    ) : (
        <span className="inline-flex min-h-8 items-center rounded-md bg-emerald-500/15 px-2.5 text-xs font-medium text-emerald-300">
            Healthy
        </span>
    );
}

function ActionForms({
    productId,
    busy,
    onSale,
    onRestock,
    compact = false,
}: {
    productId: number;
    busy: boolean;
    onSale: (e: FormEvent<HTMLFormElement>, id: number) => void;
    onRestock: (e: FormEvent<HTMLFormElement>, id: number) => void;
    compact?: boolean;
}) {
    const wrap = compact
        ? 'mt-0 flex flex-wrap gap-2'
        : 'mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2';
    const inputCls =
        'min-h-11 w-20 rounded-lg border border-zinc-700 bg-zinc-950 px-3 text-sm text-zinc-100';
    const btnBase =
        'inline-flex min-h-11 min-w-[5.5rem] items-center justify-center rounded-lg px-3 text-sm font-medium transition active:scale-[0.98] disabled:opacity-50';

    return (
        <div className={wrap}>
            <form
                className="flex items-center gap-2"
                onSubmit={(e) => onSale(e, productId)}
            >
                <label className="sr-only" htmlFor={`sale-${productId}`}>
                    Sale units
                </label>
                <input
                    id={`sale-${productId}`}
                    name="units"
                    type="number"
                    min={1}
                    defaultValue={1}
                    className={inputCls}
                />
                <button
                    type="submit"
                    disabled={busy}
                    className={`${btnBase} bg-zinc-100 text-zinc-900 hover:bg-white`}
                >
                    Sale
                </button>
            </form>
            <form
                className="flex items-center gap-2"
                onSubmit={(e) => onRestock(e, productId)}
            >
                <label className="sr-only" htmlFor={`restock-${productId}`}>
                    Restock units
                </label>
                <input
                    id={`restock-${productId}`}
                    name="units"
                    type="number"
                    min={1}
                    defaultValue={10}
                    className={inputCls}
                />
                <button
                    type="submit"
                    disabled={busy}
                    className={`${btnBase} bg-emerald-600 text-white hover:bg-emerald-500`}
                >
                    Restock
                </button>
            </form>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="rounded-2xl border border-dashed border-zinc-700 px-4 py-10 text-center text-sm text-zinc-500">
            No SKUs yet. Run{' '}
            <code className="text-emerald-400">php artisan stocklane:seed-demo</code>.
        </div>
    );
}

function Metric({
    label,
    value,
    warn = false,
}: {
    label: string;
    value: number;
    warn?: boolean;
}) {
    return (
        <div className="rounded-2xl border border-zinc-800 bg-zinc-900/50 px-4 py-5">
            <p className="text-xs uppercase tracking-wide text-zinc-500">{label}</p>
            <p
                className={`mt-2 font-mono text-3xl font-semibold tabular-nums tracking-tight ${
                    warn ? 'text-amber-300' : 'text-zinc-50'
                }`}
            >
                {value}
            </p>
        </div>
    );
}
