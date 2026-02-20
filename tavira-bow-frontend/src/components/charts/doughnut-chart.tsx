import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Tooltip,
} from 'recharts'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

interface DoughnutChartData {
  name: string
  value: number
  color: string
}

interface DoughnutChartProps {
  title: string
  description?: string
  data: DoughnutChartData[]
  height?: number
  showLegend?: boolean
  innerRadius?: number
  outerRadius?: number
}

export function DoughnutChart({
  title,
  description,
  data,
  height = 300,
  showLegend = true,
  innerRadius = 60,
  outerRadius = 100,
}: DoughnutChartProps) {
  const total = data.reduce((sum, item) => sum + item.value, 0)

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>
        <div className="flex items-start gap-4">
          <div className="shrink-0" style={{ width: (outerRadius + 10) * 2, height }}>
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={data}
                  cx="50%"
                  cy="50%"
                  innerRadius={innerRadius}
                  outerRadius={outerRadius}
                  paddingAngle={2}
                  dataKey="value"
                  label={false}
                >
                  {data.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip
                  formatter={(value: number, _name: string, props: { payload?: DoughnutChartData }) => {
                    const label = props.payload?.name ?? 'Unknown'
                    return [value, label]
                  }}
                  contentStyle={{
                    backgroundColor: 'hsl(var(--card))',
                    border: '1px solid hsl(var(--border))',
                    borderRadius: '8px',
                  }}
                />
              </PieChart>
            </ResponsiveContainer>
          </div>
          {showLegend && (
            <div className="flex-1 overflow-y-auto space-y-1.5 text-sm" style={{ maxHeight: height }}>
              {data.map((item) => {
                const pct = total > 0 ? ((item.value / total) * 100).toFixed(1) : '0.0'
                return (
                  <div key={item.name} className="flex items-center gap-2">
                    <span
                      className="inline-block h-3 w-3 shrink-0 rounded-sm"
                      style={{ backgroundColor: item.color }}
                    />
                    <span className="truncate text-muted-foreground">
                      {item.name}
                    </span>
                    <span className="ml-auto shrink-0 font-medium tabular-nums">
                      {pct}%
                    </span>
                  </div>
                )
              })}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
