# Mountain Dashboard

This sample Flask application visualizes mountain log data. It aggregates daily, monthly and yearly counts from a CSV file, shows the latest status and map markers, and allows exporting aggregated data.

## Setup
1. Install dependencies (Flask)

```bash
pip install flask
```

2. (Optional) Set your Google Maps API key:

```bash
export GOOGLE_MAPS_KEY=YOUR_KEY
```

The map will still load without a key but may be limited.

3. Run the server:

```bash
python app.py
```

Then open <http://localhost:5000> in your browser.

## CSV data
Edit `data/mountain_logs.csv` to add or modify log entries.

## Export
Click "集計CSVダウンロード" in the dashboard to download aggregated data.
