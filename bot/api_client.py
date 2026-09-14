"""Small HTTP-only client for the Laravel read API (no database access)."""
from __future__ import annotations
import json
from urllib import error, request
from .config import Config

class ApiClient:
    def __init__(self, config: Config): self.config = config
    def query(self, intent: dict, telegram_user_id: str) -> dict:
        url = self.config.spmb_api_base_url.rstrip('/') + '/query'
        data = json.dumps(intent).encode('utf-8')
        req = request.Request(url, data=data, method='POST', headers={'Content-Type':'application/json','Authorization':'Bearer ' + self.config.spmb_data_bot_token,'X-Telegram-User-Id':str(telegram_user_id)})
        try:
            with request.urlopen(req, timeout=15) as response: return json.load(response)
        except error.HTTPError as exc:
            messages = {401:'Unauthorized',400:'Bad Request',504:'API Timeout'}
            return {'status':'error','message':messages.get(exc.code, 'Server Error' if exc.code >= 500 else 'API Error'),'code':exc.code}
        except (error.URLError, TimeoutError): return {'status':'error','message':'API Timeout','code':504}
        except (ValueError, OSError): return {'status':'error','message':'Server Error','code':500}
