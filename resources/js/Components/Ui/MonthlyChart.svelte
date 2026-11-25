<script lang="ts">
    import { rupiah } from '../../lib/format';

    let { data, year }: { data: number[]; year: number } = $props();

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const width = 760;
    const height = 280;
    const left = 56;
    const right = 16;
    const top = 16;
    const bottom = 34;
    const plotWidth = width - left - right;
    const plotHeight = height - top - bottom;

    let max = $derived(Math.max(1, ...data));
    let total = $derived(data.reduce((sum, value) => sum + value, 0));

    let bars = $derived(
        data.map((value, index) => {
            const slot = plotWidth / 12;
            const barWidth = slot * 0.55;
            const barHeight = (value / max) * plotHeight;

            return {
                key: months[index],
                x: left + index * slot + (slot - barWidth) / 2,
                y: top + plotHeight - barHeight,
                width: barWidth,
                height: barHeight,
                value,
                labelX: left + index * slot + slot / 2,
            };
        }),
    );

    let ticks = $derived(
        [0, 0.25, 0.5, 0.75, 1].map((ratio) => ({
            y: top + plotHeight - ratio * plotHeight,
            value: Math.round(ratio * max),
        })),
    );

    function compact(value: number): string {
        if (value >= 1_000_000_000) {
            return `${(value / 1_000_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`;
        }

        if (value >= 1_000_000) {
            return `${(value / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
        }

        if (value >= 1_000) {
            return `${(value / 1_000).toLocaleString('id-ID', { maximumFractionDigits: 0 })} rb`;
        }

        return String(value);
    }
</script>

<div>
    <div class="mb-3 flex items-baseline justify-between gap-4">
        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Pendapatan bulanan {year}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">Total {rupiah(total)}</p>
    </div>

    <svg
        viewBox="0 0 {width} {height}"
        class="h-auto w-full"
        role="img"
        aria-label="Grafik pendapatan bulanan tahun {year}"
    >
        {#each ticks as tick}
            <line
                x1={left}
                x2={width - right}
                y1={tick.y}
                y2={tick.y}
                class="stroke-slate-200 dark:stroke-slate-700"
                stroke-width="1"
                stroke-dasharray="4 4"
            />
            <text
                x={left - 8}
                y={tick.y + 4}
                text-anchor="end"
                class="fill-slate-400 dark:fill-slate-500"
                style="font-size:10px"
            >
                {compact(tick.value)}
            </text>
        {/each}

        {#each bars as bar (bar.key)}
            <rect
                x={bar.x}
                y={bar.y}
                width={bar.width}
                height={bar.height}
                rx="4"
                class="fill-orange-500 dark:fill-orange-400"
            >
                <title>{bar.key} {year}: {rupiah(bar.value)}</title>
            </rect>

            <text
                x={bar.labelX}
                y={height - 12}
                text-anchor="middle"
                class="fill-slate-500 dark:fill-slate-400"
                style="font-size:10px"
            >
                {bar.key}
            </text>
        {/each}

        <line
            x1={left}
            x2={width - right}
            y1={top + plotHeight}
            y2={top + plotHeight}
            class="stroke-slate-300 dark:stroke-slate-600"
            stroke-width="1"
        />
    </svg>
</div>
