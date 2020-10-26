"""This module contains methods for working with a JSON file as a kind of simple database. This
allows the tokens to be persisted between runs of the server.

This should not be used as any kind of production storage, it's just a simple way for a single
user application to store data, for this example application.
"""
import json
import os

from app.constants import DB_FILENAME

_ENV_STATE = {
    "client_id": "CCB_CLIENT_ID",
    "client_key": "CCB_CLIENT_KEY",
}


def _db_exists():
    return os.path.exists(DB_FILENAME)


def _read_db():
    with open(DB_FILENAME, "r") as rfile:
        return json.load(rfile)


def _write_db(state):
    with open(DB_FILENAME, "w") as wfile:
        json.dump(state, wfile)


def init_state():
    if _db_exists():
        return

    _write_db(
        {
            "access_token": None,
            "refresh_token": None,
            "token_expires_at": 0,
        }
    )


def get_state(key, fallback=None):
    if key in _ENV_STATE:
        return os.environ[_ENV_STATE[key]]

    state = _read_db()
    return state.get(key, fallback)


def set_state(key, value):
    state = _read_db()
    state[key] = value
    _write_db(state)
