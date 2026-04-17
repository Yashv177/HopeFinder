import redis, json

r = redis.Redis(host='localhost', port=6379)

def push_match(data):
    r.lpush("ai_queue", json.dumps(data))