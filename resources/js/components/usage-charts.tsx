import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const tooltipStyle = {
    borderRadius: 'var(--radius)',
    border: '1px solid var(--border)',
    background: 'var(--popover)',
    color: 'var(--popover-foreground)',
    fontSize: '12px',
};

type DailyPoint = {
    date: string;
    calls: number;
    verifications: number;
};

export function UsageAreaChart({ data }: { data: DailyPoint[] }) {
    const points = data.map((point) => ({
        ...point,
        label: new Date(`${point.date}T00:00:00`).toLocaleDateString(
            undefined,
            {
                month: 'short',
                day: 'numeric',
            },
        ),
    }));

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">API calls over time</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-64 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart
                            data={points}
                            margin={{ top: 4, right: 4, left: -16, bottom: 0 }}
                        >
                            <defs>
                                <linearGradient
                                    id="callsGradient"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stopColor="var(--chart-1)"
                                        stopOpacity={0.3}
                                    />
                                    <stop
                                        offset="95%"
                                        stopColor="var(--chart-1)"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="var(--border)"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="label"
                                tick={{
                                    fill: 'var(--muted-foreground)',
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={false}
                                interval="preserveStartEnd"
                                minTickGap={24}
                                angle={points.length > 20 ? -35 : 0}
                                height={points.length > 20 ? 40 : 24}
                            />
                            <YAxis
                                tick={{
                                    fill: 'var(--muted-foreground)',
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={false}
                                allowDecimals={false}
                            />
                            <Tooltip
                                contentStyle={tooltipStyle}
                                cursor={{ stroke: 'var(--border)' }}
                            />
                            <Area
                                type="monotone"
                                dataKey="calls"
                                stroke="var(--chart-1)"
                                strokeWidth={2}
                                fill="url(#callsGradient)"
                                name="Calls"
                            />
                            <Area
                                type="monotone"
                                dataKey="verifications"
                                stroke="var(--chart-2)"
                                strokeWidth={2}
                                fill="transparent"
                                name="Verifications"
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}

type QueryPoint = {
    query: string;
    calls: number;
};

export function UsageByQueryChart({ data }: { data: QueryPoint[] }) {
    const points = data.length > 0 ? data : [{ query: 'No data', calls: 0 }];

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Calls by age query</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-56 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={points}
                            margin={{ top: 4, right: 4, left: -16, bottom: 0 }}
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                stroke="var(--border)"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="query"
                                tick={{
                                    fill: 'var(--muted-foreground)',
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={false}
                                interval={0}
                            />
                            <YAxis
                                tick={{
                                    fill: 'var(--muted-foreground)',
                                    fontSize: 11,
                                }}
                                tickLine={false}
                                axisLine={false}
                                allowDecimals={false}
                            />
                            <Tooltip
                                contentStyle={tooltipStyle}
                                cursor={{ fill: 'var(--muted)' }}
                            />
                            <Bar
                                dataKey="calls"
                                fill="var(--chart-3)"
                                radius={[4, 4, 0, 0]}
                                name="Calls"
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
