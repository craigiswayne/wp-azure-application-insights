# Top 10 Countries

```kusto
pageViews
| summarize Count = count() by client_CountryOrRegion
| order by Count desc
| top 10 by Count desc
| render piechart
```

## Preview
![image](../images/chart-pie.png)