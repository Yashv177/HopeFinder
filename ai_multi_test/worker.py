import redis, json, requests, time

r = redis.Redis(host='localhost', port=6379)

API_URL = "http://localhost/HopeFinder/admin/api/aiMatch.php"

while True:
    item = r.rpop("ai_queue")

    if item:
        data = json.loads(item)

        try:
            res = requests.post(API_URL, json=data)
            print("📡 Sent to API:", res.text)

        except Exception as e:
            print("❌ Retry:", e)
            r.lpush("ai_queue", json.dumps(data))

    time.sleep(1)