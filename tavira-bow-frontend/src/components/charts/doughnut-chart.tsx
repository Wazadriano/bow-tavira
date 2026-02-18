import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Legend,
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
        <ResponsiveContainer width="100%" height={height}>
          <PieChart>
            <Pie
              data={data}
              cx="50%"
              cy="50%"
              innerRadius={innerRadius}
              outerRadius={outerRadius}
              paddingAngle={2}
              dataKey="value"
              label={({ name, value }) => `${name ?? 'N/A'}: ${value ?? 0}`}
              labelLine={false}
            >
              {data.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Pie>
            <Tooltip
              formatter={(value: number) => [value, 'Count']}
              contentStyle={{
                backgroundColor: 'hsl(var(--card))',
                border: '1px solid hsl(var(--border))',
                borderRadius: '8px',
              }}
            />
            {showLegend && (
              <Legend
                formatter={(value, entry) => {
                  const item = data.find((d) => d.name === value)
                  const percentage = item && total > 0 ? ((item.value / total) * 100).toFixed(1) : '0.0'
                  return `${value} (${percentage}%)`
                }}
              />
            )}
          </PieChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  )
}
