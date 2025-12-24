from flask import Flask, render_template, jsonify, send_file
import csv
from collections import defaultdict
from datetime import datetime
import io
import os

app = Flask(__name__)

# Optionally provide a Google Maps API key via environment variable.
GOOGLE_MAPS_KEY = os.environ.get("GOOGLE_MAPS_KEY", "")

DATA_FILE = os.path.join(os.path.dirname(__file__), 'data', 'mountain_logs.csv')

def load_rows():
    rows = []
    with open(DATA_FILE, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            rows.append(row)
    return rows

def aggregate(rows):
    """Aggregate counts by day, month and year."""
    daily = defaultdict(lambda: {'\u5165\u5c71': 0, '\u4e0b\u5c71': 0})
    monthly = defaultdict(lambda: {'\u5165\u5c71': 0, '\u4e0b\u5c71': 0})
    yearly = defaultdict(lambda: {'\u5165\u5c71': 0, '\u4e0b\u5c71': 0})
    for r in rows:
        dt = datetime.strptime(r['date'], '%Y-%m-%d')
        ev = r['event']
        daily[dt.strftime('%Y-%m-%d')][ev] += 1
        monthly[dt.strftime('%Y-%m')][ev] += 1
        yearly[str(dt.year)][ev] += 1

    # Sort keys so charts display in chronological order
    daily_sorted = {k: daily[k] for k in sorted(daily)}
    monthly_sorted = {k: monthly[k] for k in sorted(monthly)}
    yearly_sorted = {k: yearly[k] for k in sorted(yearly)}
    return daily_sorted, monthly_sorted, yearly_sorted

def latest_status(rows):
    if not rows:
        return {}
    last = rows[-1]
    return {
        'voltage': last['voltage'],
        'temperature': last['temperature'],
        'crowd': last['crowd'],
        'lat': last['lat'],
        'lng': last['lng']
    }

@app.route('/')
def index():
    """Render the dashboard template."""
    return render_template('index.html', google_maps_key=GOOGLE_MAPS_KEY)

@app.route('/data')
def data():
    rows = load_rows()
    daily, monthly, yearly = aggregate(rows)
    status = latest_status(rows)
    return jsonify({
        'daily': daily,
        'monthly': monthly,
        'yearly': yearly,
        'status': status,
        'points': rows
    })

@app.route('/export')
def export_csv():
    rows = load_rows()
    daily, monthly, yearly = aggregate(rows)
    output = io.StringIO()
    writer = csv.writer(output)
    writer.writerow(['Type', 'Period', '\u5165\u5c71', '\u4e0b\u5c71'])
    for k,v in daily.items():
        writer.writerow(['daily', k, v['\u5165\u5c71'], v['\u4e0b\u5c71']])
    for k,v in monthly.items():
        writer.writerow(['monthly', k, v['\u5165\u5c71'], v['\u4e0b\u5c71']])
    for k,v in yearly.items():
        writer.writerow(['yearly', k, v['\u5165\u5c71'], v['\u4e0b\u5c71']])
    mem = io.BytesIO()
    mem.write('\ufeff'.encode('utf-8'))
    mem.write(output.getvalue().encode('utf-8'))
    mem.seek(0)
    return send_file(mem,
                     mimetype='text/csv',
                     as_attachment=True,
                     download_name='aggregated.csv')

if __name__ == '__main__':
    app.run(debug=True)
