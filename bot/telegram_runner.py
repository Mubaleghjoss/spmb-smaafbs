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
    config, offset = Config.from_env(), 0
    handler = MessageHandler(config)
    if not config.telegram_bot_token: raise RuntimeError('TELEGRAM_BOT_TOKEN is required')
    while True:
        for update in _call(config, 'getUpdates', {'offset':offset, 'timeout':30}).get('result', []):
            offset = update['update_id'] + 1; message = update.get('message')
            callback = update.get('callback_query')
            if callback:
                result = handler.handle_callback_query(callback)
                if result:
                    callback_message = callback.get('message', {})
                    payload = {'chat_id': callback_message.get('chat', {}).get('id'), 'text': result['text'], 'parse_mode': 'Markdown'}
                    if result.get('reply_markup'):
                        payload['reply_markup'] = result['reply_markup']
                    _call(config, 'sendMessage', payload)
                    _call(config, 'answerCallbackQuery', {'callback_query_id': callback.get('id')})
                continue
            if message:
                response = handler.handle_message(message)
                if response:
                    payload = {'chat_id':message['chat']['id'], 'text':response, 'parse_mode':'Markdown'}
                    if handler.last_reply_markup:
                        payload['reply_markup'] = handler.last_reply_markup
                    _call(config, 'sendMessage', payload)
if __name__ == '__main__': main()
