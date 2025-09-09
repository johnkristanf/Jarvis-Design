export interface ChartDataset {
    label: string
    backgroundColor: string
    data: number[]
}

export interface SalesReport {
    labels: string[]
    datasets: ChartDataset[]
}
