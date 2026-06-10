export interface MonthlyTrendPoint {
    label: string;
    ym: string;
    created: number;
    resolved: number;
}

interface Props {
    data: MonthlyTrendPoint[];
}

const WIDTH = 600;
const HEIGHT = 220;
const PAD = { top: 16, right: 16, bottom: 28, left: 36 };
const BAR_GAP = 2;
const GROUP_PAD = 8;

export default function MonthlyTrendChart({ data }: Props) {
    if (!data.length) {
        return <p className="text-sm text-gray-500">Belum ada data tren.</p>;
    }

    const max = Math.max(1, ...data.flatMap((d) => [d.created, d.resolved]));
    const innerW = WIDTH - PAD.left - PAD.right;
    const innerH = HEIGHT - PAD.top - PAD.bottom;

    const groupW = innerW / data.length;
    const barW = (groupW - GROUP_PAD * 2 - BAR_GAP) / 2;
    const yFor = (v: number) => PAD.top + innerH - (v / max) * innerH;
    const barH = (v: number) => (v / max) * innerH;

    const gridYs = [0, 0.25, 0.5, 0.75, 1].map((p) => ({
        y: PAD.top + innerH - p * innerH,
        v: Math.round(p * max),
    }));

    return (
        <div className="w-full">
            <div className="mb-3 flex flex-wrap items-center gap-4 text-xs">
                <LegendBar color="#0095c7" label="Dibuat" />
                <LegendBar color="#10b981" label="Selesai" />
            </div>
            <svg
                viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                className="h-auto w-full"
                role="img"
                aria-label="Tren tiket bulanan"
            >
                {gridYs.map((g, i) => (
                    <g key={i}>
                        <line
                            x1={PAD.left}
                            x2={WIDTH - PAD.right}
                            y1={g.y}
                            y2={g.y}
                            stroke="#e5e7eb"
                            strokeDasharray="2 3"
                        />
                        <text
                            x={PAD.left - 6}
                            y={g.y + 4}
                            textAnchor="end"
                            fontSize={10}
                            fill="#9ca3af"
                        >
                            {g.v}
                        </text>
                    </g>
                ))}

                {data.map((d, i) => {
                    const groupX = PAD.left + i * groupW;
                    const x1 = groupX + GROUP_PAD;
                    const x2 = x1 + barW + BAR_GAP;
                    return (
                        <g key={d.ym}>
                            <rect
                                x={x1}
                                y={yFor(d.created)}
                                width={barW}
                                height={barH(d.created)}
                                fill="#0095c7"
                                rx={2}
                            >
                                <title>{`${d.label} · Dibuat: ${d.created}`}</title>
                            </rect>
                            <rect
                                x={x2}
                                y={yFor(d.resolved)}
                                width={barW}
                                height={barH(d.resolved)}
                                fill="#10b981"
                                rx={2}
                            >
                                <title>{`${d.label} · Selesai: ${d.resolved}`}</title>
                            </rect>
                            <text
                                x={groupX + groupW / 2}
                                y={HEIGHT - 8}
                                textAnchor="middle"
                                fontSize={10}
                                fill="#6b7280"
                            >
                                {d.label}
                            </text>
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

function LegendBar({ color, label }: { color: string; label: string }) {
    return (
        <span className="inline-flex items-center gap-1.5 text-gray-600">
            <span
                className="inline-block h-2.5 w-4 rounded-sm"
                style={{ backgroundColor: color }}
            />
            {label}
        </span>
    );
}
