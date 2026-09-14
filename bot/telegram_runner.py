"""Minimal long-poll runner; all message decisions remain in MessageHandler."""
from __future__ import annotations
import json
from urllib import request
from .config import Config
from .handler import MessageHandler

def _call(config: Config, method: str, payload: dict) -> dict:
    req = request.Request('https://api.telegram.org/bot%s/%s' % (config.telegram_bot_token, method), data=json.dumps(payload).encode(), headers={'Content-Type':'application/json'}, method='POST')
    with request.urlopen(req, timeout=35) as response: return json.load(response)
def main() -> None:
    config, handler, offset = Config.from_env(), MessageHandler(Config.from_env()), 0
    if not config.telegram_bot_token: raise RuntimeError('TELEGRAM_BOT_TOKEN is required')
    while True:
        for update in _call(config, 'getUpdates', {'offset':offset, 'timeout':30}).get('result', []):
            offset = update['update_id'] + 1; message = update.get('message')
            if message:
                response = handler.handle_message(message)
                if response: _call(config, 'sendMessage', {'chat_id':message['chat']['id'], 'text':response, 'parse_mode':'Markdown'})
if __name__ == '__main__': main()
